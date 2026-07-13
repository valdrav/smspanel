<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SmsPackage\StoreSmsPackageRequest;
use App\Http\Requests\SmsPackage\UpdateSmsPackageRequest;
use App\Models\SmsPackage;
use App\Services\SmsPackage\SmsPackageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmsPackageController extends Controller
{
    public function __construct(private readonly SmsPackageService $smsPackageService) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SmsPackage::class);

        return view('admin.packages.index', [
            'pageTitle' => 'SMS Paketleri',
            'packages' => $this->smsPackageService->listAdmin($request->only(['is_public'])),
            'canManage' => auth()->user()->can('packages.manage'),
            'filters' => $request->only(['is_public']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', SmsPackage::class);

        return view('admin.packages.create', ['pageTitle' => 'Yeni SMS Paketi']);
    }

    public function store(StoreSmsPackageRequest $request): RedirectResponse
    {
        $this->smsPackageService->create($request->validated());

        return redirect()->route('admin.packages.index')->with('success', 'Paket oluşturuldu.');
    }

    public function edit(SmsPackage $package): View
    {
        $this->authorize('update', $package);

        return view('admin.packages.edit', [
            'pageTitle' => 'Paket Düzenle',
            'package' => $package,
        ]);
    }

    public function update(UpdateSmsPackageRequest $request, SmsPackage $package): RedirectResponse
    {
        $this->smsPackageService->update($package, $request->validated());

        return redirect()->route('admin.packages.index')->with('success', 'Paket güncellendi.');
    }

    public function destroy(SmsPackage $package): RedirectResponse
    {
        $this->authorize('delete', $package);
        $this->smsPackageService->delete($package);

        return redirect()->route('admin.packages.index')->with('success', 'Paket silindi.');
    }
}
