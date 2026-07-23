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
use App\Models\User;
use App\Repositories\Contracts\SmsProviderRepositoryInterface;
use App\Sms\DTOs\SmsBalanceResult;
use App\Services\Contact\ContactService;
use App\Services\Contracts\SmsSendServiceInterface;
use App\Services\Contracts\UserSenderNumberServiceInterface;
use App\Services\Contracts\WalletServiceInterface;
use App\Services\Sms\SmsTemplateService;
use App\Services\Sms\TexcellBalanceSyncService;
use App\Sms\Support\PhoneNormalizer;
use App\Sms\Support\SmsSegmentCalculator;
use App\Support\UserScope;
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

        $defaultProviderIsTexcell = $this->defaultProviderIsTexcell();
        $isPlatformOperator = UserScope::isPlatformOperator($user);
        $sync = $this->maybeSyncTexcellBalance($user);
        $user->refresh();

        $walletBalance = $this->walletService->getAvailableBalance($user);
        $display = $this->resolveBalanceDisplay($user, $defaultProviderIsTexcell, $sync, $walletBalance);

        return view('admin.sms.send', [
            'pageTitle' => 'SMS Gönder',
            'balance' => $display['value'],
            'balanceUnit' => $display['unit'],
            'balanceLabel' => $display['label'],
            'walletBalance' => $walletBalance,
            'balanceSource' => $user->organization_id ? 'organization' : 'personal',
            'isPlatformOperator' => $isPlatformOperator,
            'showUpstreamBalance' => $display['show_upstream'],
            'texcellSyncError' => $display['sync_error'],
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

        $defaultProviderIsTexcell = $this->defaultProviderIsTexcell();
        $sync = $this->maybeSyncTexcellBalance($user);
        $user->refresh();

        $segments = $this->segmentCalculator->calculateSegments($message);
        $isUnicode = $this->segmentCalculator->requiresUnicodeEncoding($message);
        $walletBalance = $this->walletService->getAvailableBalance($user);
        $display = $this->resolveBalanceDisplay($user, $defaultProviderIsTexcell, $sync, $walletBalance);

        return response()->json([
            'chars' => mb_strlen($message),
            'segments' => $segments,
            'credits' => $segments,
            'encoding' => $isUnicode ? 'unicode' : 'gsm',
            'balance' => (int) floor($walletBalance),
            'can_afford' => $walletBalance >= $segments,
            'display_balance' => $display['value'],
            'display_unit' => $display['unit'],
            'display_label' => $display['label'],
            'show_upstream' => $display['show_upstream'],
            'recipient_valid' => $recipient === '' || $this->phoneNormalizer->isValidRecipient(
                $this->phoneNormalizer->normalize($recipient)
            ),
            'texcell_synced' => $sync?->success,
            'texcell_sync_error' => $display['sync_error'],
        ]);
    }

    public function store(SendSmsRequest $request): RedirectResponse|JsonResponse
    {
        $user = auth()->user();
        $this->maybeSyncTexcellBalance($user);
        $user->refresh();

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
        $this->maybeSyncTexcellBalance($user);
        $user->refresh();

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

    private function defaultProviderIsTexcell(): bool
    {
        $defaultProvider = $this->smsProviderRepository->findDefaultActive();

        return $defaultProvider?->driver === SmsProviderDriver::Texcell
            || ($defaultProvider === null && config('sms.default_provider') === 'texcell');
    }

    private function maybeSyncTexcellBalance(User $user): ?SmsBalanceResult
    {
        if (! $this->defaultProviderIsTexcell()) {
            return null;
        }

        if (! UserScope::isPlatformOperator($user)) {
            return null;
        }

        if (! filter_var(config('sms.texcell.sync_balance_to_admin', true), FILTER_VALIDATE_BOOL)) {
            return null;
        }

        return $this->texcellBalanceSyncService->syncDefault($user);
    }

    /**
     * @return array{value: float, unit: string, label: string, show_upstream: bool, sync_error: ?string}
     */
    private function resolveBalanceDisplay(
        User $user,
        bool $defaultProviderIsTexcell,
        ?SmsBalanceResult $sync,
        float $walletBalance,
    ): array {
        $isOperator = UserScope::isPlatformOperator($user);
        $syncError = $sync !== null && ! $sync->success
            ? ($sync->errorMessage ?? 'Sağlayıcı bakiyesi alınamadı.')
            : null;

        if ($isOperator && $defaultProviderIsTexcell && ($sync?->success ?? false)) {
            $usd = (float) ($sync->rawUsd ?? $sync->balance);

            return [
                'value' => $usd,
                'unit' => 'USD',
                'label' => 'Hesap bakiyesi',
                'show_upstream' => true,
                'sync_error' => null,
            ];
        }

        $label = $user->organization_id !== null ? 'Organizasyon hakkı' : 'Kalan hak';

        return [
            'value' => $walletBalance,
            'unit' => 'SMS',
            'label' => $label,
            'show_upstream' => false,
            'sync_error' => $isOperator && $defaultProviderIsTexcell ? $syncError : null,
        ];
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
