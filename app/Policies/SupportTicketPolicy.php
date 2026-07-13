<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;
use App\Support\UserScope;

class SupportTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return UserScope::isPlatformAdmin($user)
            || $user->can('tickets.view')
            || $user->can('tickets.create');
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        return UserScope::isPlatformAdmin($user) || $ticket->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('tickets.create');
    }

    public function update(User $user, SupportTicket $ticket): bool
    {
        return UserScope::isPlatformAdmin($user);
    }

    public function reply(User $user, SupportTicket $ticket): bool
    {
        return UserScope::isPlatformAdmin($user) || $ticket->user_id === $user->id;
    }
}
