<?php

namespace App\Listeners\User;

use App\Events\User\UserCreated;
use Illuminate\Support\Facades\Log;

/**
 * Kullanıcı oluşturma event listener'ı.
 */
class LogUserCreated
{
    /**
     * Event'i işler.
     */
    public function handle(UserCreated $event): void
    {
        Log::channel('daily')->info('Kullanıcı oluşturuldu', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
        ]);
    }
}
