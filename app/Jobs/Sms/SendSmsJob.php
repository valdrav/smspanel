<?php

namespace App\Jobs\Sms;

use App\Enums\SmsMessageStatus;
use App\Models\SmsMessage;
use App\Repositories\Contracts\SmsMessageRepositoryInterface;
use App\Services\Sms\SmsMessageResultHandler;
use App\Sms\DTOs\SmsSendRequest;
use App\Sms\SmsProviderFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
        SmsMessageResultHandler $resultHandler,
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

        $resultHandler->handle($smsMessage, $result);
    }
}
