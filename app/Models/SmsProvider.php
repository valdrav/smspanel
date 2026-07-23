<?php

namespace App\Models;

use App\Enums\SmsProviderDriver;
use Database\Factories\SmsProviderFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * SMS sağlayıcı yapılandırma modeli.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property SmsProviderDriver|null $driver
 * @property array<string, mixed> $config
 * @property bool $is_active
 * @property bool $is_default
 * @property int $priority
 * @property float|null $last_balance
 */
class SmsProvider extends Model
{
    /** @use HasFactory<SmsProviderFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'driver',
        'config',
        'is_active',
        'is_default',
        'priority',
        'last_balance',
        'last_balance_checked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'config' => 'encrypted:array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'priority' => 'integer',
            'last_balance' => 'decimal:4',
            'last_balance_checked_at' => 'datetime',
        ];
    }

    /**
     * Bilinmeyen eski sürücü değerlerinde (örn. easysendsms) sayfa çökmesin.
     *
     * @return Attribute<SmsProviderDriver|null, string>
     */
    protected function driver(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value): ?SmsProviderDriver {
                if ($value instanceof SmsProviderDriver) {
                    return $value;
                }

                return SmsProviderDriver::tryFrom((string) $value);
            },
            set: function (mixed $value): string {
                if ($value instanceof SmsProviderDriver) {
                    return $value->value;
                }

                return (string) $value;
            },
        );
    }

    public function driverLabel(): string
    {
        return $this->driver?->label()
            ?? (string) ($this->attributes['driver'] ?? 'bilinmiyor');
    }

    public function driverValue(): string
    {
        return $this->driver?->value
            ?? (string) ($this->attributes['driver'] ?? '');
    }
}
