<?php

namespace App\Jobs\Sms;

use App\Enums\SmsMessageStatus;
use App\Models\SmsMessage;
use App\Services\Sms\SmsMessageResultHandler;
use App\Sms\DTOs\SmsSendRequest;
use App\Sms\SmsProviderFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Aynı kuyruk işinde en fazla 30 mesajı sağlayıcının bulk API'sine iletir.
 */
class SendSmsBatchJob implements ShouldQueue
{
    use Queueable;

    public const MAX_MESSAGES = 30;

    /**
     * @param  list<int>  $smsMessageIds
     */
    public function __construct(public readonly array $smsMessageIds)
    {
        $this->onQueue(config('sms.queue', 'sms'));
    }

    public function handle(
        SmsProviderFactory $providerFactory,
        SmsMessageResultHandler $resultHandler,
    ): void {
        $ids = array_slice(array_values(array_unique(array_map('intval', $this->smsMessageIds))), 0, self::MAX_MESSAGES);

        $messages = SmsMessage::query()
            ->whereIn('id', $ids)
            ->where('status', SmsMessageStatus::Queued->value)
            ->get()
            ->sortBy(fn (SmsMessage $message) => array_search($message->id, $ids, true))
            ->values();

        if ($messages->isEmpty()) {
            return;
        }

        foreach ($messages->groupBy('provider') as $providerCode => $providerMessages) {
            $provider = $providerFactory->resolveByCode((string) $providerCode);
            $requests = $providerMessages
                ->map(fn (SmsMessage $message) => new SmsSendRequest(
                    to: $message->recipient,
                    message: $message->message,
                    senderId: $message->sender_id,
                    referenceId: (string) $message->id,
                ))
                ->all();

            $results = $provider->sendBulk($requests);

            foreach ($providerMessages->values() as $index => $message) {
                $resultHandler->handle(
                    $message,
                    $results[$index] ?? new \App\Sms\DTOs\SmsSendResult(
                        success: false,
                        errorMessage: 'SMS sağlayıcısı eksik gönderim yanıtı döndürdü.',
                    ),
                );
            }
        }
    }
}
