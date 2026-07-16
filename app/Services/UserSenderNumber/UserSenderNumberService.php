<?php

namespace App\Services\UserSenderNumber;

use App\DTOs\ActivityLog\CreateActivityLogData;
use App\DTOs\UserSenderNumber\CreateUserSenderNumberData;
use App\DTOs\UserSenderNumber\UpdateUserSenderNumberData;
use App\Enums\ActivityAction;
use App\Exceptions\BusinessException;
use App\Models\User;
use App\Models\UserSenderNumber;
use App\Repositories\Contracts\SmsProviderRepositoryInterface;
use App\Repositories\Contracts\UserSenderNumberRepositoryInterface;
use App\Services\Contracts\ActivityLogServiceInterface;
use App\Services\Contracts\UserSenderNumberServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserSenderNumberService implements UserSenderNumberServiceInterface
{
    public function __construct(
        private readonly UserSenderNumberRepositoryInterface $userSenderNumberRepository,
        private readonly SmsProviderRepositoryInterface $smsProviderRepository,
        private readonly ActivityLogServiceInterface $activityLogService,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->userSenderNumberRepository->paginateWithFilters($filters, $perPage);
    }

    public function getActiveForUser(User $user): Collection
    {
        return $this->userSenderNumberRepository->getActiveForUser($user->id);
    }

    public function create(CreateUserSenderNumberData $data): UserSenderNumber
    {
        if ($this->userSenderNumberRepository->findByUserAndSenderId($data->userId, $data->senderId)) {
            throw new BusinessException('Bu kullanıcı için gönderici numarası zaten tanımlı.');
        }

        return DB::transaction(function () use ($data): UserSenderNumber {
            if ($data->isDefault) {
                $this->userSenderNumberRepository->clearDefaultForUser($data->userId);
            }

            /** @var UserSenderNumber $senderNumber */
            $senderNumber = $this->userSenderNumberRepository->create([
                'user_id' => $data->userId,
                'sender_id' => $data->senderId,
                'label' => $data->label,
                'is_default' => $data->isDefault,
                'is_active' => $data->isActive,
            ]);

            $this->logAction(ActivityAction::Created, $senderNumber, 'Kullanıcı gönderici numarası oluşturuldu');

            return $senderNumber->load('user');
        });
    }

    public function update(UserSenderNumber $senderNumber, UpdateUserSenderNumberData $data): UserSenderNumber
    {
        $duplicate = $this->userSenderNumberRepository->findByUserAndSenderId($senderNumber->user_id, $data->senderId);

        if ($duplicate && $duplicate->id !== $senderNumber->id) {
            throw new BusinessException('Bu kullanıcı için gönderici numarası zaten tanımlı.');
        }

        return DB::transaction(function () use ($senderNumber, $data): UserSenderNumber {
            if ($data->isDefault) {
                $this->userSenderNumberRepository->clearDefaultForUser($senderNumber->user_id);
            }

            $updated = $this->userSenderNumberRepository->update($senderNumber, [
                'sender_id' => $data->senderId,
                'label' => $data->label,
                'is_default' => $data->isDefault,
                'is_active' => $data->isActive,
            ]);

            $this->logAction(ActivityAction::Updated, $updated, 'Kullanıcı gönderici numarası güncellendi');

            return $updated->load('user');
        });
    }

    public function delete(UserSenderNumber $senderNumber): void
    {
        DB::transaction(function () use ($senderNumber): void {
            $this->logAction(ActivityAction::Deleted, $senderNumber, 'Kullanıcı gönderici numarası silindi');
            $this->userSenderNumberRepository->delete($senderNumber);
        });
    }

    public function resolveSenderId(User $user, ?string $requestedSenderId): string
    {
        $assigned = $this->getActiveForUser($user);

        if ($assigned->isNotEmpty()) {
            $normalized = $requestedSenderId !== null && $requestedSenderId !== ''
                ? strtoupper($requestedSenderId)
                : null;

            if ($normalized !== null) {
                if (! $assigned->contains('sender_id', $normalized)) {
                    throw new BusinessException('Seçilen gönderici numarası kullanım yetkiniz bulunmuyor.');
                }

                return $normalized;
            }

            $default = $assigned->firstWhere('is_default', true) ?? $assigned->first();

            return $default->sender_id;
        }

        return $requestedSenderId
            ?? $user->organization?->sms_sender_id
            ?? $user->sms_sender_id
            ?? $this->defaultProviderSenderId()
            ?? config('sms.default_sender_id');
    }

    private function defaultProviderSenderId(): ?string
    {
        $provider = $this->smsProviderRepository->findDefaultActive();
        $config = $provider?->config ?? [];

        foreach (['sender_id', 'msgheader', 'sender'] as $key) {
            $sender = trim((string) ($config[$key] ?? ''));
            if ($sender !== '') {
                return $sender;
            }
        }

        return null;
    }

    private function logAction(ActivityAction $action, UserSenderNumber $senderNumber, string $description): void
    {
        $this->activityLogService->record(new CreateActivityLogData(
            action: $action,
            description: "{$description}: {$senderNumber->sender_id}",
            userId: Auth::id(),
            subjectType: UserSenderNumber::class,
            subjectId: $senderNumber->id,
            properties: ['user_id' => $senderNumber->user_id],
            ipAddress: request()->ip(),
            userAgent: request()->userAgent(),
        ));
    }
}
