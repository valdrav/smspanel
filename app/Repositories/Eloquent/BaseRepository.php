<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent tabanlı temel repository sınıfı.
 *
 * @template TModel of Model
 *
 * @implements RepositoryInterface<TModel>
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * @param  TModel  $model
     */
    public function __construct(protected Model $model) {}

    /**
     * {@inheritDoc}
     */
    public function all(): Collection
    {
        return $this->model->newQuery()->get();
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->latest('id')->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $id): ?Model
    {
        return $this->model->newQuery()->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findByIdOrFail(int $id): Model
    {
        return $this->model->newQuery()->findOrFail($id);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Model
    {
        return $this->model->newQuery()->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(Model $model, array $data): Model
    {
        $model->update($data);

        return $model->fresh();
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }
}
