<?php

namespace App\Services\Contracts;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ActivityLogListingServiceInterface
{
    /**
     * @return LengthAwarePaginator<int, ActivityLog>
     */
    public function list(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator;
}
