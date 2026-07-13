<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Package\PurchasePackageRequest;
use App\Models\SmsPackage;
use App\Services\SmsPackage\PackageOrderService;
use App\Services\SmsPackage\SmsPackageService;
use App\Services\Contracts\WalletServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PackageCatalogController extends Controller
{
    public function __construct(
        private readonly SmsPackageService $smsPackageService,
        private readonly PackageOrderService $packageOrderService,
        private readonly WalletServiceInterface $walletService,
    ) {}

    public function index(): View
    {
        $this->authorize('browseCatalog', SmsPackage::class);

        $user = auth()->user();

        return view('admin.packages.catalog', [
            'pageTitle' => 'SMS Paketleri',
            'packages' => $this->smsPackageService->listPublic(),
            'balance' => $this->walletService->getAvailableBalance($user),
            'myOrders' => $user->can('packages.purchase')
                ? $this->packageOrderService->listForUser($user, 8)
                : null,
        ]);
    }

    public function purchase(PurchasePackageRequest $request, SmsPackage $package): RedirectResponse
    {
        $this->packageOrderService->request(
            auth()->user(),
            $package,
            $request->validated('user_note'),
        );

        return back()->with('success', 'Satın alma talebiniz alındı. Onay sonrası SMS hakkınız yüklenecek.');
    }
}
