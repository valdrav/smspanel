<?php

namespace App\Services\Organization;

use App\DTOs\ActivityLog\CreateActivityLogData;
use App\DTOs\Organization\CreateOrganizationData;
use App\DTOs\Organization\UpdateOrganizationData;
use App\DTOs\Wallet\CreditWalletData;
use App\Enums\ActivityAction;
use App\Exceptions\BusinessException;
use App\Models\Organization;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use App\Services\Contracts\ActivityLogServiceInterface;
use App\Services\Contracts\OrganizationServiceInterface;
use App\Services\Contracts\WalletServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Organizasyon yönetim servis implementasyonu.
 */
class OrganizationService implements OrganizationServiceInterface
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizationRepository,
        private readonly ActivityLogServiceInterface $activityLogService,
        private readonly WalletServiceInterface $walletService,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->organizationRepository->paginateWithFilters($filters, $perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function create(CreateOrganizationData $data): Organization
    {
        $slug = $this->generateUniqueSlug($data->name);

        return DB::transaction(function () use ($data, $slug): Organization {
            /** @var Organization $organization */
            $organization = $this->organizationRepository->create([
                'name' => $data->name,
                'slug' => $slug,
                'tax_number' => $data->taxNumber,
                'email' => $data->email,
                'phone' => $data->phone,
                'address' => $data->address,
                'status' => $data->status->value,
                'sms_balance' => 0,
                'sms_sender_id' => $data->smsSenderId,
                'notes' => $data->notes,
            ]);

            if ($data->initialBalance > 0) {
                $performedBy = Auth::user();

                if ($performedBy === null) {
                    throw new BusinessException('Bakiye yüklemek için oturum açmış kullanıcı gerekli.');
                }

                $this->walletService->creditOrganization(
                    $organization,
                    new CreditWalletData($data->initialBalance, 'Başlangıç bakiyesi'),
                    $performedBy,
                );
            }

            $this->activityLogService->record(new CreateActivityLogData(
                action: ActivityAction::Created,
                description: "Organizasyon oluşturuldu: {$organization->name}",
                userId: Auth::id(),
                subjectType: Organization::class,
                subjectId: $organization->id,
                ipAddress: request()->ip(),
                userAgent: request()->userAgent(),
            ));

            return $organization->fresh();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function update(Organization $organization, UpdateOrganizationData $data): Organization
    {
        return DB::transaction(function () use ($organization, $data): Organization {
            $updated = $this->organizationRepository->update($organization, [
                'name' => $data->name,
                'tax_number' => $data->taxNumber,
                'email' => $data->email,
                'phone' => $data->phone,
                'address' => $data->address,
                'status' => $data->status->value,
                'sms_sender_id' => $data->smsSenderId,
                'notes' => $data->notes,
            ]);

            $this->activityLogService->record(new CreateActivityLogData(
                action: ActivityAction::Updated,
                description: "Organizasyon güncellendi: {$updated->name}",
                userId: Auth::id(),
                subjectType: Organization::class,
                subjectId: $updated->id,
                ipAddress: request()->ip(),
                userAgent: request()->userAgent(),
            ));

            return $updated;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Organization $organization): void
    {
        if ($organization->users()->exists()) {
            throw new BusinessException('Kullanıcısı olan organizasyon silinemez.');
        }

        DB::transaction(function () use ($organization): void {
            $name = $organization->name;

            $this->activityLogService->record(new CreateActivityLogData(
                action: ActivityAction::Deleted,
                description: "Organizasyon silindi: {$name}",
                userId: Auth::id(),
                subjectType: Organization::class,
                subjectId: $organization->id,
                ipAddress: request()->ip(),
                userAgent: request()->userAgent(),
            ));

            $this->organizationRepository->delete($organization);
        });
    }

    /**
     * Benzersiz slug üretir.
     */
    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->organizationRepository->findBySlug($slug) !== null) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
