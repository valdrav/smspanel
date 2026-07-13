<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\User;
use App\Models\WalletTransaction;

class WalletTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('wallet.view');
    }

    public function view(User $user, WalletTransaction $transaction): bool
    {
        if ($user->hasRole(RoleName::SuperAdmin->value)) {
            return true;
        }

        return $transaction->user_id === $user->id;
    }

    public function credit(User $user): bool
    {
        return $user->can('wallet.credit');
    }
}
