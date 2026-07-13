<?php

namespace App\Exceptions;

/**
 * Kimlik doğrulama hataları için exception sınıfı.
 */
class AuthenticationException extends BusinessException
{
    public function __construct(string $message = 'Kimlik doğrulama başarısız.')
    {
        parent::__construct($message, 401);
    }
}
