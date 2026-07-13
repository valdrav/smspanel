<?php

namespace App\Events\Sms;

use App\Models\SmsMessage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * SMS kuyruğa alındığında tetiklenen event.
 */
class SmsMessageQueued
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly SmsMessage $smsMessage) {}
}
