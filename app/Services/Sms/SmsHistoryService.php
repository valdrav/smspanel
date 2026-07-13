<?php

namespace App\Services\Sms;

use App\Models\User;
use App\Repositories\Contracts\SmsMessageRepositoryInterface;
use App\Services\Contracts\SmsHistoryServiceInterface;
use App\Services\Contracts\WalletServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * SMS geçmişi servis implementasyonu.
 */
class SmsHistoryService implements SmsHistoryServiceInterface
{
    public function __construct(
        private readonly SmsMessageRepositoryInterface $smsMessageRepository,
        private readonly WalletServiceInterface $walletService,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function list(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->smsMessageRepository->paginateWithFilters($user, $filters, $perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function getDashboardStats(User $user): array
    {
        return [
            'today_count' => $this->smsMessageRepository->countTodayForUser($user),
            'queued_count' => $this->smsMessageRepository->countQueuedForUser($user),
            'today_segments' => $this->smsMessageRepository->sumSegmentsTodayForUser($user),
            'balance' => $this->walletService->getAvailableBalance($user),
        ];
    }
}
