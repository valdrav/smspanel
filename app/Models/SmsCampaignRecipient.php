<?php

namespace App\Models;

use App\Enums\CampaignRecipientStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsCampaignRecipient extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'sms_campaign_id', 'phone', 'name', 'status',
        'sms_message_id', 'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['status' => CampaignRecipientStatus::class];
    }

    /**
     * @return BelongsTo<SmsCampaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SmsCampaign::class, 'sms_campaign_id');
    }

    /**
     * @return BelongsTo<SmsMessage, $this>
     */
    public function smsMessage(): BelongsTo
    {
        return $this->belongsTo(SmsMessage::class);
    }
}
