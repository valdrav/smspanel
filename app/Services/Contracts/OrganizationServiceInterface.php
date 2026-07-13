<?php

namespace App\Services\Contracts;

use App\DTOs\Organization\CreateOrganizationData;
use App\DTOs\Organization\UpdateOrganizationData;
use App\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrganizationServiceInterface
{
    /**
     * @return LengthAwarePaginator<int, Organization>
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function create(CreateOrganizationData $data): Organization;

    public function update(Organization $organization, UpdateOrganizationData $data): Organization;

    public function delete(Organization $organization): void;
}
