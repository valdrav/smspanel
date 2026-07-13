<?php

namespace App\Exceptions;

/**
 * Yetkilendirme hataları için exception sınıfı.
 */
class AuthorizationException extends BusinessException
{
    public function __construct(string $message = 'Bu işlem için yetkiniz bulunmamaktadır.')
    {
        parent::__construct($message, 403);
    }
}
