<?php

namespace App\Policies;

use App\Models\SmsPackage;
use App\Models\User;
use App\Support\UserScope;

class SmsPackagePolicy
{
    public function viewAny(User $user): bool
    {
        return UserScope::isPlatformAdmin($user);
    }

    public function browseCatalog(User $user): bool
    {
        return $user->can('packages.view') || $user->can('packages.purchase');
    }

    public function view(User $user, SmsPackage $package): bool
    {
        if (UserScope::isPlatformAdmin($user)) {
            return true;
        }

        return $user->can('packages.view') && $package->is_active && $package->is_public;
    }

    public function create(User $user): bool
    {
        return UserScope::isPlatformAdmin($user);
    }

    public function update(User $user, SmsPackage $package): bool
    {
        return UserScope::isPlatformAdmin($user);
    }

    public function delete(User $user, SmsPackage $package): bool
    {
        return UserScope::isPlatformAdmin($user);
    }
}
