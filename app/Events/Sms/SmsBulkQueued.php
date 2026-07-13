<?php

namespace App\Events\Sms;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Toplu SMS kuyruğa alındığında tetiklenen event.
 */
class SmsBulkQueued
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $batchId,
        public readonly int $count,
    ) {}
}
