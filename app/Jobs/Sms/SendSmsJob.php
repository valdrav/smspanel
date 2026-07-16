<?php

namespace App\Jobs\Sms;

use App\Enums\SmsMessageStatus;
use App\Models\SmsMessage;
use App\Models\User;
use App\Repositories\Contracts\SmsMessageRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\WalletServiceInterface;
use App\Sms\SmsProviderFactory;
use App\Sms\DTOs\SmsSendRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SMS gönderim kuyruk işi.
 */
class SendSmsJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  int  $smsMessageId  Gönderilecek SMS kaydı ID
     */
    public function __construct(public readonly int $smsMessageId)
    {
        $this->onQueue(config('sms.queue', 'sms'));
    }

    /**
     * SMS gönderimini gerçekleştirir.
     */
    public function handle(
        SmsProviderFactory $providerFactory,
        SmsMessageRepositoryInterface $smsMessageRepository,
        UserRepositoryInterface $userRepository,
        WalletServiceInterface $walletService,
    ): void {
        /** @var SmsMessage|null $smsMessage */
        $smsMessage = $smsMessageRepository->findById($this->smsMessageId);

        if ($smsMessage === null) {
            return;
        }

        if ($smsMessage->status !== SmsMessageStatus::Queued) {
            return;
        }

        $provider = $providerFactory->resolveByCode($smsMessage->provider);

        $result = $provider->send(new SmsSendRequest(
            to: $smsMessage->recipient,
            message: $smsMessage->message,
            senderId: $smsMessage->sender_id,
            referenceId: (string) $smsMessage->id,
        ));

        DB::transaction(function () use ($smsMessage, $result, $smsMessageRepository, $userRepository, $walletService): void {
            if ($result->success) {
                $smsMessageRepository->update($smsMessage, [
                    'status' => SmsMessageStatus::Sent->value,
                    'provider_message_id' => $result->messageId,
                    'sent_at' => now(),
                ]);

                return;
            }

            $smsMessageRepository->update($smsMessage, [
                'status' => SmsMessageStatus::Failed->value,
                'error_message' => $result->errorMessage ?? 'SMS gönderilemedi.',
            ]);

            $user = $userRepository->findById($smsMessage->user_id);

            if ($user !== null) {
                $walletService->refund(
                    $user,
                    (float) $smsMessage->segments,
                    "Başarısız SMS iadesi ({$smsMessage->segments} segment): {$smsMessage->recipient}",
                    $smsMessage,
                );
            }

            Log::channel('daily')->warning('SMS gönderimi başarısız', [
                'sms_message_id' => $smsMessage->id,
                'error' => $result->errorMessage,
            ]);
        });
    }
}
