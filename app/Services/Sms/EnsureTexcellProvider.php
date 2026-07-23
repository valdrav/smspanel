<?php

namespace App\Services\Sms;

use App\Enums\SmsProviderDriver;
use App\Models\SmsProvider;
use Illuminate\Support\Facades\DB;
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

        return DB::transaction(function () use ($config): SmsProvider {
            // 1) code=texcell varsa onu kullan (mock’u yeniden adlandırma — unique çakışması olmasın)
            $texcell = SmsProvider::query()
                ->where('code', 'texcell')
                ->lockForUpdate()
                ->first();

            // 2) Yoksa driver=texcell olan kaydı bul
            if ($texcell === null) {
                $texcell = SmsProvider::query()
                    ->where('driver', SmsProviderDriver::Texcell->value)
                    ->orderByDesc('is_default')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();
            }

            // 3) Hâlâ yoksa ve code=texcell çakışması yoksa mock’u yerinde çevir
            if ($texcell === null) {
                $texcell = SmsProvider::query()
                    ->where(function ($query): void {
                        $query->where('code', 'mock')
                            ->orWhere('driver', SmsProviderDriver::Mock->value);
                    })
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();
            }

            if ($texcell === null) {
                $texcell = new SmsProvider;
            }

            // code’u texcell yapmadan önce başka satırda aynı code varsa onu kullan
            if ($texcell->exists && $texcell->code !== 'texcell') {
                $byCode = SmsProvider::query()
                    ->where('code', 'texcell')
                    ->whereKeyNot($texcell->id)
                    ->lockForUpdate()
                    ->first();

                if ($byCode !== null) {
                    $texcell = $byCode;
                }
            }

            $texcell->fill([
                'code' => 'texcell',
                'name' => 'Texcell EIMS',
                'driver' => SmsProviderDriver::Texcell->value,
                'config' => $config,
                'is_active' => true,
                'is_default' => true,
                'priority' => 1,
            ]);
            $texcell->save();

            SmsProvider::query()
                ->whereKeyNot($texcell->id)
                ->update([
                    'is_default' => false,
                    'is_active' => false,
                ]);

            return $texcell->fresh() ?? $texcell;
        });
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
                'HzCzaRmBLAUt9'
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
