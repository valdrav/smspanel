<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'API token gerekli.'], 401);
        }

        $user = User::query()->where('api_token', hash('sha256', $token))->first();

        if ($user === null || ! $user->isActive()) {
            return response()->json(['message' => 'Geçersiz API token.'], 401);
        }

        auth()->setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
