<?php

namespace App\Services\Sms;

use App\Enums\SmsMessageStatus;
use App\Models\User;
use App\Repositories\Contracts\SmsMessageRepositoryInterface;
use App\Services\Contracts\SmsReportServiceInterface;
use App\Support\UserScope;
use Illuminate\Support\Facades\DB;

/**
 * SMS raporlama servisi.
 */
class SmsReportService implements SmsReportServiceInterface
{
    public function __construct(
        private readonly SmsMessageRepositoryInterface $smsMessageRepository,
    ) {}

    public function getSummary(User $user, array $filters = []): array
    {
        $query = DB::table('sms_messages');

        if (! UserScope::isPlatformAdmin($user)) {
            $query->where('user_id', $user->id);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['organization_id']) && UserScope::isPlatformAdmin($user)) {
            $query->where('organization_id', (int) $filters['organization_id']);
        }

        $baseQuery = clone $query;

        $totalSegmentsUsed = (int) (clone $baseQuery)->sum('segments');

        $byStatus = (clone $baseQuery)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        $totalCount = (clone $baseQuery)->count();

        $byDay = (clone $baseQuery)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'), DB::raw('SUM(segments) as segments'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->limit(30)
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'count' => (int) $row->count,
                'segments' => (int) $row->segments,
            ])
            ->all();

        $topRecipients = (clone $baseQuery)
            ->select('recipient', DB::raw('COUNT(*) as count'))
            ->groupBy('recipient')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($row) => ['recipient' => $row->recipient, 'count' => (int) $row->count])
            ->all();

        return [
            'total_count' => $totalCount,
            'delivered_count' => (int) ($byStatus[SmsMessageStatus::Delivered->value] ?? 0),
            'failed_count' => (int) ($byStatus[SmsMessageStatus::Failed->value] ?? 0),
            'queued_count' => (int) (($byStatus[SmsMessageStatus::Queued->value] ?? 0) + ($byStatus[SmsMessageStatus::Pending->value] ?? 0)),
            'total_segments_used' => $totalSegmentsUsed,
            'total_segments' => $totalSegmentsUsed,
            'by_status' => $byStatus,
            'by_day' => $byDay,
            'top_recipients' => $topRecipients,
        ];
    }
}
