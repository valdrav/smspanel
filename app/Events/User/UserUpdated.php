<?php

namespace App\Events\User;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Kullanıcı güncellendiğinde tetiklenen event.
 */
class UserUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly User $user) {}
}
