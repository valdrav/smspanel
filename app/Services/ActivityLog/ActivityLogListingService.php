<?php

namespace App\Services\ActivityLog;

use App\Enums\RoleName;
use App\Models\User;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use App\Services\Contracts\ActivityLogListingServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ActivityLogListingService implements ActivityLogListingServiceInterface
{
    public function __construct(
        private readonly ActivityLogRepositoryInterface $activityLogRepository,
    ) {}

    public function list(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        if (! $user->hasRole(RoleName::SuperAdmin->value)) {
            abort(403);
        }

        return $this->activityLogRepository->paginateWithFilters($filters, $perPage);
    }
}
