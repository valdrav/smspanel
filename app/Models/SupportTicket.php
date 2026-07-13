<?php

namespace App\Models;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'ticket_number', 'user_id', 'assigned_to', 'subject',
        'category', 'priority', 'status', 'closed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => TicketCategory::class,
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'closed_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return HasMany<SupportTicketMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class)->orderBy('created_at');
    }

    /**
     * @return HasMany<SupportTicketMessage, $this>
     */
    public function publicMessages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class)
            ->where('is_internal', false)
            ->orderBy('created_at');
    }

    /**
     * @return HasMany<SupportTicketStatusLog, $this>
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(SupportTicketStatusLog::class)->orderBy('created_at');
    }
}
