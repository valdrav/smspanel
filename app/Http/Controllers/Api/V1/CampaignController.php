<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Models\SmsCampaign;
use App\Services\Sms\CampaignService;
use Illuminate\Http\JsonResponse;

class CampaignController extends Controller
{
    public function __construct(private readonly CampaignService $campaignService) {}

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $campaign = $this->campaignService->create(auth()->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Kampanya oluşturuldu.',
            'data' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status->value,
                'total_recipients' => $campaign->total_recipients,
                'scheduled_at' => $campaign->scheduled_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function show(SmsCampaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status->value,
                'total_recipients' => $campaign->total_recipients,
                'processed_count' => $campaign->processed_count,
                'success_count' => $campaign->success_count,
                'failed_count' => $campaign->failed_count,
                'progress_percent' => $campaign->progressPercent(),
                'scheduled_at' => $campaign->scheduled_at?->toIso8601String(),
                'started_at' => $campaign->started_at?->toIso8601String(),
                'completed_at' => $campaign->completed_at?->toIso8601String(),
            ],
        ]);
    }
}
