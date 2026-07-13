<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\ReplySupportTicketRequest;
use App\Http\Requests\Support\StoreSupportTicketRequest;
use App\Http\Requests\Support\UpdateSupportTicketRequest;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Support\SupportTicketService;
use App\Support\UserScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportTicketController extends Controller
{
    public function __construct(
        private readonly SupportTicketService $supportTicketService,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SupportTicket::class);
        $user = auth()->user();
        $canManage = UserScope::isPlatformAdmin($user);

        return view('admin.support-tickets.index', [
            'pageTitle' => $canManage ? 'Destek Talepleri' : 'Destek Taleplerim',
            'tickets' => $this->supportTicketService->list(
                $user,
                $request->only(['status', 'priority', 'user_id']),
            ),
            'filters' => $request->only(['status', 'priority', 'user_id']),
            'canManage' => $canManage,
            'users' => $canManage ? $this->userRepository->all() : collect(),
            'statuses' => TicketStatus::cases(),
            'priorities' => TicketPriority::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', SupportTicket::class);

        return view('admin.support-tickets.create', [
            'pageTitle' => 'Yeni Destek Talebi',
            'categories' => TicketCategory::cases(),
            'priorities' => TicketPriority::cases(),
        ]);
    }

    public function store(StoreSupportTicketRequest $request): RedirectResponse
    {
        $ticket = $this->supportTicketService->create(
            auth()->user(),
            $request->safe()->except('attachments'),
            $request->file('attachments', []),
        );

        return redirect()
            ->route('admin.support-tickets.show', $ticket)
            ->with('success', 'Destek talebiniz oluşturuldu.');
    }

    public function show(SupportTicket $ticket): View
    {
        $this->authorize('view', $ticket);
        $user = auth()->user();
        $canManage = UserScope::isPlatformAdmin($user);

        $ticket->load(['user', 'assignee', 'statusLogs.user']);

        $messagesQuery = $canManage ? $ticket->messages() : $ticket->publicMessages();
        $messages = $messagesQuery->with(['user', 'attachments'])->get();

        return view('admin.support-tickets.show', [
            'pageTitle' => 'Talep #'.$ticket->ticket_number,
            'ticket' => $ticket,
            'messages' => $messages,
            'statusLogs' => $ticket->statusLogs,
            'canManage' => $canManage,
            'statuses' => TicketStatus::cases(),
            'priorities' => TicketPriority::cases(),
            'staffUsers' => $canManage ? $this->userRepository->all() : collect(),
        ]);
    }

    public function reply(ReplySupportTicketRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorize('reply', $ticket);

        $this->supportTicketService->reply(
            $ticket,
            auth()->user(),
            $request->validated('body'),
            $request->boolean('is_internal'),
            $request->file('attachments', []),
        );

        return back()->with('success', 'Yanıtınız gönderildi.');
    }

    public function update(UpdateSupportTicketRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);
        $this->supportTicketService->update($ticket, $request->validated(), auth()->user());

        return back()->with('success', 'Talep güncellendi.');
    }

    public function downloadAttachment(SupportTicketAttachment $attachment): StreamedResponse
    {
        $attachment->load('message.ticket');
        $this->authorize('view', $attachment->message->ticket);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }
}
