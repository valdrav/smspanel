<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Sms\SendBulkSmsData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sms\SendBulkSmsRequest;
use App\Services\Contracts\SmsSendServiceInterface;
use Illuminate\Http\JsonResponse;

class BulkSmsController extends Controller
{
    public function __construct(private readonly SmsSendServiceInterface $smsSendService) {}

    public function send(SendBulkSmsRequest $request): JsonResponse
    {
        $messages = $this->smsSendService->sendBulk(
            auth()->user(),
            SendBulkSmsData::fromArray($request->validated()),
        );

        return response()->json([
            'success' => true,
            'message' => 'Toplu SMS kuyruğa alındı.',
            'data' => [
                'count' => $messages->count(),
                'batch_id' => $messages->first()?->batch_id,
            ],
        ], 201);
    }
}
