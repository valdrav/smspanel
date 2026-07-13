<?php

namespace App\Models;

use Database\Factories\UserSenderNumberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kullanıcıya tanımlı SMS gönderici numarası/başlığı.
 *
 * @property int $id
 * @property int $user_id
 * @property string $sender_id
 * @property string|null $label
 * @property bool $is_default
 * @property bool $is_active
 */
class UserSenderNumber extends Model
{
    /** @use HasFactory<UserSenderNumberFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'sender_id',
        'label',
        'is_default',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
