<?php

namespace App\Services\SmsProvider;

use App\DTOs\ActivityLog\CreateActivityLogData;
use App\DTOs\SmsProvider\CreateSmsProviderData;
use App\DTOs\SmsProvider\UpdateSmsProviderData;
use App\Enums\ActivityAction;
use App\Enums\SmsProviderDriver;
use App\Exceptions\BusinessException;
use App\Models\SmsProvider;
use App\Repositories\Contracts\SmsProviderRepositoryInterface;
use App\Services\Contracts\ActivityLogServiceInterface;
use App\Services\Contracts\SmsProviderServiceInterface;
use App\Services\Sms\TexcellBalanceSyncService;
use App\Sms\DTOs\SmsBalanceResult;
use App\Sms\SmsProviderFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * SMS sağlayıcı yönetim servisi.
 */
class SmsProviderService implements SmsProviderServiceInterface
{
    public function __construct(
        private readonly SmsProviderRepositoryInterface $smsProviderRepository,
        private readonly SmsProviderFactory $smsProviderFactory,
        private readonly ActivityLogServiceInterface $activityLogService,
        private readonly TexcellBalanceSyncService $texcellBalanceSyncService,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->smsProviderRepository->paginateWithFilters($filters, $perPage);
    }

    public function create(CreateSmsProviderData $data): SmsProvider
    {
        if ($this->smsProviderRepository->findByCode($data->code)) {
            throw new BusinessException('Bu sağlayıcı kodu zaten kullanılıyor.');
        }

        return DB::transaction(function () use ($data): SmsProvider {
            if ($data->isDefault) {
                $this->smsProviderRepository->clearDefaultFlag();
            }

            /** @var SmsProvider $provider */
            $provider = $this->smsProviderRepository->create([
                'code' => $data->code,
                'name' => $data->name,
                'driver' => $data->driver->value,
                'config' => $data->config,
                'is_active' => $data->isDefault ? true : $data->isActive,
                'is_default' => $data->isDefault,
                'priority' => $data->priority,
            ]);

            $this->logAction(ActivityAction::Created, $provider, 'SMS sağlayıcı oluşturuldu');

            return $provider;
        });
    }

    public function update(SmsProvider $provider, UpdateSmsProviderData $data): SmsProvider
    {
        return DB::transaction(function () use ($provider, $data): SmsProvider {
            if ($data->isDefault) {
                $this->smsProviderRepository->clearDefaultFlag();
            }

            $config = $data->config;

            if (
                $data->driver->value === 'texcell'
                && trim((string) ($config['password'] ?? '')) === ''
                && ! empty($provider->config['password'])
            ) {
                $config['password'] = $provider->config['password'];
            }

            $updated = $this->smsProviderRepository->update($provider, [
                'name' => $data->name,
                'driver' => $data->driver->value,
                'config' => $config,
                'is_active' => $data->isDefault ? true : $data->isActive,
                'is_default' => $data->isDefault,
                'priority' => $data->priority,
            ]);

            $this->logAction(ActivityAction::Updated, $updated, 'SMS sağlayıcı güncellendi');

            return $updated;
        });
    }

    public function delete(SmsProvider $provider): void
    {
        if ($provider->is_default) {
            throw new BusinessException('Varsayılan sağlayıcı silinemez.');
        }

        DB::transaction(function () use ($provider): void {
            $this->logAction(ActivityAction::Deleted, $provider, 'SMS sağlayıcı silindi');
            $this->smsProviderRepository->delete($provider);
        });
    }

    public function testBalance(SmsProvider $provider): SmsBalanceResult
    {
        if ($provider->driver === null) {
            return new SmsBalanceResult(
                success: false,
                errorMessage: 'Bu sağlayıcının sürücüsü artık desteklenmiyor. Kaydı silip Texcell ekleyin.',
            );
        }

        if (
            $provider->driver === SmsProviderDriver::Texcell
            && filter_var(config('sms.texcell.sync_balance_to_admin', true), FILTER_VALIDATE_BOOL)
        ) {
            return $this->texcellBalanceSyncService->syncProvider($provider);
        }

        $instance = $this->smsProviderFactory->makeFromModel($provider);
        $result = $instance->getBalance();

        if ($result->success) {
            $this->smsProviderRepository->update($provider, [
                'last_balance' => $result->balance,
                'last_balance_checked_at' => now(),
            ]);
        }

        return $result;
    }

    private function logAction(ActivityAction $action, SmsProvider $provider, string $description): void
    {
        $this->activityLogService->record(new CreateActivityLogData(
            action: $action,
            description: "{$description}: {$provider->name}",
            userId: Auth::id(),
            subjectType: SmsProvider::class,
            subjectId: $provider->id,
            ipAddress: request()->ip(),
            userAgent: request()->userAgent(),
        ));
    }
}
