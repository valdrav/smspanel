<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\PackageCatalogController;
use App\Http\Controllers\Admin\PackageOrderController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SmsCampaignController;
use App\Http\Controllers\Admin\SmsHistoryController;
use App\Http\Controllers\Admin\SmsPackageController;
use App\Http\Controllers\Admin\SmsProviderController;
use App\Http\Controllers\Admin\SmsReportController;
use App\Http\Controllers\Admin\SmsSendController;
use App\Http\Controllers\Admin\SmsTemplateController;
use App\Http\Controllers\Admin\SupportTicketController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserSenderNumberController;
use App\Http\Controllers\Admin\WalletTransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::resource('users', UserController::class);
Route::resource('organizations', OrganizationController::class);
Route::post('organizations/{organization}/credit', [OrganizationController::class, 'credit'])->name('organizations.credit');

Route::get('wallet', [WalletTransactionController::class, 'index'])->name('wallet.index');
Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

Route::resource('packages', SmsPackageController::class)->except(['show']);
Route::get('paketler', [PackageCatalogController::class, 'index'])->name('packages.catalog');
Route::post('paketler/{package}/satin-al', [PackageCatalogController::class, 'purchase'])->name('packages.purchase');
Route::get('paket-siparisleri', [PackageOrderController::class, 'index'])->name('package-orders.index');
Route::post('paket-siparisleri/{order}/onayla', [PackageOrderController::class, 'approve'])->name('package-orders.approve');
Route::post('paket-siparisleri/{order}/reddet', [PackageOrderController::class, 'reject'])->name('package-orders.reject');

Route::get('destek/ek/{attachment}', [SupportTicketController::class, 'downloadAttachment'])->name('support-tickets.attachments.download');
Route::resource('destek', SupportTicketController::class)
    ->parameters(['destek' => 'ticket'])
    ->names('support-tickets')
    ->except(['edit', 'update', 'destroy']);
Route::put('destek/{ticket}/guncelle', [SupportTicketController::class, 'update'])->name('support-tickets.update');
Route::post('destek/{ticket}/yanit', [SupportTicketController::class, 'reply'])->name('support-tickets.reply');

Route::get('ayarlar', [SettingController::class, 'index'])->name('settings.index');
Route::put('ayarlar', [SettingController::class, 'update'])->name('settings.update');
Route::post('ayarlar/api-token', [SettingController::class, 'regenerateApiToken'])->name('settings.api-token');

Route::get('roller', [RolePermissionController::class, 'index'])->name('roles.index');
Route::get('roller/{role}/duzenle', [RolePermissionController::class, 'edit'])->name('roles.edit');
Route::put('roller/{role}', [RolePermissionController::class, 'update'])->name('roles.update');

Route::get('raporlar/sms', [SmsReportController::class, 'index'])->name('reports.sms');

Route::resource('rehber', ContactController::class)
    ->parameters(['rehber' => 'contact'])
    ->names('contacts');
Route::post('rehber/ice-aktar', [ContactController::class, 'import'])->name('contacts.import');
Route::get('rehber/disa-aktar', [ContactController::class, 'export'])->name('contacts.export');

Route::resource('kampanyalar', SmsCampaignController::class)
    ->parameters(['kampanyalar' => 'campaign'])
    ->names('campaigns')
    ->only(['index', 'create', 'store', 'show']);
Route::post('kampanyalar/{campaign}/iptal', [SmsCampaignController::class, 'cancel'])->name('campaigns.cancel');

Route::resource('sms-sablonlari', SmsTemplateController::class)
    ->parameters(['sms-sablonlari' => 'smsTemplate'])
    ->names('sms-templates')
    ->except(['show']);

Route::resource('user-sender-numbers', UserSenderNumberController::class)->except(['show']);

Route::resource('sms-providers', SmsProviderController::class)->except(['show']);
Route::post('sms-providers/{sms_provider}/test-balance', [SmsProviderController::class, 'testBalance'])->name('sms-providers.test-balance');

Route::prefix('sms')->name('sms.')->group(function (): void {
    Route::post('onizleme', [SmsSendController::class, 'preview'])->name('send.preview');
    Route::get('gonder', [SmsSendController::class, 'create'])->name('send.create');
    Route::post('gonder', [SmsSendController::class, 'store'])->name('send.store');
    Route::post('toplu-gonder', [SmsSendController::class, 'storeBulk'])->name('send.bulk');

    Route::get('gecmis', [SmsHistoryController::class, 'index'])->name('history.index');
    Route::get('gecmis/disa-aktar', [SmsHistoryController::class, 'export'])->name('history.export');
    Route::get('gecmis/{smsMessage}', [SmsHistoryController::class, 'show'])->name('history.show');
});
