<?php

namespace App\Services\Contracts;

use App\DTOs\UserSenderNumber\CreateUserSenderNumberData;
use App\DTOs\UserSenderNumber\UpdateUserSenderNumberData;
use App\Models\User;
use App\Models\UserSenderNumber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserSenderNumberServiceInterface
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * @return Collection<int, UserSenderNumber>
     */
    public function getActiveForUser(User $user): Collection;

    public function create(CreateUserSenderNumberData $data): UserSenderNumber;

    public function update(UserSenderNumber $senderNumber, UpdateUserSenderNumberData $data): UserSenderNumber;

    public function delete(UserSenderNumber $senderNumber): void;

    public function resolveSenderId(User $user, ?string $requestedSenderId): string;
}
