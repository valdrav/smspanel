<?php

namespace App\Services\Sms;

use App\Enums\RoleName;
use App\Enums\SmsProviderDriver;
use App\Models\Organization;
use App\Models\SmsProvider;
use App\Models\User;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use App\Repositories\Contracts\SmsProviderRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Sms\DTOs\SmsBalanceResult;
use App\Sms\SmsProviderFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Texcell USD bakiyesini (Bro Per SMS) panel SMS adedine çevirip yazar.
 */
class TexcellBalanceSyncService
{
    public function __construct(
        private readonly SmsProviderRepositoryInterface $smsProviderRepository,
        private readonly SmsProviderFactory $smsProviderFactory,
        private readonly UserRepositoryInterface $userRepository,
        private readonly OrganizationRepositoryInterface $organizationRepository,
        private readonly TexcellCreditConverter $creditConverter,
    ) {}

    public function syncProvider(SmsProvider $provider, ?User $actingUser = null): SmsBalanceResult
    {
        if ($provider->driver !== SmsProviderDriver::Texcell) {
            return new SmsBalanceResult(success: false, errorMessage: 'Sağlayıcı Texcell değil.');
        }

        $instance = $this->smsProviderFactory->makeFromModel($provider);
        $result = $instance->getBalance();

        if (! $result->success) {
            Log::channel('daily')->warning('Texcell bakiye senkronu başarısız', [
                'error' => $result->errorMessage,
                'provider_id' => $provider->id,
            ]);

            return $result;
        }

        $usd = (float) ($result->rawUsd ?? $result->balance);
        $credits = $this->creditConverter->usdToCredits($usd);

        $this->smsProviderRepository->update($provider, [
            'last_balance' => $credits,
            'last_balance_checked_at' => now(),
        ]);

        Cache::put('texcell.last_balance_usd', $usd, now()->addMinutes(5));
        Cache::put('texcell.last_balance', (float) $credits, now()->addMinutes(5));
        Cache::put('texcell.usd_per_sms', $this->creditConverter->rate(), now()->addMinutes(5));

        if ($actingUser !== null) {
            $this->applyCreditsToUserWallet($actingUser, $credits);
        }

        $this->applyCreditsToPanelAdmins($credits);

        Log::channel('daily')->info('Texcell USD → SMS adet', [
            'usd' => $usd,
            'rate' => $this->creditConverter->rate(),
            'credits' => $credits,
        ]);

        return new SmsBalanceResult(
            success: true,
            balance: (float) $credits,
            currency: 'SMS',
            rawUsd: $usd,
        );
    }

    public function syncDefault(?User $actingUser = null): SmsBalanceResult
    {
        if (! app()->environment('testing')) {
            app(EnsureTexcellProvider::class)->ensure();
        }

        $provider = $this->resolveTexcellProvider();

        if ($provider === null) {
            return new SmsBalanceResult(success: false, errorMessage: 'Aktif Texcell sağlayıcısı yok.');
        }

        return $this->syncProvider($provider, $actingUser);
    }

    public function cachedUpstreamBalance(): ?float
    {
        $cached = Cache::get('texcell.last_balance');

        return is_numeric($cached) ? (float) $cached : null;
    }

    public function cachedUpstreamUsd(): ?float
    {
        $cached = Cache::get('texcell.last_balance_usd');

        return is_numeric($cached) ? (float) $cached : null;
    }

    private function resolveTexcellProvider(): ?SmsProvider
    {
        $provider = $this->smsProviderRepository->findDefaultActive();

        if ($provider !== null && $provider->driver === SmsProviderDriver::Texcell) {
            return $provider;
        }

        return SmsProvider::query()
            ->where('driver', SmsProviderDriver::Texcell->value)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->first();
    }

    private function applyCreditsToPanelAdmins(int $credits): void
    {
        $users = User::role([RoleName::SuperAdmin->value, RoleName::Admin->value])
            ->whereNull('organization_id')
            ->get();

        foreach ($users as $user) {
            $this->writePersonalBalance($user, $credits);
        }
    }

    private function applyCreditsToUserWallet(User $user, int $credits): void
    {
        if ($user->organization_id !== null) {
            $organization = $this->organizationRepository->findById($user->organization_id);
            if ($organization instanceof Organization) {
                $this->writeOrganizationBalance($organization, $credits);

                return;
            }
        }

        $this->writePersonalBalance($user, $credits);
    }

    private function writePersonalBalance(User $user, int $credits): void
    {
        DB::transaction(function () use ($user, $credits): void {
            $locked = $this->userRepository->findByIdOrFail($user->id);
            $before = (int) floor((float) $locked->sms_balance);

            if ($before === $credits) {
                return;
            }

            $this->userRepository->update($locked, ['sms_balance' => $credits]);

            Log::channel('daily')->info('Texcell → kişisel SMS hakkı', [
                'user_id' => $locked->id,
                'before' => $before,
                'after' => $credits,
            ]);
        });
    }

    private function writeOrganizationBalance(Organization $organization, int $credits): void
    {
        DB::transaction(function () use ($organization, $credits): void {
            $locked = $this->organizationRepository->findByIdOrFail($organization->id);
            $before = (int) floor((float) $locked->sms_balance);

            if ($before === $credits) {
                return;
            }

            $this->organizationRepository->update($locked, ['sms_balance' => $credits]);

            Log::channel('daily')->info('Texcell → organizasyon SMS hakkı', [
                'organization_id' => $locked->id,
                'before' => $before,
                'after' => $credits,
            ]);
        });
    }
}
