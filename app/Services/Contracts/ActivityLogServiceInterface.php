<?php

namespace App\Services\Contracts;

use App\DTOs\ActivityLog\CreateActivityLogData;
use App\Models\ActivityLog;

/**
 * Aktivite log servis sözleşmesi.
 */
interface ActivityLogServiceInterface
{
    /**
     * Aktivite kaydı oluşturur.
     */
    public function record(CreateActivityLogData $data): ActivityLog;
}
