<?php

namespace App\Repositories\Contracts;

use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * SMS mesaj repository sözleşmesi.
 *
 * @extends RepositoryInterface<SmsMessage>
 */
interface SmsMessageRepositoryInterface extends RepositoryInterface
{
    /**
     * Filtrelenmiş SMS listesini getirir.
     *
     * @return LengthAwarePaginator<int, SmsMessage>
     */
    public function paginateWithFilters(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Bugün gönderilen SMS sayısını döndürür.
     */
    public function countTodayForUser(?User $user = null): int;

    /**
     * Kuyrukta bekleyen SMS sayısını döndürür.
     */
    public function countQueuedForUser(?User $user = null): int;

    /**
     * Bugün kullanılan segment (SMS hakkı) toplamını döndürür.
     */
    public function sumSegmentsTodayForUser(?User $user = null): int;

    /**
     * @deprecated {@see sumSegmentsTodayForUser()}
     */
    public function sumCostTodayForUser(?User $user = null): float;
}
