<?php

namespace App\Jobs\Sms;

use App\Services\Sms\CampaignService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessCampaignChunkJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $campaignId)
    {
        $this->onQueue(config('sms.queue', 'sms'));
    }

    public function handle(CampaignService $campaignService): void
    {
        $campaignService->processChunk($this->campaignId);
    }
}
