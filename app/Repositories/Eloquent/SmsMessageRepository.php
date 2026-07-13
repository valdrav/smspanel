<?php

namespace App\Repositories\Eloquent;

use App\Models\SmsMessage;
use App\Models\User;
use App\Repositories\Contracts\SmsMessageRepositoryInterface;
use App\Support\UserScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * SMS mesaj Eloquent repository implementasyonu.
 *
 * @extends BaseRepository<SmsMessage>
 */
class SmsMessageRepository extends BaseRepository implements SmsMessageRepositoryInterface
{
    public function __construct(SmsMessage $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function paginateWithFilters(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with('user');

        if (! UserScope::isPlatformAdmin($user)) {
            $this->applyUserScope($query, $user);
        }

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('recipient', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['user_id']) && UserScope::isPlatformAdmin($user)) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    /**
     * {@inheritDoc}
     */
    public function countTodayForUser(?User $user = null): int
    {
        $query = $this->model->newQuery()->whereDate('created_at', today());

        if ($user && ! UserScope::isPlatformAdmin($user)) {
            $this->applyUserScope($query, $user);
        }

        return $query->count();
    }

    /**
     * {@inheritDoc}
     */
    public function countQueuedForUser(?User $user = null): int
    {
        $query = $this->model->newQuery()->whereIn('status', [
            \App\Enums\SmsMessageStatus::Pending->value,
            \App\Enums\SmsMessageStatus::Queued->value,
        ]);

        if ($user && ! UserScope::isPlatformAdmin($user)) {
            $this->applyUserScope($query, $user);
        }

        return $query->count();
    }

    /**
     * {@inheritDoc}
     */
    public function sumSegmentsTodayForUser(?User $user = null): int
    {
        $query = $this->model->newQuery()->whereDate('created_at', today());

        if ($user && ! UserScope::isPlatformAdmin($user)) {
            $this->applyUserScope($query, $user);
        }

        return (int) $query->sum('segments');
    }

    /**
     * @deprecated {@see sumSegmentsTodayForUser()}
     */
    public function sumCostTodayForUser(?User $user = null): float
    {
        return (float) $this->sumSegmentsTodayForUser($user);
    }

    /**
     * Kullanıcıya özel kapsam — yalnızca kendi kayıtları.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<SmsMessage>  $query
     */
    private function applyUserScope($query, User $user): void
    {
        $query->where('user_id', $user->id);
    }
}

