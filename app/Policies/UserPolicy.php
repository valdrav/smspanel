<?php

namespace App\Policies;

use App\Models\User;
use App\Support\UserScope;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return UserScope::isPlatformAdmin($user);
    }

    public function view(User $user, User $model): bool
    {
        return UserScope::isPlatformAdmin($user) || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return UserScope::isPlatformAdmin($user);
    }

    public function update(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return true;
        }

        return UserScope::isPlatformAdmin($user);
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return UserScope::isPlatformAdmin($user);
    }
}
