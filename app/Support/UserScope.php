<?php

namespace App\Support;

use App\Enums\RoleName;
use App\Models\User;

final class UserScope
{
    /**
     * Platform (SaaS) yöneticisi — tüm kullanıcı verilerine erişebilir.
     */
    public static function isPlatformAdmin(User $user): bool
    {
        return $user->hasRole(RoleName::SuperAdmin->value);
    }

    /**
     * Texcell hesap bakiyesini görebilen platform operatörü (organizasyonsuz yönetici).
     */
    public static function isPlatformOperator(User $user): bool
    {
        if ($user->organization_id !== null) {
            return false;
        }

        return $user->hasRole([RoleName::SuperAdmin->value, RoleName::Admin->value]);
    }
}
