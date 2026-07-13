<?php

namespace App\Services\Contracts;

use App\DTOs\Sms\SendBulkSmsData;
use App\DTOs\Sms\SendSmsData;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * SMS gönderim servis sözleşmesi.
 */
interface SmsSendServiceInterface
{
    /**
     * Tekil SMS gönderir.
     */
    public function send(User $user, SendSmsData $data): SmsMessage;

    /**
     * Toplu SMS gönderir.
     *
     * @return Collection<int, SmsMessage>
     */
    public function sendBulk(User $user, SendBulkSmsData $data): Collection;
}
