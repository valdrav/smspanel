<?php

namespace App\Models;

use App\Enums\SmsProviderDriver;
use Database\Factories\SmsProviderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * SMS sağlayıcı yapılandırma modeli.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property SmsProviderDriver $driver
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
            'driver' => SmsProviderDriver::class,
            'config' => 'encrypted:array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'priority' => 'integer',
            'last_balance' => 'decimal:4',
            'last_balance_checked_at' => 'datetime',
        ];
    }
}
