<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('giris', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('giris', [LoginController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.submit');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('cikis', [LogoutController::class, 'logout'])->name('logout');

    Route::prefix('admin')
        ->name('admin.')
        ->group(base_path('routes/admin.php'));
});
