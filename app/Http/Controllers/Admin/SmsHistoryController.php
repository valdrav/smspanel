<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\Contracts\SmsHistoryServiceInterface;
use App\Enums\SmsMessageStatus;
use App\Support\UserScope;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SmsHistoryController extends Controller
{
    public function __construct(
        private readonly SmsHistoryServiceInterface $smsHistoryService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SmsMessage::class);

        $user = auth()->user();
        $messages = $this->smsHistoryService->list(
            $user,
            filters: $request->only(['search', 'status', 'date_from', 'date_to', 'user_id']),
            perPage: 20,
        );

        $users = UserScope::isPlatformAdmin($user)
            ? User::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.sms.history.index', [
            'pageTitle' => 'SMS Geçmişi',
            'messages' => $messages,
            'statuses' => SmsMessageStatus::cases(),
            'users' => $users,
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to', 'user_id']),
        ]);
    }

    public function show(SmsMessage $smsMessage): View
    {
        $this->authorize('view', $smsMessage);

        $smsMessage->load('user');

        return view('admin.sms.history.show', [
            'pageTitle' => 'SMS Detayı',
            'smsMessage' => $smsMessage,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', SmsMessage::class);

        $user = auth()->user();
        $filters = $request->only(['search', 'status', 'date_from', 'date_to']);

        return response()->streamDownload(function () use ($user, $filters): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Tarih', 'Alıcı', 'Durum', 'Segment', 'Mesaj']);

            $page = 1;
            do {
                request()->merge(['page' => $page]);
                $batch = $this->smsHistoryService->list($user, $filters, 500);
                foreach ($batch as $message) {
                    fputcsv($handle, [
                        $message->id,
                        $message->created_at?->format('d.m.Y H:i'),
                        $message->recipient,
                        $message->status->label(),
                        $message->segments,
                        $message->message,
                    ]);
                }
                $page++;
            } while ($batch->hasMorePages());

            fclose($handle);
        }, 'sms-gecmis-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
