<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Kullanıcı repository sözleşmesi.
 *
 * @extends RepositoryInterface<User>
 */
interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * E-posta ile kullanıcı bulur.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Filtrelenmiş kullanıcı listesini getirir.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Rol ile kullanıcıları getirir.
     *
     * @return Collection<int, User>
     */
    public function getByRole(string $role): Collection;
}
