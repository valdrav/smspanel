<?php

namespace App\Repositories\Eloquent;

use App\DTOs\ActivityLog\CreateActivityLogData;
use App\Models\ActivityLog;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Aktivite log Eloquent repository implementasyonu.
 *
 * @extends BaseRepository<ActivityLog>
 */
class ActivityLogRepository extends BaseRepository implements ActivityLogRepositoryInterface
{
    public function __construct(ActivityLog $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function log(CreateActivityLogData $data): ActivityLog
    {
        /** @var ActivityLog $log */
        $log = $this->create([
            'user_id' => $data->userId,
            'action' => $data->action->value,
            'description' => $data->description,
            'subject_type' => $data->subjectType,
            'subject_id' => $data->subjectId,
            'properties' => $data->properties,
            'ip_address' => $data->ipAddress,
            'user_agent' => $data->userAgent,
        ]);

        return $log;
    }

    /**
     * {@inheritDoc}
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with('user');

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where('description', 'like', "%{$search}%");
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }
}
