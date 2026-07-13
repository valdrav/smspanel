<?php

namespace App\Events\User;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Kullanıcı oluşturulduğunda tetiklenen event.
 */
class UserCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly User $user) {}
}
