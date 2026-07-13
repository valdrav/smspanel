<?php

namespace App\Policies;

use App\Models\SmsCampaign;
use App\Models\User;
use App\Support\UserScope;

class SmsCampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('campaigns.view') || $user->can('campaigns.create');
    }

    public function view(User $user, SmsCampaign $campaign): bool
    {
        return UserScope::isPlatformAdmin($user) || $campaign->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('campaigns.create');
    }

    public function cancel(User $user, SmsCampaign $campaign): bool
    {
        return UserScope::isPlatformAdmin($user) || $campaign->user_id === $user->id;
    }
}
