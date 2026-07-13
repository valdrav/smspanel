<?php

namespace App\Services\Contracts;

use App\DTOs\User\CreateUserData;
use App\DTOs\User\UpdateUserData;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Kullanıcı yönetim servis sözleşmesi.
 */
interface UserServiceInterface
{
    /**
     * Filtrelenmiş kullanıcı listesini getirir.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Yeni kullanıcı oluşturur.
     */
    public function create(CreateUserData $data): User;

    /**
     * Kullanıcı bilgilerini günceller.
     */
    public function update(User $user, UpdateUserData $data): User;

    /**
     * Kullanıcıyı siler.
     */
    public function delete(User $user): void;
}
