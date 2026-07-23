<?php

namespace App\Services\Sms;

use App\DTOs\ActivityLog\CreateActivityLogData;
use App\DTOs\Sms\SendBulkSmsData;
use App\DTOs\Sms\SendSmsData;
use App\Enums\ActivityAction;
use App\Enums\SmsMessageStatus;
use App\Enums\SmsProviderDriver;
use App\Events\Sms\SmsBulkQueued;
use App\Events\Sms\SmsMessageQueued;
use App\Exceptions\BusinessException;
use App\Jobs\Sms\SendSmsBatchJob;
use App\Jobs\Sms\SendSmsJob;
use App\Models\SmsMessage;
use App\Models\User;
use App\Repositories\Contracts\SmsMessageRepositoryInterface;
use App\Repositories\Contracts\SmsProviderRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\ActivityLogServiceInterface;
use App\Services\Contracts\SmsSendServiceInterface;
use App\Services\Contracts\UserSenderNumberServiceInterface;
use App\Services\Contracts\WalletServiceInterface;
use App\Sms\Support\PhoneNormalizer;
use App\Sms\Support\SmsSegmentCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * SMS gönderim servis implementasyonu.
 */
class SmsSendService implements SmsSendServiceInterface
{
    public function __construct(
        private readonly SmsMessageRepositoryInterface $smsMessageRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly ActivityLogServiceInterface $activityLogService,
        private readonly SmsProviderRepositoryInterface $smsProviderRepository,
        private readonly WalletServiceInterface $walletService,
        private readonly UserSenderNumberServiceInterface $userSenderNumberService,
        private readonly SmsSegmentCalculator $segmentCalculator,
        private readonly PhoneNormalizer $phoneNormalizer,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function send(User $user, SendSmsData $data): SmsMessage
    {
        $this->ensureProviderMessageLength($data->message);
        $recipient = $this->phoneNormalizer->normalize($data->recipient);

        if (! $this->phoneNormalizer->isValidRecipient($recipient)) {
            throw new BusinessException('Geçerli bir telefon numarası giriniz (ör. 5551234567 veya 905551234567).');
        }

        $message = $this->createAndQueueMessage(
            user: $user,
            recipient: $recipient,
            message: $data->message,
            senderId: $this->userSenderNumberService->resolveSenderId($user, $data->senderId),
            dispatchImmediately: false,
        );

        $this->dispatchBatches(collect([$message]), sync: $this->shouldDispatchSync(1));

        $this->activityLogService->record(new CreateActivityLogData(
            action: ActivityAction::Created,
            description: "SMS gönderildi/kuyruğa alındı: {$recipient}",
            userId: Auth::id(),
            subjectType: SmsMessage::class,
            subjectId: $message->id,
            properties: ['segments' => $message->segments],
            ipAddress: request()->ip(),
            userAgent: request()->userAgent(),
        ));

        event(new SmsMessageQueued($message->fresh()));

        return $message->fresh();
    }

    /**
     * {@inheritDoc}
     */
    public function sendBulk(User $user, SendBulkSmsData $data): Collection
    {
        $this->ensureProviderMessageLength($data->message);

        if ($data->recipients === []) {
            throw new BusinessException('En az bir telefon numarası giriniz.');
        }

        $recipients = [];
        foreach ($data->recipients as $recipientLine) {
            $recipient = $this->phoneNormalizer->normalize($recipientLine);

            if (! $this->phoneNormalizer->isValidRecipient($recipient)) {
                throw new BusinessException("Geçersiz telefon numarası: {$recipientLine}");
            }

            $recipients[$recipient] = $recipient;
        }
        $recipients = array_values($recipients);

        if (count($recipients) > config('sms.batch_size', 1000)) {
            throw new BusinessException('Tek seferde en fazla '.config('sms.batch_size', 1000).' benzersiz numaraya SMS gönderilebilir.');
        }

        $batchId = (string) Str::uuid();
        $messages = collect();
        $senderId = $this->userSenderNumberService->resolveSenderId($user, $data->senderId);

        DB::transaction(function () use ($user, $data, $recipients, $batchId, $senderId, &$messages): void {
            foreach ($recipients as $recipient) {
                $messages->push($this->createAndQueueMessage(
                    user: $user,
                    recipient: $recipient,
                    message: $data->message,
                    senderId: $senderId,
                    batchId: $batchId,
                    dispatchImmediately: false,
                ));
            }
        });

        $this->dispatchBatches($messages, sync: $this->shouldDispatchSync($messages->count()));

        $this->activityLogService->record(new CreateActivityLogData(
            action: ActivityAction::Created,
            description: "Toplu SMS gönderildi/kuyruğa alındı: {$messages->count()} adet",
            userId: Auth::id(),
            subjectType: SmsMessage::class,
            properties: ['batch_id' => $batchId, 'count' => $messages->count()],
            ipAddress: request()->ip(),
            userAgent: request()->userAgent(),
        ));

        event(new SmsBulkQueued($user, $batchId, $messages->count()));

        return $messages->map(fn (SmsMessage $message) => $message->fresh())->values();
    }

    /**
     * SMS kaydı oluşturur (hak düşer). Gönderim ayrı dispatch edilir.
     */
    private function createAndQueueMessage(
        User $user,
        string $recipient,
        string $message,
        ?string $senderId,
        ?string $batchId = null,
        bool $dispatchImmediately = true,
    ): SmsMessage {
        $segments = $this->segmentCalculator->calculateSegments($message);
        $creditsUsed = $segments;

        return DB::transaction(function () use ($user, $recipient, $message, $senderId, $batchId, $segments, $creditsUsed, $dispatchImmediately): SmsMessage {
            $lockedUser = $this->userRepository->findByIdOrFail($user->id);
            $lockedUser->load('organization');

            $available = (float) $this->walletService->getAvailableBalance($lockedUser);

            if ($available < $creditsUsed) {
                throw new BusinessException(
                    "Yetersiz SMS hakkı. Kalan: {$available} adet, gereken: {$creditsUsed} adet. Paket satın alın veya yöneticide paket dağıtımı yaptırın."
                );
            }

            /** @var SmsMessage $smsMessage */
            $smsMessage = $this->smsMessageRepository->create([
                'user_id' => $lockedUser->id,
                'organization_id' => $lockedUser->organization_id,
                'batch_id' => $batchId,
                'recipient' => $recipient,
                'message' => $message,
                'sender_id' => $senderId,
                'provider' => $this->resolveProviderCode(),
                'status' => SmsMessageStatus::Queued->value,
                'segments' => $segments,
                'cost' => $creditsUsed,
            ]);

            $this->walletService->debit(
                $lockedUser,
                (float) $creditsUsed,
                "SMS gönderimi ({$segments} segment): {$recipient}",
                $smsMessage,
            );

            if ($dispatchImmediately) {
                SendSmsJob::dispatchSync($smsMessage->id);
            }

            return $smsMessage;
        });
    }

    /**
     * @param  Collection<int, SmsMessage>  $messages
     */
    private function dispatchBatches(Collection $messages, bool $sync): void
    {
        foreach ($messages->pluck('id')->chunk(SendSmsBatchJob::MAX_MESSAGES) as $chunkIndex => $messageIds) {
            $ids = $messageIds->values()->all();

            if ($sync) {
                SendSmsBatchJob::dispatchSync($ids);

                continue;
            }

            SendSmsBatchJob::dispatch($ids)
                ->delay(now()->addSeconds(intdiv((int) $chunkIndex, 25)));
        }
    }

    private function shouldDispatchSync(int $recipientCount): bool
    {
        $mode = (string) config('sms.dispatch_mode', 'auto');

        if ($mode === 'sync') {
            return true;
        }

        if ($mode === 'queue') {
            return false;
        }

        // auto: küçük/orta manuel gönderimler worker beklemeden tamamlanır.
        return $recipientCount <= (int) config('sms.sync_threshold', 300);
    }

    private function resolveProviderCode(): string
    {
        $provider = $this->smsProviderRepository->findDefaultActive();

        return $provider?->code ?? config('sms.default_provider', 'mock');
    }

    private function ensureProviderMessageLength(string $message): void
    {
        $provider = $this->smsProviderRepository->findDefaultActive();
        $usesTexcell = $provider?->driver === SmsProviderDriver::Texcell
            || ($provider === null && config('sms.default_provider') === SmsProviderDriver::Texcell->value);

        if ($usesTexcell && mb_strlen($message) > 1024) {
            throw new BusinessException('Texcell mesaj metni en fazla 1024 karakter olabilir.');
        }
    }
}
