<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsMessage;
use App\Services\Contracts\SmsHistoryServiceInterface;
use App\Support\UserScope;
use Illuminate\View\View;

/**
 * Yönetim paneli dashboard controller'ı.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly SmsHistoryServiceInterface $smsHistoryService,
    ) {}

    /**
     * Dashboard sayfasını gösterir.
     */
    public function index(): View
    {
        $user = auth()->user();
        $stats = $this->smsHistoryService->getDashboardStats($user);

        $recentMessages = SmsMessage::query()
            ->when(
                ! UserScope::isPlatformAdmin($user),
                fn ($query) => $query->where('user_id', $user->id)
            )
            ->latest('id')
            ->limit(5)
            ->get();

        return view('admin.dashboard.index', [
            'pageTitle' => 'Kontrol Paneli',
            'stats' => $stats,
            'recentMessages' => $recentMessages,
        ]);
    }
}
