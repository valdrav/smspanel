<?php

namespace App\Policies;

use App\Models\SmsMessage;
use App\Models\User;
use App\Support\UserScope;

/**
 * SMS mesaj yetkilendirme policy'si.
 */
class SmsMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sms.view');
    }

    public function view(User $user, SmsMessage $smsMessage): bool
    {
        if (! $user->can('sms.view')) {
            return false;
        }

        return UserScope::isPlatformAdmin($user) || $user->id === $smsMessage->user_id;
    }

    public function create(User $user): bool
    {
        return $user->can('sms.send');
    }
}

