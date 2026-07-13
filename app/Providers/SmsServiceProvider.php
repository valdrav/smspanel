<?php

namespace App\Providers;

use App\Events\User\UserCreated;
use App\Listeners\User\LogUserCreated;
use App\Sms\Contracts\SmsProviderInterface;
use App\Sms\SmsProviderFactory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * SMS sağlayıcı servis sağlayıcısı.
 */
class SmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsProviderFactory::class);

        $this->app->bind(SmsProviderInterface::class, function ($app): SmsProviderInterface {
            return $app->make(SmsProviderFactory::class)->resolveDefault();
        });
    }

    public function boot(): void
    {
        Event::listen(UserCreated::class, LogUserCreated::class);
    }
}
