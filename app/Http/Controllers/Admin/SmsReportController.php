<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use App\Services\Contracts\SmsReportServiceInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * SMS raporlama controller'ı.
 */
class SmsReportController extends Controller
{
    public function __construct(
        private readonly SmsReportServiceInterface $smsReportService,
        private readonly OrganizationRepositoryInterface $organizationRepository,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('reports.view');

        $user = auth()->user();
        $filters = $request->only(['date_from', 'date_to', 'organization_id']);

        if (empty($filters['date_from'])) {
            $filters['date_from'] = now()->subDays(30)->format('Y-m-d');
        }

        if (empty($filters['date_to'])) {
            $filters['date_to'] = now()->format('Y-m-d');
        }

        return view('admin.reports.sms.index', [
            'pageTitle' => 'SMS Raporları',
            'summary' => $this->smsReportService->getSummary($user, $filters),
            'organizations' => $user->can('organizations.view')
                ? $this->organizationRepository->getActiveList()
                : collect(),
            'filters' => $filters,
        ]);
    }
}
