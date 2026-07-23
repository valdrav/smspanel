<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\Sms\SendBulkSmsData;
use App\DTOs\Sms\SendSmsData;
use App\Enums\SmsMessageStatus;
use App\Enums\SmsProviderDriver;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sms\SendBulkSmsRequest;
use App\Http\Requests\Sms\SendSmsRequest;
use App\Models\SmsMessage;
use App\Repositories\Contracts\SmsProviderRepositoryInterface;
use App\Services\Contact\ContactService;
use App\Services\Contracts\SmsSendServiceInterface;
use App\Services\Contracts\UserSenderNumberServiceInterface;
use App\Services\Contracts\WalletServiceInterface;
use App\Services\Sms\SmsTemplateService;
use App\Services\Sms\TexcellBalanceSyncService;
use App\Sms\Support\PhoneNormalizer;
use App\Sms\Support\SmsSegmentCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SmsSendController extends Controller
{
    public function __construct(
        private readonly SmsSendServiceInterface $smsSendService,
        private readonly WalletServiceInterface $walletService,
        private readonly UserSenderNumberServiceInterface $userSenderNumberService,
        private readonly ContactService $contactService,
        private readonly SmsTemplateService $smsTemplateService,
        private readonly SmsSegmentCalculator $segmentCalculator,
        private readonly PhoneNormalizer $phoneNormalizer,
        private readonly SmsProviderRepositoryInterface $smsProviderRepository,
        private readonly TexcellBalanceSyncService $texcellBalanceSyncService,
    ) {}

    public function create(): View
    {
        $this->authorize('create', SmsMessage::class);

        $user = auth()->user();
        $senderNumbers = $this->userSenderNumberService->getActiveForUser($user);
        $defaultSender = $senderNumbers->firstWhere('is_default', true)?->sender_id
            ?? $senderNumbers->first()?->sender_id
            ?? $this->userSenderNumberService->resolveSenderId($user, null);

        $defaultProvider = $this->smsProviderRepository->findDefaultActive();
        $defaultProviderIsTexcell = $defaultProvider?->driver === SmsProviderDriver::Texcell
            || ($defaultProvider === null && config('sms.default_provider') === 'texcell');

        $texcellSyncError = null;
        $texcellSynced = false;
        $texcellUsd = null;

        if (
            $defaultProviderIsTexcell
            && filter_var(config('sms.texcell.sync_balance_to_admin', true), FILTER_VALIDATE_BOOL)
        ) {
            $sync = $this->texcellBalanceSyncService->syncDefault($user);
            $user->refresh();
            $texcellSynced = $sync->success;
            $texcellSyncError = $sync->success ? null : ($sync->errorMessage ?? 'Texcell bakiye alınamadı.');
            $texcellUsd = $sync->success ? $sync->rawUsd : null;
        }

        $balance = $this->walletService->getAvailableBalance($user);

        return view('admin.sms.send', [
            'pageTitle' => 'SMS Gönder',
            'balance' => $balance,
            'balanceSource' => $user->organization_id ? 'organization' : 'personal',
            'balanceFromTexcell' => $texcellSynced,
            'texcellSyncError' => $texcellSyncError,
            'texcellUsd' => $texcellUsd,
            'defaultSenderId' => $defaultProviderIsTexcell ? '' : $defaultSender,
            'defaultProviderIsTexcell' => $defaultProviderIsTexcell,
            'senderNumbers' => $senderNumbers,
            'hasAssignedSenders' => $senderNumbers->isNotEmpty(),
            'maxBatchSize' => (int) config('sms.batch_size', 1000),
            'contacts' => $this->contactService->getActiveForUser($user),
            'templates' => $this->smsTemplateService->activeForUser($user),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $this->authorize('create', SmsMessage::class);

        $user = auth()->user();
        $message = (string) $request->input('message', '');
        $recipient = (string) $request->input('recipient', '');

        $segments = $this->segmentCalculator->calculateSegments($message);
        $isUnicode = $this->segmentCalculator->requiresUnicodeEncoding($message);
        $balance = $this->walletService->getAvailableBalance($user);

        return response()->json([
            'chars' => mb_strlen($message),
            'segments' => $segments,
            'credits' => $segments,
            'encoding' => $isUnicode ? 'unicode' : 'gsm',
            'balance' => (int) $balance,
            'can_afford' => $balance >= $segments,
            'recipient_valid' => $recipient === '' || $this->phoneNormalizer->isValidRecipient(
                $this->phoneNormalizer->normalize($recipient)
            ),
        ]);
    }

    public function store(SendSmsRequest $request): RedirectResponse|JsonResponse
    {
        $user = auth()->user();
        $message = $this->smsSendService->send(
            $user,
            SendSmsData::fromArray($request->validated()),
        );

        $payload = $this->buildSendResponse(collect([$message]), $user);

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return redirect()
            ->route('admin.sms.history.index')
            ->with('success', $payload['message']);
    }

    public function storeBulk(SendBulkSmsRequest $request): RedirectResponse|JsonResponse
    {
        $user = auth()->user();
        $messages = $this->smsSendService->sendBulk(
            $user,
            SendBulkSmsData::fromArray($request->validated()),
        );

        $payload = $this->buildSendResponse($messages, $user);

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return redirect()
            ->route('admin.sms.history.index')
            ->with('success', $payload['message']);
    }

    /**
     * @param  Collection<int, SmsMessage>  $messages
     * @return array<string, mixed>
     */
    private function buildSendResponse(Collection $messages, $user): array
    {
        $messages = $messages->map(fn (SmsMessage $message) => $message->fresh())->values();
        $sent = $messages->where('status', SmsMessageStatus::Sent)->count();
        $failed = $messages->where('status', SmsMessageStatus::Failed)->count();
        $queued = $messages->where('status', SmsMessageStatus::Queued)->count();
        $balance = (int) $this->walletService->getAvailableBalance($user);

        $summary = match (true) {
            $failed > 0 && $sent > 0 => "{$sent} SMS gönderildi, {$failed} başarısız.",
            $failed > 0 && $sent === 0 && $queued === 0 => "{$failed} SMS başarısız. Haklar iade edildi.",
            $queued > 0 => "{$messages->count()} SMS kuyruğa alındı (worker işleyecek).",
            default => "{$sent} SMS başarıyla gönderildi.",
        };

        return [
            'success' => $failed === 0,
            'message' => $summary,
            'count' => $messages->count(),
            'sent' => $sent,
            'failed' => $failed,
            'queued' => $queued,
            'balance' => $balance,
            'items' => $messages->map(fn (SmsMessage $message) => [
                'id' => $message->id,
                'recipient' => $message->recipient,
                'status' => $message->status->value,
                'status_label' => $message->status->label(),
                'segments' => $message->segments,
                'error_message' => $message->error_message,
            ])->all(),
        ];
    }
}
