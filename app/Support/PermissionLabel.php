<?php

namespace App\Support;

/**
 * Yetki adları için Türkçe etiketler.
 */
final class PermissionLabel
{
    /**
     * @return array<string, string>
     */
    public static function groups(): array
    {
        return [
            'users' => 'Kullanıcılar',
            'organizations' => 'Organizasyonlar',
            'wallet' => 'Cüzdan',
            'providers' => 'SMS Sağlayıcıları',
            'sender-numbers' => 'Gönderici Numaraları',
            'activity' => 'Aktivite Logları',
            'dashboard' => 'Kontrol Paneli',
            'sms' => 'SMS',
            'reports' => 'Raporlar',
            'settings' => 'Sistem Ayarları',
            'roles' => 'Roller & Yetkiler',
            'packages' => 'SMS Paketleri',
            'tickets' => 'Destek Sistemi',
            'contacts' => 'Rehber',
            'campaigns' => 'SMS Kampanyaları',
            'templates' => 'SMS Şablonları',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function permissions(): array
    {
        return [
            'users.view' => 'Kullanıcıları görüntüle',
            'users.create' => 'Kullanıcı oluştur',
            'users.update' => 'Kullanıcı düzenle',
            'users.delete' => 'Kullanıcı sil',
            'organizations.view' => 'Organizasyonları görüntüle',
            'organizations.create' => 'Organizasyon oluştur',
            'organizations.update' => 'Organizasyon düzenle',
            'organizations.delete' => 'Organizasyon sil',
            'wallet.view' => 'Cüzdan işlemlerini görüntüle',
            'wallet.credit' => 'Bakiye yükle',
            'providers.view' => 'SMS sağlayıcılarını görüntüle',
            'providers.manage' => 'SMS sağlayıcılarını yönet',
            'sender-numbers.view' => 'Gönderici numaralarını görüntüle',
            'sender-numbers.manage' => 'Gönderici numaralarını yönet',
            'activity.view' => 'Aktivite loglarını görüntüle',
            'dashboard.view' => 'Kontrol paneline eriş',
            'sms.send' => 'SMS gönder',
            'sms.view' => 'SMS geçmişini görüntüle',
            'reports.view' => 'Raporları görüntüle',
            'settings.manage' => 'Sistem ayarlarını yönet',
            'roles.view' => 'Rolleri görüntüle',
            'roles.manage' => 'Rolleri yönet',
            'packages.manage' => 'SMS paketlerini yönet',
            'packages.view' => 'SMS paketlerini görüntüle',
            'packages.purchase' => 'SMS paketi satın alma talebi gönder',
            'tickets.view' => 'Destek taleplerini görüntüle',
            'tickets.create' => 'Destek talebi oluştur',
            'tickets.manage' => 'Tüm destek taleplerini yönet (Süper Yönetici)',
            'contacts.view' => 'Rehberi görüntüle',
            'contacts.manage' => 'Rehberi yönet',
            'campaigns.view' => 'SMS kampanyalarını görüntüle',
            'campaigns.create' => 'SMS kampanyası oluştur',
            'templates.view' => 'SMS şablonlarını görüntüle',
            'templates.manage' => 'SMS şablonlarını yönet',
        ];
    }

    public static function group(string $key): string
    {
        return self::groups()[$key] ?? ucfirst(str_replace('-', ' ', $key));
    }

    public static function permission(string $name): string
    {
        return self::permissions()[$name] ?? $name;
    }
}
