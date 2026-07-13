<?php

namespace App\Services\Contracts;

use App\Models\User;

interface SmsReportServiceInterface
{
    /**
     * SMS rapor özetini döndürür.
     *
     * @return array{
     *     total_count: int,
     *     delivered_count: int,
     *     failed_count: int,
     *     queued_count: int,
     *     total_segments_used: int,
     *     total_segments: int,
     *     by_status: array<string, int>,
     *     by_day: list<array{date: string, count: int, segments: int}>,
     *     top_recipients: list<array{recipient: string, count: int}>
     * }
     */
    public function getSummary(User $user, array $filters = []): array;
}
