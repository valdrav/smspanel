<?php

namespace App\Services\Contracts;

use App\DTOs\Auth\LoginData;
use App\Models\User;

/**
 * Kimlik doğrulama servis sözleşmesi.
 */
interface AuthServiceInterface
{
    /**
     * Kullanıcı girişi yapar.
     */
    public function login(LoginData $data, string $ipAddress, ?string $userAgent): User;

    /**
     * Kullanıcı çıkışı yapar.
     */
    public function logout(): void;
}
