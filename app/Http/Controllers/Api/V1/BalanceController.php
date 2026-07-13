<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Contracts\WalletServiceInterface;
use Illuminate\Http\JsonResponse;

class BalanceController extends Controller
{
    public function __construct(
        private readonly WalletServiceInterface $walletService,
    ) {}

    public function show(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'balance' => (int) $this->walletService->getAvailableBalance($user),
            'unit' => 'sms',
        ]);
    }
}
