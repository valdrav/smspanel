<?php

namespace App\Providers;

use App\Services\Contracts\SettingServiceInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        if (Schema::hasTable('settings')) {
            $settings = app(SettingServiceInterface::class);
            $settings->applyBranding();

            View::share('themePrimary', $settings->get('primary_color', '#6366f1'));
            View::share('themeAccent', $settings->get('accent_color', '#22d3ee'));
            View::share('appTagline', $settings->get('app_tagline', ''));
        }
    }
}
