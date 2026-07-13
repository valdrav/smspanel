<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Organization;
use App\Models\User;

/**
 * Organizasyon yetkilendirme policy'si.
 */
class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('organizations.view');
    }

    public function view(User $user, Organization $organization): bool
    {
        if ($user->can('organizations.view')) {
            return true;
        }

        return $user->organization_id === $organization->id;
    }

    public function create(User $user): bool
    {
        return $user->can('organizations.create');
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->can('organizations.update');
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->can('organizations.delete');
    }

    public function credit(User $user, Organization $organization): bool
    {
        return $user->can('wallet.credit') && $user->hasAnyRole([
            RoleName::SuperAdmin->value,
            RoleName::Admin->value,
        ]);
    }
}
