<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Temel repository sözleşmesi.
 *
 * @template TModel of Model
 */
interface RepositoryInterface
{
    /**
     * Tüm kayıtları getirir.
     *
     * @return Collection<int, TModel>
     */
    public function all(): Collection;

    /**
     * Sayfalanmış kayıtları getirir.
     *
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * ID ile kayıt bulur.
     */
    public function findById(int $id): ?Model;

    /**
     * ID ile kayıt bulur veya exception fırlatır.
     */
    public function findByIdOrFail(int $id): Model;

    /**
     * Yeni kayıt oluşturur.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model;

    /**
     * Kaydı günceller.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model;

    /**
     * Kaydı siler.
     */
    public function delete(Model $model): bool;
}
