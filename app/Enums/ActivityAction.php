<?php

namespace App\Enums;

/**
 * Sistem aktivite log aksiyonları.
 */
enum ActivityAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Login = 'login';
    case Logout = 'logout';
    case LoginFailed = 'login_failed';
    case PasswordChanged = 'password_changed';
    case StatusChanged = 'status_changed';

    /**
     * Aksiyonun Türkçe etiketini döndürür.
     */
    public function label(): string
    {
        return match ($this) {
            self::Created => 'Oluşturuldu',
            self::Updated => 'Güncellendi',
            self::Deleted => 'Silindi',
            self::Login => 'Giriş Yapıldı',
            self::Logout => 'Çıkış Yapıldı',
            self::LoginFailed => 'Başarısız Giriş',
            self::PasswordChanged => 'Şifre Değiştirildi',
            self::StatusChanged => 'Durum Değiştirildi',
        };
    }
}
