<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\UpdateSettingsRequest;
use App\Services\Contracts\SettingServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingServiceInterface $settingService,
    ) {}

    public function index(): View
    {
        $this->authorize('settings.manage');

        return view('admin.settings.index', [
            'pageTitle' => 'Sistem Ayarları',
            'settings' => $this->settingService->allGrouped(),
            'values' => [
                'app_name' => $this->settingService->get('app_name', 'SMS Panel'),
                'app_tagline' => $this->settingService->get('app_tagline', ''),
                'support_email' => $this->settingService->get('support_email', ''),
                'primary_color' => $this->settingService->get('primary_color', '#6366f1'),
                'accent_color' => $this->settingService->get('accent_color', '#22d3ee'),
                'login_welcome' => $this->settingService->get('login_welcome', 'Hoş geldiniz'),
                'footer_text' => $this->settingService->get('footer_text', ''),
                'logo_path' => $this->settingService->get('logo_path'),
            ],
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->settingService->setMany([
            'app_name' => $data['app_name'],
            'app_tagline' => $data['app_tagline'] ?? '',
            'support_email' => $data['support_email'] ?? '',
            'primary_color' => $data['primary_color'],
            'accent_color' => $data['accent_color'],
            'login_welcome' => $data['login_welcome'] ?? '',
            'footer_text' => $data['footer_text'] ?? '',
        ], 'general');

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('settings', 'public');
            $old = $this->settingService->get('logo_path');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
            $this->settingService->set('logo_path', $path, 'branding');
        }

        if ($request->boolean('remove_logo')) {
            $old = $this->settingService->get('logo_path');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
            $this->settingService->set('logo_path', '', 'branding');
        }

        $this->settingService->applyBranding();

        return back()->with('success', 'Ayarlar kaydedildi.');
    }

    public function regenerateApiToken(): RedirectResponse
    {
        $this->authorize('settings.manage');

        $user = auth()->user();
        $plainToken = Str::random(64);
        $user->update(['api_token' => hash('sha256', $plainToken)]);

        return back()->with('success', 'API token oluşturuldu.')->with('api_token_plain', $plainToken);
    }
}
