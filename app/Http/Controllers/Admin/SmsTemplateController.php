<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SmsTemplate\StoreSmsTemplateRequest;
use App\Http\Requests\SmsTemplate\UpdateSmsTemplateRequest;
use App\Models\SmsTemplate;
use App\Services\Sms\SmsTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SmsTemplateController extends Controller
{
    public function __construct(private readonly SmsTemplateService $smsTemplateService) {}

    public function index(): View
    {
        $this->authorize('viewAny', SmsTemplate::class);

        return view('admin.sms-templates.index', [
            'pageTitle' => 'SMS Şablonları',
            'templates' => $this->smsTemplateService->list(auth()->user()),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', SmsTemplate::class);

        return view('admin.sms-templates.create', ['pageTitle' => 'Yeni Şablon']);
    }

    public function store(StoreSmsTemplateRequest $request): RedirectResponse
    {
        $this->smsTemplateService->create(auth()->user(), $request->validated());

        return redirect()->route('admin.sms-templates.index')->with('success', 'Şablon oluşturuldu.');
    }

    public function edit(SmsTemplate $smsTemplate): View
    {
        $this->authorize('update', $smsTemplate);

        return view('admin.sms-templates.edit', [
            'pageTitle' => 'Şablon Düzenle',
            'template' => $smsTemplate,
        ]);
    }

    public function update(UpdateSmsTemplateRequest $request, SmsTemplate $smsTemplate): RedirectResponse
    {
        $this->smsTemplateService->update($smsTemplate, $request->validated());

        return redirect()->route('admin.sms-templates.index')->with('success', 'Şablon güncellendi.');
    }

    public function destroy(SmsTemplate $smsTemplate): RedirectResponse
    {
        $this->authorize('delete', $smsTemplate);
        $this->smsTemplateService->delete($smsTemplate);

        return redirect()->route('admin.sms-templates.index')->with('success', 'Şablon silindi.');
    }
}
