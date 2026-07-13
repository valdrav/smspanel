<?php

namespace App\Http\Controllers\Auth;

use App\DTOs\Auth\LoginData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Kullanıcı giriş controller'ı.
 */
class LoginController extends Controller
{
    public function __construct(
        private readonly AuthServiceInterface $authService,
    ) {}

    /**
     * Giriş formunu gösterir.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Giriş işlemini gerçekleştirir.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $this->authService->login(
            LoginData::fromArray($request->validated()),
            $request->ip() ?? '0.0.0.0',
            $request->userAgent(),
        );

        return redirect()->intended(route('admin.dashboard'));
    }
}
