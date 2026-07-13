<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\Organization\CreateOrganizationData;
use App\DTOs\Organization\UpdateOrganizationData;
use App\DTOs\Wallet\CreditWalletData;
use App\Enums\OrganizationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\CreditOrganizationRequest;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Services\Contracts\OrganizationServiceInterface;
use App\Services\Contracts\WalletServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Organizasyon yönetimi controller'ı.
 */
class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationServiceInterface $organizationService,
        private readonly WalletServiceInterface $walletService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Organization::class);

        return view('admin.organizations.index', [
            'pageTitle' => 'Organizasyonlar',
            'organizations' => $this->organizationService->list(
                filters: $request->only(['search', 'status']),
            ),
            'statuses' => OrganizationStatus::cases(),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Organization::class);

        return view('admin.organizations.create', [
            'pageTitle' => 'Yeni Organizasyon',
            'statuses' => OrganizationStatus::cases(),
        ]);
    }

    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $this->organizationService->create(CreateOrganizationData::fromArray($request->validated()));

        return redirect()->route('admin.organizations.index')->with('success', 'Organizasyon oluşturuldu.');
    }

    public function show(Organization $organization): View
    {
        $this->authorize('view', $organization);

        $organization->load(['users', 'walletTransactions' => fn ($q) => $q->latest()->limit(10)]);

        return view('admin.organizations.show', [
            'pageTitle' => 'Organizasyon Detayı',
            'organization' => $organization,
        ]);
    }

    public function edit(Organization $organization): View
    {
        $this->authorize('update', $organization);

        return view('admin.organizations.edit', [
            'pageTitle' => 'Organizasyon Düzenle',
            'organization' => $organization,
            'statuses' => OrganizationStatus::cases(),
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $this->organizationService->update($organization, UpdateOrganizationData::fromArray($request->validated()));

        return redirect()->route('admin.organizations.index')->with('success', 'Organizasyon güncellendi.');
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        $this->authorize('delete', $organization);

        $this->organizationService->delete($organization);

        return redirect()->route('admin.organizations.index')->with('success', 'Organizasyon silindi.');
    }

    public function credit(CreditOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $this->authorize('credit', $organization);

        $this->walletService->creditOrganization(
            $organization,
            CreditWalletData::fromArray($request->validated()),
            auth()->user(),
        );

        return back()->with('success', 'SMS kredisi başarıyla yüklendi.');
    }
}
