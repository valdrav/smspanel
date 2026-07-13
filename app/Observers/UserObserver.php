<?php

namespace App\Observers;

use App\DTOs\ActivityLog\CreateActivityLogData;
use App\Enums\ActivityAction;
use App\Models\User;
use App\Services\Contracts\ActivityLogServiceInterface;
use Illuminate\Support\Facades\Auth;

/**
 * Kullanıcı model observer'ı.
 */
class UserObserver
{
    public function __construct(
        private readonly ActivityLogServiceInterface $activityLogService,
    ) {}

    /**
     * Kullanıcı durumu değiştiğinde log kaydı oluşturur.
     */
    public function updated(User $user): void
    {
        if (! $user->wasChanged('status')) {
            return;
        }

        $this->activityLogService->record(new CreateActivityLogData(
            action: ActivityAction::StatusChanged,
            description: "Kullanıcı durumu değiştirildi: {$user->email} -> {$user->status->label()}",
            userId: Auth::id(),
            subjectType: User::class,
            subjectId: $user->id,
            properties: [
                'old_status' => $user->getOriginal('status'),
                'new_status' => $user->status->value,
            ],
            ipAddress: request()->ip(),
            userAgent: request()->userAgent(),
        ));
    }
}
