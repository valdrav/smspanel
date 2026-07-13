<?php

namespace App\Services\Setting;

use App\Models\Setting;
use App\Services\Contracts\SettingServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SettingService implements SettingServiceInterface
{
    private const CACHE_KEY = 'app_settings_all';

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->loadAll();

        return $all[$key] ?? $default;
    }

    public function set(string $key, mixed $value, string $group = 'general'): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => is_string($value) ? $value : json_encode($value), 'group' => $group],
        );

        $this->clearCache();
    }

    public function setMany(array $settings, string $group = 'general'): void
    {
        foreach ($settings as $key => $value) {
            $this->set($key, $value, $group);
        }
    }

    public function allGrouped(): array
    {
        if (! Schema::hasTable('settings')) {
            return [];
        }

        return Setting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy('group')
            ->map(fn ($items) => $items->pluck('value', 'key')->all())
            ->all();
    }

    public function applyBranding(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $appName = (string) $this->get('app_name', config('adminlte.title', 'SMS Panel'));
        $logoPath = $this->get('logo_path');

        config(['adminlte.title' => $appName]);

        if ($logoPath) {
            config(['adminlte.logo_img' => 'storage/'.$logoPath]);
            config(['adminlte.logo' => '']);
        } else {
            $parts = explode(' ', $appName, 2);
            $logoHtml = count($parts) > 1
                ? '<b>'.e($parts[0]).'</b>'.e($parts[1])
                : '<b>'.e($appName).'</b>';
            config(['adminlte.logo' => $logoHtml]);
        }
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadAll(): array
    {
        if (! Schema::hasTable('settings')) {
            return [];
        }

        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            return Setting::query()->pluck('value', 'key')->all();
        });
    }
}
