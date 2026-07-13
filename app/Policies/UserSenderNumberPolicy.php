<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserSenderNumber;
use App\Support\UserScope;

class UserSenderNumberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sender-numbers.view') || $user->can('sender-numbers.manage');
    }

    public function view(User $user, UserSenderNumber $senderNumber): bool
    {
        return UserScope::isPlatformAdmin($user) || $user->id === $senderNumber->user_id;
    }

    public function create(User $user): bool
    {
        return UserScope::isPlatformAdmin($user);
    }

    public function update(User $user, UserSenderNumber $senderNumber): bool
    {
        return UserScope::isPlatformAdmin($user) || $user->id === $senderNumber->user_id;
    }

    public function delete(User $user, UserSenderNumber $senderNumber): bool
    {
        return UserScope::isPlatformAdmin($user) || $user->id === $senderNumber->user_id;
    }
}
