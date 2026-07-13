<?php

namespace App\Services\Contracts;

use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * SMS geçmişi servis sözleşmesi.
 */
interface SmsHistoryServiceInterface
{
    /**
     * Filtrelenmiş SMS geçmişini getirir.
     *
     * @return LengthAwarePaginator<int, SmsMessage>
     */
    public function list(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Dashboard istatistiklerini döndürür.
     *
     * @return array{today_count: int, queued_count: int, today_segments: int, balance: float}
     */
    public function getDashboardStats(User $user): array;
}
