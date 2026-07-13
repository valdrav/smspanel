<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PackageOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\PackageOrder;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\SmsPackage\PackageOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PackageOrderController extends Controller
{
    public function __construct(
        private readonly PackageOrderService $packageOrderService,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PackageOrder::class);
        $user = auth()->user();

        return view('admin.package-orders.index', [
            'pageTitle' => $user->can('packages.manage') ? 'Paket Siparişleri' : 'Satın Alma Taleplerim',
            'orders' => $user->can('packages.manage')
                ? $this->packageOrderService->listAdmin($request->only(['status', 'user_id']))
                : $this->packageOrderService->listForUser($user),
            'filters' => $request->only(['status', 'user_id']),
            'canManage' => $user->can('packages.manage'),
            'users' => $user->can('packages.manage') ? $this->userRepository->all() : collect(),
            'statuses' => PackageOrderStatus::cases(),
        ]);
    }

    public function approve(Request $request, PackageOrder $order): RedirectResponse
    {
        $this->authorize('approve', $order);
        $this->packageOrderService->approve($order, auth()->user(), $request->input('admin_note'));

        return back()->with('success', 'Talep onaylandı ve SMS hakkı yüklendi.');
    }

    public function reject(Request $request, PackageOrder $order): RedirectResponse
    {
        $this->authorize('approve', $order);
        $this->packageOrderService->reject($order, auth()->user(), $request->input('admin_note'));

        return back()->with('success', 'Talep reddedildi.');
    }
}
