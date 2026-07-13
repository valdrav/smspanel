<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Sms\SendSmsData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sms\SendSmsRequest;
use App\Services\Contracts\SmsSendServiceInterface;
use Illuminate\Http\JsonResponse;

class SmsController extends Controller
{
    public function __construct(
        private readonly SmsSendServiceInterface $smsSendService,
    ) {}

    public function send(SendSmsRequest $request): JsonResponse
    {
        $message = $this->smsSendService->send(
            auth()->user(),
            SendSmsData::fromArray($request->validated()),
        );

        return response()->json([
            'success' => true,
            'message' => 'SMS kuyruğa alındı.',
            'data' => [
                'id' => $message->id,
                'recipient' => $message->recipient,
                'segments' => $message->segments,
                'status' => $message->status->value,
            ],
        ], 201);
    }
}
