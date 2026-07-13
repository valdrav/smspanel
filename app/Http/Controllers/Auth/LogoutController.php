<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Http\RedirectResponse;

/**
 * Kullanıcı çıkış controller'ı.
 */
class LogoutController extends Controller
{
    public function __construct(
        private readonly AuthServiceInterface $authService,
    ) {}

    /**
     * Çıkış işlemini gerçekleştirir.
     */
    public function logout(): RedirectResponse
    {
        $this->authService->logout();

        return redirect()->route('login')->with('success', 'Başarıyla çıkış yaptınız.');
    }
}
