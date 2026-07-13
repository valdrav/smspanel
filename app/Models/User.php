<?php

namespace App\Models;

use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Kullanıcı modeli.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property int|null $organization_id
 * @property UserStatus $status
 * @property float $sms_balance SMS hakkı (adet)
 * @property string|null $sms_sender_id
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Toplu atanabilir alanlar.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'name',
        'email',
        'phone',
        'password',
        'status',
        'sms_balance',
        'sms_sender_id',
        'last_login_at',
    ];

    /**
     * Gizli alanlar.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
    ];

    /**
     * Alan dönüşümleri.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'sms_balance' => 'decimal:4',
        ];
    }

    /**
     * Kullanıcının organizasyonu.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Kullanıcının SMS mesajları.
     *
     * @return HasMany<SmsMessage, $this>
     */
    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class);
    }

    /**
     * Kullanıcının aktivite logları.
     *
     * @return HasMany<ActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Kullanıcıya tanımlı gönderici numaraları.
     *
     * @return HasMany<UserSenderNumber, $this>
     */
    public function senderNumbers(): HasMany
    {
        return $this->hasMany(UserSenderNumber::class);
    }

    /**
     * Kullanıcının aktif olup olmadığını kontrol eder.
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }
}
