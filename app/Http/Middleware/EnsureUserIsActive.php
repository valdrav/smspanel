<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Exceptions\BusinessException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kullanıcının aktif olup olmadığını kontrol eden middleware.
 */
class EnsureUserIsActive
{
    /**
     * İsteği işler.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status !== UserStatus::Active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw new BusinessException('Hesabınız aktif değil. Lütfen yönetici ile iletişime geçin.');
        }

        return $next($request);
    }
}
