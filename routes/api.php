<?php

use App\Http\Controllers\Api\V1\BalanceController;
use App\Http\Controllers\Api\V1\BulkSmsController;
use App\Http\Controllers\Api\V1\CampaignController;
use App\Http\Controllers\Api\V1\SmsController;
use App\Http\Controllers\Webhooks\TexcellReportWebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api.token', 'throttle:api'])->prefix('v1')->group(function (): void {
    Route::get('balance', [BalanceController::class, 'show']);
    Route::post('sms/send', [SmsController::class, 'send'])->middleware('can:sms.send');
    Route::post('sms/bulk', [BulkSmsController::class, 'send'])->middleware('can:sms.send');
    Route::post('campaigns', [CampaignController::class, 'store'])->middleware('can:campaigns.create');
    Route::get('campaigns/{campaign}', [CampaignController::class, 'show'])->middleware('can:campaigns.view');
});

/*
| Texcell EIMS DLR push (PUT). Token boşsa /api/webhooks/texcell/report da kabul edilir.
| Panelde push URL: https://YOUR_DOMAIN/api/webhooks/texcell/{TEXCELL_WEBHOOK_TOKEN}/report
*/
Route::match(['put', 'post'], 'webhooks/texcell/{token}/report', TexcellReportWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.texcell.report');

Route::match(['put', 'post'], 'webhooks/texcell/report', TexcellReportWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.texcell.report.plain');

