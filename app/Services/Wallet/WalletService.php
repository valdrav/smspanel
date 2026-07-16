<?php

namespace App\Services\Wallet;

use App\DTOs\ActivityLog\CreateActivityLogData;
use App\DTOs\Wallet\CreditWalletData;
use App\Enums\ActivityAction;
use App\Enums\OrganizationStatus;
use App\Enums\WalletTransactionType;
use App\Exceptions\BusinessException;
use App\Models\Organization;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\WalletTransactionRepositoryInterface;
use App\Services\Contracts\ActivityLogServiceInterface;
use App\Services\Contracts\WalletServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Cüzdan işlem servis implementasyonu.
 */
class WalletService implements WalletServiceInterface
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizationRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly WalletTransactionRepositoryInterface $walletTransactionRepository,
        private readonly ActivityLogServiceInterface $activityLogService,
    ) {}

    /**
     * Kullanılabilir SMS hakkı (adet) döndürür.
     */
    public function getAvailableBalance(User $user): float
    {
        $lockedUser = $this->userRepository->findByIdOrFail($user->id);

        if ($lockedUser->organization_id !== null) {
            $organization = $this->organizationRepository->findById($lockedUser->organization_id);

            return $organization ? (float) $organization->sms_balance : 0;
        }

        return (float) $lockedUser->sms_balance;
    }

    /**
     * {@inheritDoc}
     */
    public function getPersonalBalance(User $user): float
    {
        $lockedUser = $this->userRepository->findByIdOrFail($user->id);

        return (float) $lockedUser->sms_balance;
    }

    /**
     * {@inheritDoc}
     */
    public function creditUser(User $user, float $amount, string $description, ?Model $reference = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new BusinessException('Geçersiz SMS adedi.');
        }

        return DB::transaction(function () use ($user, $amount, $description, $reference): WalletTransaction {
            $lockedUser = $this->userRepository->findByIdOrFail($user->id);
            $balanceBefore = (float) $lockedUser->sms_balance;
            $balanceAfter = $balanceBefore + $amount;

            $this->userRepository->update($lockedUser, ['sms_balance' => $balanceAfter]);

            return $this->createTransaction(
                organizationId: $lockedUser->organization_id,
                userId: $lockedUser->id,
                type: WalletTransactionType::Credit,
                amount: $amount,
                balanceBefore: $balanceBefore,
                balanceAfter: $balanceAfter,
                description: $description,
                reference: $reference,
            );
        });
    }

    /**
     * {@inheritDoc}
     */
    public function creditAvailableBalance(User $user, float $amount, string $description, ?Model $reference = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new BusinessException('Geçersiz SMS adedi.');
        }

        return DB::transaction(function () use ($user, $amount, $description, $reference): WalletTransaction {
            $lockedUser = $this->userRepository->findByIdOrFail($user->id);

            if ($lockedUser->organization_id !== null) {
                return $this->applyOrganizationTransaction(
                    organization: $this->organizationRepository->findByIdOrFail($lockedUser->organization_id),
                    user: $lockedUser,
                    type: WalletTransactionType::Credit,
                    amount: $amount,
                    description: $description,
                    reference: $reference,
                    subtract: false,
                );
            }

            return $this->creditUser($lockedUser, $amount, $description, $reference);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function debit(User $user, float $amount, string $description, ?Model $reference = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new BusinessException('Geçersiz SMS adedi.');
        }

        return DB::transaction(function () use ($user, $amount, $description, $reference): WalletTransaction {
            if ($user->organization_id !== null) {
                return $this->applyOrganizationTransaction(
                    organization: $this->organizationRepository->findByIdOrFail($user->organization_id),
                    user: $user,
                    type: WalletTransactionType::Debit,
                    amount: $amount,
                    description: $description,
                    reference: $reference,
                    subtract: true,
                );
            }

            $lockedUser = $this->userRepository->findByIdOrFail($user->id);
            $balanceBefore = (float) $lockedUser->sms_balance;

            if ($balanceBefore < $amount) {
                throw new BusinessException('Yetersiz SMS hakkı. Lütfen kredi yükleyin.');
            }

            $balanceAfter = $balanceBefore - $amount;
            $this->userRepository->update($lockedUser, ['sms_balance' => $balanceAfter]);

            return $this->createTransaction(
                organizationId: null,
                userId: $lockedUser->id,
                type: WalletTransactionType::Debit,
                amount: $amount,
                balanceBefore: $balanceBefore,
                balanceAfter: $balanceAfter,
                description: $description,
                reference: $reference,
            );
        });
    }

    /**
     * {@inheritDoc}
     */
    public function refund(User $user, float $amount, string $description, ?Model $reference = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new BusinessException('Geçersiz SMS adedi.');
        }

        return DB::transaction(function () use ($user, $amount, $description, $reference): WalletTransaction {
            if ($user->organization_id !== null) {
                return $this->applyOrganizationTransaction(
                    organization: $this->organizationRepository->findByIdOrFail($user->organization_id),
                    user: $user,
                    type: WalletTransactionType::Refund,
                    amount: $amount,
                    description: $description,
                    reference: $reference,
                    subtract: false,
                );
            }

            $lockedUser = $this->userRepository->findByIdOrFail($user->id);
            $balanceBefore = (float) $lockedUser->sms_balance;
            $balanceAfter = $balanceBefore + $amount;

            $this->userRepository->update($lockedUser, ['sms_balance' => $balanceAfter]);

            return $this->createTransaction(
                organizationId: null,
                userId: $lockedUser->id,
                type: WalletTransactionType::Refund,
                amount: $amount,
                balanceBefore: $balanceBefore,
                balanceAfter: $balanceAfter,
                description: $description,
                reference: $reference,
            );
        });
    }

    /**
     * {@inheritDoc}
     */
    public function creditOrganization(Organization $organization, CreditWalletData $data, User $performedBy): WalletTransaction
    {
        if ($data->amount <= 0) {
            throw new BusinessException('Yüklenecek SMS adedi sıfırdan büyük olmalıdır.');
        }

        return DB::transaction(function () use ($organization, $data, $performedBy): WalletTransaction {
            $transaction = $this->applyOrganizationTransaction(
                organization: $organization,
                user: $performedBy,
                type: WalletTransactionType::Credit,
                amount: $data->amount,
                description: $data->description,
                reference: $organization,
                subtract: false,
            );

            $this->activityLogService->record(new CreateActivityLogData(
                action: ActivityAction::Created,
                description: "SMS kredisi yüklendi: {$organization->name} (+{$data->amount} adet)",
                userId: Auth::id(),
                subjectType: Organization::class,
                subjectId: $organization->id,
                properties: ['amount' => $data->amount],
                ipAddress: request()->ip(),
                userAgent: request()->userAgent(),
            ));

            return $transaction;
        });
    }

    /**
     * Organizasyon bakiyesi üzerinde işlem uygular.
     */
    private function applyOrganizationTransaction(
        Organization $organization,
        User $user,
        WalletTransactionType $type,
        float $amount,
        string $description,
        ?Model $reference,
        bool $subtract,
    ): WalletTransaction {
        if (! $organization->isActive() && $subtract) {
            throw new BusinessException('Organizasyon aktif değil.');
        }

        $lockedOrg = $this->organizationRepository->findByIdOrFail($organization->id);
        $balanceBefore = (float) $lockedOrg->sms_balance;

        if ($subtract && $balanceBefore < $amount) {
            throw new BusinessException('Yetersiz SMS hakkı. Lütfen kredi yükleyin.');
        }

        $balanceAfter = $subtract ? $balanceBefore - $amount : $balanceBefore + $amount;

        $this->organizationRepository->update($lockedOrg, ['sms_balance' => $balanceAfter]);

        return $this->createTransaction(
            organizationId: $lockedOrg->id,
            userId: $user->id,
            type: $type,
            amount: $amount,
            balanceBefore: $balanceBefore,
            balanceAfter: $balanceAfter,
            description: $description,
            reference: $reference,
        );
    }

    /**
     * Cüzdan işlem kaydı oluşturur.
     */
    private function createTransaction(
        ?int $organizationId,
        ?int $userId,
        WalletTransactionType $type,
        float $amount,
        float $balanceBefore,
        float $balanceAfter,
        string $description,
        ?Model $reference,
    ): WalletTransaction {
        /** @var WalletTransaction $transaction */
        $transaction = $this->walletTransactionRepository->create([
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'type' => $type->value,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => $description,
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference?->getKey(),
        ]);

        return $transaction;
    }
}
