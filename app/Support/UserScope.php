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
}
