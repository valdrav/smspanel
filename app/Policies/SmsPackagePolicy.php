<?php

namespace App\Policies;

use App\Models\SmsPackage;
use App\Models\User;

class SmsPackagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('packages.manage');
    }

    public function browseCatalog(User $user): bool
    {
        return $user->can('packages.view') || $user->can('packages.purchase');
    }

    public function view(User $user, SmsPackage $package): bool
    {
        if ($user->can('packages.manage')) {
            return true;
        }

        return $user->can('packages.view') && $package->is_active && $package->is_public;
    }

    public function create(User $user): bool
    {
        return $user->can('packages.manage');
    }

    public function update(User $user, SmsPackage $package): bool
    {
        return $user->can('packages.manage');
    }

    public function delete(User $user, SmsPackage $package): bool
    {
        return $user->can('packages.manage');
    }
}
