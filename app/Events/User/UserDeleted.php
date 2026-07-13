<?php

namespace App\Events\User;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Kullanıcı silindiğinde tetiklenen event.
 */
class UserDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly User $user) {}
}
