<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActivityAction;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\Contracts\ActivityLogListingServiceInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Aktivite log controller'ı.
 */
class ActivityLogController extends Controller
{
    public function __construct(
        private readonly ActivityLogListingServiceInterface $activityLogListingService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ActivityLog::class);

        return view('admin.activity-logs.index', [
            'pageTitle' => 'Aktivite Logları',
            'logs' => $this->activityLogListingService->list(
                auth()->user(),
                filters: $request->only(['search', 'action', 'user_id']),
            ),
            'actions' => ActivityAction::cases(),
            'filters' => $request->only(['search', 'action', 'user_id']),
        ]);
    }
}
