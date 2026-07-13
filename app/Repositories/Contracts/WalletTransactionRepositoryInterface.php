<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends RepositoryInterface<WalletTransaction>
 */
interface WalletTransactionRepositoryInterface extends RepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, WalletTransaction>
     */
    public function paginateWithFilters(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator;
}
