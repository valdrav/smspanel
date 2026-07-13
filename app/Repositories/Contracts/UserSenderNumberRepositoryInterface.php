<?php

namespace App\Repositories\Contracts;

use App\Models\UserSenderNumber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends RepositoryInterface<UserSenderNumber>
 */
interface UserSenderNumberRepositoryInterface extends RepositoryInterface
{
    public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * @return Collection<int, UserSenderNumber>
     */
    public function getActiveForUser(int $userId): Collection;

    public function findByUserAndSenderId(int $userId, string $senderId): ?UserSenderNumber;

    public function clearDefaultForUser(int $userId): void;
}
