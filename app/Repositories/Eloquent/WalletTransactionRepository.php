<?php

namespace App\Repositories\Eloquent;

use App\Enums\RoleName;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Repositories\Contracts\WalletTransactionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepository<WalletTransaction>
 */
class WalletTransactionRepository extends BaseRepository implements WalletTransactionRepositoryInterface
{
    public function __construct(WalletTransaction $model)
    {
        parent::__construct($model);
    }

    public function paginateWithFilters(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with(['organization', 'user']);

        if ($user->hasRole(RoleName::SuperAdmin->value)) {
            if (! empty($filters['user_id'])) {
                $query->where('user_id', (int) $filters['user_id']);
            }
        } else {
            $query->where('user_id', $user->id);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }
}
