<?php

namespace App\Services\User;

use App\DTOs\ActivityLog\CreateActivityLogData;
use App\DTOs\User\CreateUserData;
use App\DTOs\User\UpdateUserData;
use App\Enums\ActivityAction;
use App\Events\User\UserCreated;
use App\Events\User\UserDeleted;
use App\Events\User\UserUpdated;
use App\Exceptions\BusinessException;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\ActivityLogServiceInterface;
use App\Services\Contracts\UserServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Kullanıcı yönetim servis implementasyonu.
 */
class UserService implements UserServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly ActivityLogServiceInterface $activityLogService,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->paginateWithFilters($filters, $perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function create(CreateUserData $data): User
    {
        if ($this->userRepository->findByEmail($data->email)) {
            throw new BusinessException('Bu e-posta adresi zaten kayıtlı.');
        }

        return DB::transaction(function () use ($data): User {
            /** @var User $user */
            $user = $this->userRepository->create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password,
                'phone' => $data->phone,
                'status' => $data->status->value,
            ]);

            if (! empty($data->roles)) {
                $user->syncRoles($data->roles);
            }

            $this->activityLogService->record(new CreateActivityLogData(
                action: ActivityAction::Created,
                description: "Kullanıcı oluşturuldu: {$user->email}",
                userId: Auth::id(),
                subjectType: User::class,
                subjectId: $user->id,
                properties: ['roles' => $data->roles],
                ipAddress: request()->ip(),
                userAgent: request()->userAgent(),
            ));

            event(new UserCreated($user));

            return $user->load('roles');
        });
    }

    /**
     * {@inheritDoc}
     */
    public function update(User $user, UpdateUserData $data): User
    {
        $existingUser = $this->userRepository->findByEmail($data->email);

        if ($existingUser && $existingUser->id !== $user->id) {
            throw new BusinessException('Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.');
        }

        return DB::transaction(function () use ($user, $data): User {
            $updateData = [
                'name' => $data->name,
                'email' => $data->email,
                'phone' => $data->phone,
                'status' => $data->status->value,
            ];

            if ($data->password !== null) {
                $updateData['password'] = $data->password;
            }

            $updatedUser = $this->userRepository->update($user, $updateData);

            if ($data->roles !== null) {
                $updatedUser->syncRoles($data->roles);
            }

            $this->activityLogService->record(new CreateActivityLogData(
                action: ActivityAction::Updated,
                description: "Kullanıcı güncellendi: {$updatedUser->email}",
                userId: Auth::id(),
                subjectType: User::class,
                subjectId: $updatedUser->id,
                ipAddress: request()->ip(),
                userAgent: request()->userAgent(),
            ));

            event(new UserUpdated($updatedUser));

            return $updatedUser->load('roles');
        });
    }

    /**
     * {@inheritDoc}
     */
    public function delete(User $user): void
    {
        if (Auth::id() === $user->id) {
            throw new BusinessException('Kendi hesabınızı silemezsiniz.');
        }

        DB::transaction(function () use ($user): void {
            $email = $user->email;

            $this->activityLogService->record(new CreateActivityLogData(
                action: ActivityAction::Deleted,
                description: "Kullanıcı silindi: {$email}",
                userId: Auth::id(),
                subjectType: User::class,
                subjectId: $user->id,
                ipAddress: request()->ip(),
                userAgent: request()->userAgent(),
            ));

            event(new UserDeleted($user));

            $this->userRepository->delete($user);
        });
    }
}
