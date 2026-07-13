<?php

namespace App\Services\Support;

use App\Enums\TicketStatus;
use App\Exceptions\BusinessException;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Models\SupportTicketStatusLog;
use App\Models\User;
use App\Support\UserScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SupportTicketService
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<UploadedFile>|null  $attachments
     */
    public function create(User $user, array $data, ?array $attachments = null): SupportTicket
    {
        return DB::transaction(function () use ($user, $data, $attachments): SupportTicket {
            $ticket = SupportTicket::create([
                'ticket_number' => $this->generateTicketNumber(),
                'user_id' => $user->id,
                'subject' => $data['subject'],
                'category' => $data['category'],
                'priority' => $data['priority'],
                'status' => TicketStatus::Open,
            ]);

            $message = SupportTicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'body' => $data['body'],
                'is_internal' => false,
            ]);

            $this->storeAttachments($message, $user, $attachments);
            $this->logStatusChange($ticket, $user, null, TicketStatus::Open, 'Talep oluşturuldu');

            return $ticket->load(['user', 'messages.user', 'messages.attachments', 'statusLogs.user']);
        });
    }

    public function list(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = SupportTicket::query()->with(['user', 'assignee']);

        if (! UserScope::isPlatformAdmin($user)) {
            $query->where('user_id', $user->id);
        } elseif (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    /**
     * @param  list<UploadedFile>|null  $attachments
     */
    public function reply(
        SupportTicket $ticket,
        User $user,
        string $body,
        bool $isInternal = false,
        ?array $attachments = null,
    ): SupportTicketMessage {
        $this->assertCanAccess($ticket, $user);

        $isStaff = UserScope::isPlatformAdmin($user);

        if (in_array($ticket->status, [TicketStatus::Closed, TicketStatus::Resolved], true) && ! $isStaff) {
            throw new BusinessException('Kapalı talebe yanıt verilemez.');
        }

        $message = SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => $body,
            'is_internal' => $isInternal && $isStaff,
        ]);

        $this->storeAttachments($message, $user, $attachments);

        if ($isStaff && ! $isInternal) {
            $this->changeStatus($ticket, $user, TicketStatus::WaitingCustomer, 'Personel yanıt verdi');
        } elseif (! $isStaff) {
            $this->changeStatus($ticket, $user, TicketStatus::Open, 'Müşteri yanıt verdi');
        }

        return $message->load(['user', 'attachments']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SupportTicket $ticket, array $data, User $actor): SupportTicket
    {
        $previousStatus = $ticket->status;
        $previousAssignee = $ticket->assigned_to;
        $newStatus = isset($data['status'])
            ? TicketStatus::from($data['status'])
            : $ticket->status;

        $ticket->update([
            'assigned_to' => $data['assigned_to'] ?? $ticket->assigned_to,
            'status' => $newStatus,
            'priority' => $data['priority'] ?? $ticket->priority,
            'closed_at' => in_array($newStatus, [TicketStatus::Closed, TicketStatus::Resolved], true)
                ? ($ticket->closed_at ?? now())
                : null,
        ]);

        if ($previousStatus !== $newStatus) {
            $this->logStatusChange($ticket, $actor, $previousStatus, $newStatus, 'Durum güncellendi');
        }

        $newAssignee = $data['assigned_to'] ?? $previousAssignee;
        if (isset($data['assigned_to']) && (int) $newAssignee !== (int) $previousAssignee && ! empty($newAssignee)) {
            $this->logStatusChange($ticket, $actor, $newStatus, $newStatus, 'Talep personele atandı');
        }

        return $ticket->fresh(['user', 'assignee', 'statusLogs.user']);
    }

    public function assertCanAccess(SupportTicket $ticket, User $user): void
    {
        if (UserScope::isPlatformAdmin($user)) {
            return;
        }

        if ($ticket->user_id !== $user->id) {
            throw new BusinessException('Bu destek talebine erişim yetkiniz yok.');
        }
    }

    /**
     * @param  list<UploadedFile>|null  $files
     */
    private function storeAttachments(SupportTicketMessage $message, User $user, ?array $files): void
    {
        if ($files === null || $files === []) {
            return;
        }

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $path = $file->store("support-tickets/{$message->support_ticket_id}", 'public');

            SupportTicketAttachment::create([
                'support_ticket_message_id' => $message->id,
                'user_id' => $user->id,
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size' => $file->getSize() ?? 0,
            ]);
        }
    }

    private function changeStatus(
        SupportTicket $ticket,
        User $user,
        TicketStatus $status,
        string $note,
    ): void {
        $from = $ticket->status;

        if ($from === $status) {
            return;
        }

        $ticket->update([
            'status' => $status,
            'closed_at' => in_array($status, [TicketStatus::Closed, TicketStatus::Resolved], true)
                ? now()
                : null,
        ]);

        $this->logStatusChange($ticket, $user, $from, $status, $note);
    }

    private function logStatusChange(
        SupportTicket $ticket,
        ?User $user,
        ?TicketStatus $from,
        TicketStatus $to,
        ?string $note = null,
    ): void {
        SupportTicketStatusLog::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $user?->id,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
        ]);
    }

    private function generateTicketNumber(): string
    {
        do {
            $number = 'TKT-'.strtoupper(substr(uniqid(), -8));
        } while (SupportTicket::where('ticket_number', $number)->exists());

        return $number;
    }
}
