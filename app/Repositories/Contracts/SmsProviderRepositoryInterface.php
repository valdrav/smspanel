<?php

namespace App\Repositories\Contracts;

use App\Models\SmsProvider;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends RepositoryInterface<SmsProvider>
 */
interface SmsProviderRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code): ?SmsProvider;

    public function findDefaultActive(): ?SmsProvider;

    /**
     * @return LengthAwarePaginator<int, SmsProvider>
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * @return Collection<int, SmsProvider>
     */
    public function getActiveList(): Collection;

    public function clearDefaultFlag(): void;
}
