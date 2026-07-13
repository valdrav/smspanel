<?php

namespace App\Repositories\Eloquent;

use App\Models\UserSenderNumber;
use App\Repositories\Contracts\UserSenderNumberRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<UserSenderNumber>
 */
class UserSenderNumberRepository extends BaseRepository implements UserSenderNumberRepositoryInterface
{
    public function __construct(UserSenderNumber $model)
    {
        parent::__construct($model);
    }

    public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with('user');

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('sender_id', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderByDesc('is_default')->orderBy('sender_id')->paginate($perPage)->withQueryString();
    }

    public function getActiveForUser(int $userId): Collection
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('sender_id')
            ->get();
    }

    public function findByUserAndSenderId(int $userId, string $senderId): ?UserSenderNumber
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('sender_id', strtoupper($senderId))
            ->first();
    }

    public function clearDefaultForUser(int $userId): void
    {
        $this->model->newQuery()->where('user_id', $userId)->update(['is_default' => false]);
    }
}
