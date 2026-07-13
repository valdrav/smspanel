<?php

namespace App\Policies;

use App\Models\PackageOrder;
use App\Models\User;

class PackageOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('packages.manage') || $user->can('packages.purchase');
    }

    public function view(User $user, PackageOrder $order): bool
    {
        return $user->can('packages.manage') || $order->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('packages.purchase');
    }

    public function approve(User $user, PackageOrder $order): bool
    {
        return $user->can('packages.manage');
    }
}
