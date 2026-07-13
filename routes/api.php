<?php

use App\Http\Controllers\Api\V1\BalanceController;
use App\Http\Controllers\Api\V1\BulkSmsController;
use App\Http\Controllers\Api\V1\CampaignController;
use App\Http\Controllers\Api\V1\SmsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api.token', 'throttle:api'])->prefix('v1')->group(function (): void {
    Route::get('balance', [BalanceController::class, 'show']);
    Route::post('sms/send', [SmsController::class, 'send'])->middleware('can:sms.send');
    Route::post('sms/bulk', [BulkSmsController::class, 'send'])->middleware('can:sms.send');
    Route::post('campaigns', [CampaignController::class, 'store'])->middleware('can:campaigns.create');
    Route::get('campaigns/{campaign}', [CampaignController::class, 'show'])->middleware('can:campaigns.view');
});

