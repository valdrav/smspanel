<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;
use App\Support\UserScope;

class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('contacts.view') || $user->can('contacts.manage');
    }

    public function view(User $user, Contact $contact): bool
    {
        return UserScope::isPlatformAdmin($user) || $contact->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('contacts.manage');
    }

    public function update(User $user, Contact $contact): bool
    {
        return UserScope::isPlatformAdmin($user) || $contact->user_id === $user->id;
    }

    public function delete(User $user, Contact $contact): bool
    {
        return UserScope::isPlatformAdmin($user) || $contact->user_id === $user->id;
    }
}
