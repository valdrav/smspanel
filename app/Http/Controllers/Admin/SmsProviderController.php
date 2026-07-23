<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\SmsProvider\CreateSmsProviderData;
use App\DTOs\SmsProvider\UpdateSmsProviderData;
use App\Enums\SmsProviderDriver;
use App\Http\Controllers\Controller;
use App\Http\Requests\SmsProvider\StoreSmsProviderRequest;
use App\Http\Requests\SmsProvider\UpdateSmsProviderRequest;
use App\Models\SmsProvider;
use App\Services\Contracts\SmsProviderServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * SMS sağlayıcı yönetimi controller'ı.
 */
class SmsProviderController extends Controller
{
    public function __construct(
        private readonly SmsProviderServiceInterface $smsProviderService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SmsProvider::class);

        return view('admin.sms-providers.index', [
            'pageTitle' => 'SMS Sağlayıcıları',
            'providers' => $this->smsProviderService->list($request->only(['search', 'is_active'])),
            'filters' => $request->only(['search', 'is_active']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', SmsProvider::class);

        return view('admin.sms-providers.create', [
            'pageTitle' => 'Yeni SMS Sağlayıcı',
            'drivers' => $this->availableDrivers(),
        ]);
    }

    public function store(StoreSmsProviderRequest $request): RedirectResponse
    {
        $this->smsProviderService->create(CreateSmsProviderData::fromArray($request->validated()));

        return redirect()->route('admin.sms-providers.index')->with('success', 'SMS sağlayıcı oluşturuldu.');
    }

    public function edit(SmsProvider $smsProvider): View
    {
        $this->authorize('update', $smsProvider);

        return view('admin.sms-providers.edit', [
            'pageTitle' => 'SMS Sağlayıcı Düzenle',
            'provider' => $smsProvider,
            'drivers' => $this->availableDrivers(),
        ]);
    }

    public function update(UpdateSmsProviderRequest $request, SmsProvider $smsProvider): RedirectResponse
    {
        $this->smsProviderService->update($smsProvider, UpdateSmsProviderData::fromArray($request->validated()));

        return redirect()->route('admin.sms-providers.index')->with('success', 'SMS sağlayıcı güncellendi.');
    }

    public function destroy(SmsProvider $smsProvider): RedirectResponse
    {
        $this->authorize('delete', $smsProvider);

        $this->smsProviderService->delete($smsProvider);

        return redirect()->route('admin.sms-providers.index')->with('success', 'SMS sağlayıcı silindi.');
    }

    public function testBalance(SmsProvider $smsProvider): RedirectResponse
    {
        $this->authorize('view', $smsProvider);

        $result = $this->smsProviderService->testBalance($smsProvider);

        if ($result->success) {
            return back()->with('success', 'Sağlayıcı bakiyesi sorgulandı: '.number_format($result->balance, 0, ',', '.').' SMS'
                .($smsProvider->driver === \App\Enums\SmsProviderDriver::Texcell
                    ? ' (ana kullanıcı SMS hakkına senkronlandı)'
                    : ''));
        }

        return back()->with('error', $result->errorMessage ?? 'Bakiye sorgulanamadı.');
    }

    /**
     * @return list<SmsProviderDriver>
     */
    private function availableDrivers(): array
    {
        $registered = array_keys(config('sms.drivers', []));

        return array_values(array_filter(
            SmsProviderDriver::cases(),
            fn (SmsProviderDriver $driver): bool => in_array($driver->value, $registered, true),
        ));
    }
}
