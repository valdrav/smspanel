<?php

namespace App\Models;

use App\Enums\SmsMessageStatus;
use Database\Factories\SmsMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SMS mesaj modeli.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $batch_id
 * @property string $recipient
 * @property string $message
 * @property string|null $sender_id
 * @property string $provider
 * @property string|null $provider_message_id
 * @property SmsMessageStatus $status
 * @property int $segments
 * @property float $cost
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $delivered_at
 */
class SmsMessage extends Model
{
    /** @use HasFactory<SmsMessageFactory> */
    use HasFactory;

    /**
     * Toplu atanabilir alanlar.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'organization_id',
        'batch_id',
        'recipient',
        'message',
        'sender_id',
        'provider',
        'provider_message_id',
        'status',
        'segments',
        'cost',
        'error_message',
        'sent_at',
        'delivered_at',
    ];

    /**
     * Alan dönüşümleri.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SmsMessageStatus::class,
            'segments' => 'integer',
            'cost' => 'decimal:4',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * Mesajı gönderen kullanıcı.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
