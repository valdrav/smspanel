<?php

namespace App\Repositories\Contracts;

use App\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends RepositoryInterface<Organization>
 */
interface OrganizationRepositoryInterface extends RepositoryInterface
{
    public function findBySlug(string $slug): ?Organization;

    /**
     * @return LengthAwarePaginator<int, Organization>
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * @return Collection<int, Organization>
     */
    public function getActiveList(): Collection;
}
