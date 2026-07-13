<?php

namespace App\Services\Sms;

use App\DTOs\ActivityLog\CreateActivityLogData;
use App\DTOs\Sms\SendBulkSmsData;
use App\DTOs\Sms\SendSmsData;
use App\Enums\ActivityAction;
use App\Enums\SmsMessageStatus;
use App\Events\Sms\SmsBulkQueued;
use App\Events\Sms\SmsMessageQueued;
use App\Exceptions\BusinessException;
use App\Jobs\Sms\SendSmsJob;
use App\Models\SmsMessage;
use App\Models\User;
use App\Repositories\Contracts\SmsMessageRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\ActivityLogServiceInterface;
use App\Services\Contracts\SmsSendServiceInterface;
use App\Repositories\Contracts\SmsProviderRepositoryInterface;
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
        $recipient = $this->phoneNormalizer->normalize($data->recipient);

        if (! $this->phoneNormalizer->isValidTurkishMobile($recipient)) {
            throw new BusinessException('Geçerli bir cep telefonu numarası giriniz.');
        }

        return $this->createAndQueueMessage(
            user: $user,
            recipient: $recipient,
            message: $data->message,
            senderId: $this->userSenderNumberService->resolveSenderId($user, $data->senderId),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function sendBulk(User $user, SendBulkSmsData $data): Collection
    {
        if ($data->recipients === []) {
            throw new BusinessException('En az bir telefon numarası giriniz.');
        }

        if (count($data->recipients) > config('sms.batch_size', 100)) {
            throw new BusinessException('Tek seferde en fazla '.config('sms.batch_size', 100).' numaraya SMS gönderilebilir.');
        }

        $batchId = (string) Str::uuid();
        $messages = collect();
        $senderId = $this->userSenderNumberService->resolveSenderId($user, $data->senderId);

        DB::transaction(function () use ($user, $data, $batchId, $senderId, &$messages): void {
            foreach ($data->recipients as $recipientLine) {
                $recipient = $this->phoneNormalizer->normalize($recipientLine);

                if (! $this->phoneNormalizer->isValidTurkishMobile($recipient)) {
                    throw new BusinessException("Geçersiz telefon numarası: {$recipientLine}");
                }

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

        foreach ($messages as $message) {
            SendSmsJob::dispatch($message->id);
        }

        $this->activityLogService->record(new CreateActivityLogData(
            action: ActivityAction::Created,
            description: "Toplu SMS kuyruğa alındı: {$messages->count()} adet",
            userId: Auth::id(),
            subjectType: SmsMessage::class,
            properties: ['batch_id' => $batchId, 'count' => $messages->count()],
            ipAddress: request()->ip(),
            userAgent: request()->userAgent(),
        ));

        event(new SmsBulkQueued($user, $batchId, $messages->count()));

        return $messages;
    }

    /**
     * SMS kaydı oluşturur ve kuyruğa alır.
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

            if ((float) $this->walletService->getAvailableBalance($lockedUser) < $creditsUsed) {
                throw new BusinessException('Yetersiz SMS hakkı. Lütfen kredi yükleyin.');
            }

            $this->walletService->debit($lockedUser, (float) $creditsUsed, "SMS gönderimi ({$segments} segment): {$recipient}");

            $providerCode = $this->resolveProviderCode();

            /** @var SmsMessage $smsMessage */
            $smsMessage = $this->smsMessageRepository->create([
                'user_id' => $lockedUser->id,
                'organization_id' => $lockedUser->organization_id,
                'batch_id' => $batchId,
                'recipient' => $recipient,
                'message' => $message,
                'sender_id' => $senderId,
                'provider' => $providerCode,
                'status' => SmsMessageStatus::Queued->value,
                'segments' => $segments,
                'cost' => $creditsUsed,
            ]);

            if ($dispatchImmediately) {
                SendSmsJob::dispatch($smsMessage->id);

                $this->activityLogService->record(new CreateActivityLogData(
                    action: ActivityAction::Created,
                    description: "SMS kuyruğa alındı: {$recipient}",
                    userId: Auth::id(),
                    subjectType: SmsMessage::class,
                    subjectId: $smsMessage->id,
                    properties: ['segments' => $segments, 'credits_used' => $creditsUsed],
                    ipAddress: request()->ip(),
                    userAgent: request()->userAgent(),
                ));

                event(new SmsMessageQueued($smsMessage));
            }

            return $smsMessage;
        });
    }

    /**
     * Aktif varsayılan sağlayıcı kodunu döndürür.
     */
    private function resolveProviderCode(): string
    {
        $provider = $this->smsProviderRepository->findDefaultActive();

        return $provider?->code ?? config('sms.default_provider', 'mock');
    }
}
