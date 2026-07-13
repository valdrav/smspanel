<?php

namespace App\Models;

use App\Enums\OrganizationStatus;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Organizasyon (müşteri firma) modeli.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $tax_number
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property OrganizationStatus $status
 * @property float $sms_balance SMS hakkı (adet)
 * @property string|null $sms_sender_id
 * @property string|null $notes
 */
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'tax_number',
        'email',
        'phone',
        'address',
        'status',
        'sms_balance',
        'sms_sender_id',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrganizationStatus::class,
            'sms_balance' => 'decimal:4',
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<SmsMessage, $this>
     */
    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class);
    }

    /**
     * @return HasMany<WalletTransaction, $this>
     */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Organizasyonun aktif olup olmadığını kontrol eder.
     */
    public function isActive(): bool
    {
        return $this->status === OrganizationStatus::Active;
    }
}
