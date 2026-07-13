<?php

namespace App\Repositories\Eloquent;

use App\Models\SmsProvider;
use App\Repositories\Contracts\SmsProviderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<SmsProvider>
 */
class SmsProviderRepository extends BaseRepository implements SmsProviderRepositoryInterface
{
    public function __construct(SmsProvider $model)
    {
        parent::__construct($model);
    }

    public function findByCode(string $code): ?SmsProvider
    {
        return $this->model->newQuery()->where('code', $code)->first();
    }

    public function findDefaultActive(): ?SmsProvider
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->where('is_default', true)
            ->orderBy('priority')
            ->first();
    }

    public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderBy('priority')->paginate($perPage)->withQueryString();
    }

    public function getActiveList(): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();
    }

    public function clearDefaultFlag(): void
    {
        $this->model->newQuery()->where('is_default', true)->update(['is_default' => false]);
    }
}
