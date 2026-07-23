<?php

namespace App\Services\Sms;

use App\Enums\SmsProviderDriver;
use App\Models\SmsProvider;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Tek aktif/varsayılan sağlayıcı olarak Texcell’i config’den DB’ye yazar.
 * Panelden tekrar giriş gerekmez.
 */
class EnsureTexcellProvider
{
    public function ensure(): SmsProvider
    {
        if (! Schema::hasTable('sms_providers')) {
            throw new RuntimeException('sms_providers tablosu yok. Önce php artisan migrate çalıştırın.');
        }

        $config = $this->resolvedConfig();

        $texcell = SmsProvider::query()->updateOrCreate(
            ['code' => 'texcell'],
            [
                'name' => 'Texcell EIMS',
                'driver' => SmsProviderDriver::Texcell->value,
                'config' => $config,
                'is_active' => true,
                'is_default' => true,
                'priority' => 1,
            ]
        );

        SmsProvider::query()
            ->whereKeyNot($texcell->id)
            ->update([
                'is_default' => false,
                'is_active' => false,
            ]);

        return $texcell->fresh() ?? $texcell;
    }

    /**
     * @return array{account: string, password: string, base_url: string, sender: string, encryption_key: string}
     */
    public function resolvedConfig(): array
    {
        return [
            'account' => $this->nonEmpty(
                (string) config('sms.texcell.account', ''),
                'CTU780'
            ),
            'password' => $this->nonEmpty(
                (string) config('sms.texcell.password', ''),
                'EZM9lh3MVh1i'
            ),
            'base_url' => rtrim($this->nonEmpty(
                (string) config('sms.texcell.base_url', ''),
                'http://38.150.64.36:20003'
            ), '/'),
            'sender' => trim((string) config('sms.texcell.sender', '')),
            'encryption_key' => trim((string) config('sms.texcell.encryption_key', '')),
        ];
    }

    private function nonEmpty(string $value, string $fallback): string
    {
        $value = trim($value);

        return $value !== '' ? $value : $fallback;
    }
}
