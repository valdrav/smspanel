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

        $hour = (int) now()->format('H');
        $greeting = match (true) {
            $hour < 12 => 'Günaydın',
            $hour < 18 => 'İyi günler',
            default => 'İyi akşamlar',
        };

        return view('admin.dashboard.index', [
            'pageTitle' => 'Kontrol Paneli',
            'greeting' => $greeting,
            'stats' => $stats,
            'recentMessages' => $recentMessages,
        ]);
    }
}
