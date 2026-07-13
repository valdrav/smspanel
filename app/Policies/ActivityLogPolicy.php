<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RoleName::SuperAdmin->value);
    }

    public function view(User $user, ActivityLog $activityLog): bool
    {
        return $user->hasRole(RoleName::SuperAdmin->value);
    }
}
