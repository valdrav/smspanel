<?php

namespace App\Services\Sms;

use App\Enums\RoleName;
use App\Enums\SmsProviderDriver;
use App\Models\SmsProvider;
use App\Models\User;
use App\Repositories\Contracts\SmsProviderRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Sms\DTOs\SmsBalanceResult;
use App\Sms\SmsProviderFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Texcell /getbalance sonucunu ana kullanıcının SMS hakkına yansıtır.
 */
class TexcellBalanceSyncService
{
    public function __construct(
        private readonly SmsProviderRepositoryInterface $smsProviderRepository,
        private readonly SmsProviderFactory $smsProviderFactory,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function syncProvider(SmsProvider $provider): SmsBalanceResult
    {
        if ($provider->driver !== SmsProviderDriver::Texcell) {
            return new SmsBalanceResult(success: false, errorMessage: 'Sağlayıcı Texcell değil.');
        }

        $instance = $this->smsProviderFactory->makeFromModel($provider);
        $result = $instance->getBalance();

        if (! $result->success) {
            return $result;
        }

        $this->smsProviderRepository->update($provider, [
            'last_balance' => $result->balance,
            'last_balance_checked_at' => now(),
        ]);

        $this->applyBalanceToMainUsers((float) $result->balance);

        return $result;
    }

    /**
     * Varsayılan Texcell sağlayıcısından bakiye çeker ve ana kullanıcıya işler.
     */
    public function syncDefault(?User $actingUser = null): SmsBalanceResult
    {
        $provider = $this->smsProviderRepository->findDefaultActive();

        if ($provider === null || $provider->driver !== SmsProviderDriver::Texcell) {
            $provider = SmsProvider::query()
                ->where('driver', SmsProviderDriver::Texcell->value)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->first();
        }

        if ($provider === null) {
            return new SmsBalanceResult(success: false, errorMessage: 'Aktif Texcell sağlayıcısı yok.');
        }

        $result = $this->syncProvider($provider);

        // İşlemi yapan süper admin organizasyonsuz ise ona da yaz (zaten apply içinde).
        if ($result->success && $actingUser !== null) {
            $this->applyToUserIfEligible($actingUser, (float) $result->balance);
        }

        return $result;
    }

    private function applyBalanceToMainUsers(float $balance): void
    {
        $credits = max(0, (int) floor($balance));

        $users = User::query()
            ->role(RoleName::SuperAdmin->value)
            ->whereNull('organization_id')
            ->get();

        if ($users->isEmpty()) {
            Log::channel('daily')->warning('Texcell bakiye senkronu: süper admin bulunamadı', [
                'balance' => $credits,
            ]);

            return;
        }

        foreach ($users as $user) {
            $this->applyToUserIfEligible($user, (float) $credits);
        }
    }

    private function applyToUserIfEligible(User $user, float $balance): void
    {
        if ($user->organization_id !== null) {
            return;
        }

        if (! method_exists($user, 'hasRole') || ! $user->hasRole(RoleName::SuperAdmin->value)) {
            return;
        }

        $credits = max(0, (int) floor($balance));

        DB::transaction(function () use ($user, $credits): void {
            $locked = $this->userRepository->findByIdOrFail($user->id);
            $before = (float) $locked->sms_balance;

            if ((int) $before === $credits) {
                return;
            }

            $this->userRepository->update($locked, ['sms_balance' => $credits]);

            Log::channel('daily')->info('Texcell bakiye → SMS hakkı senkronu', [
                'user_id' => $locked->id,
                'before' => $before,
                'after' => $credits,
            ]);
        });
    }
}
