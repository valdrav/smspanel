<?php

namespace App\Repositories\Contracts;

use App\DTOs\ActivityLog\CreateActivityLogData;
use App\Models\ActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Aktivite log repository sözleşmesi.
 *
 * @extends RepositoryInterface<ActivityLog>
 */
interface ActivityLogRepositoryInterface extends RepositoryInterface
{
    /**
     * Aktivite log kaydı oluşturur.
     */
    public function log(CreateActivityLogData $data): ActivityLog;

    /**
     * Filtrelenmiş log listesini getirir.
     *
     * @return LengthAwarePaginator<int, ActivityLog>
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 20): LengthAwarePaginator;
}
