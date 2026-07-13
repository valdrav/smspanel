<?php

namespace App\Policies;

use App\Models\SmsProvider;
use App\Models\User;

/**
 * SMS sağlayıcı policy'si.
 */
class SmsProviderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('providers.view');
    }

    public function view(User $user, SmsProvider $provider): bool
    {
        return $user->can('providers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('providers.manage');
    }

    public function update(User $user, SmsProvider $provider): bool
    {
        return $user->can('providers.manage');
    }

    public function delete(User $user, SmsProvider $provider): bool
    {
        return $user->can('providers.manage');
    }
}
