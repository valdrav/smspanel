<?php

namespace App\Policies;

use App\Models\SmsTemplate;
use App\Models\User;
use App\Support\UserScope;

class SmsTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('templates.view') || $user->can('templates.manage');
    }

    public function view(User $user, SmsTemplate $template): bool
    {
        return UserScope::isPlatformAdmin($user) || $template->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('templates.manage');
    }

    public function update(User $user, SmsTemplate $template): bool
    {
        return UserScope::isPlatformAdmin($user) || $template->user_id === $user->id;
    }

    public function delete(User $user, SmsTemplate $template): bool
    {
        return UserScope::isPlatformAdmin($user) || $template->user_id === $user->id;
    }
}
