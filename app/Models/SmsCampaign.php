<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsCampaign extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id', 'name', 'message', 'sender_id', 'status',
        'total_recipients', 'processed_count', 'success_count', 'failed_count',
        'chunk_size', 'chunk_delay_seconds', 'batch_id', 'scheduled_at',
        'started_at', 'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CampaignStatus::class,
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
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
     * @return HasMany<SmsCampaignRecipient, $this>
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(SmsCampaignRecipient::class);
    }

    public function progressPercent(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }

        return round(($this->processed_count / $this->total_recipients) * 100, 1);
    }
}
