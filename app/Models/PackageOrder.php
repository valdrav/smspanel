<?php

namespace App\Models;

use App\Enums\PackageOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageOrder extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id', 'sms_package_id', 'status', 'user_note',
        'admin_note', 'processed_by', 'processed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PackageOrderStatus::class,
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<SmsPackage, $this>
     */
    public function smsPackage(): BelongsTo
    {
        return $this->belongsTo(SmsPackage::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
