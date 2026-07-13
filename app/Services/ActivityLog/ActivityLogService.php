<?php

namespace App\Services\ActivityLog;

use App\DTOs\ActivityLog\CreateActivityLogData;
use App\Models\ActivityLog;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use App\Services\Contracts\ActivityLogServiceInterface;

/**
 * Aktivite log servis implementasyonu.
 */
class ActivityLogService implements ActivityLogServiceInterface
{
    public function __construct(
        private readonly ActivityLogRepositoryInterface $activityLogRepository,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function record(CreateActivityLogData $data): ActivityLog
    {
        return $this->activityLogRepository->log($data);
    }
}
