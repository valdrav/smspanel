<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Models\Contact;
use App\Models\SmsCampaign;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contact\ContactService;
use App\Services\Contracts\UserSenderNumberServiceInterface;
use App\Services\Sms\CampaignService;
use App\Support\UserScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmsCampaignController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaignService,
        private readonly ContactService $contactService,
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserSenderNumberServiceInterface $userSenderNumberService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SmsCampaign::class);
        $user = auth()->user();

        return view('admin.campaigns.index', [
            'pageTitle' => 'SMS Kampanyaları',
            'campaigns' => $this->campaignService->list($user, $request->only(['status', 'user_id'])),
            'filters' => $request->only(['status', 'user_id']),
            'statuses' => CampaignStatus::cases(),
            'canManageAll' => UserScope::isPlatformAdmin($user),
            'users' => UserScope::isPlatformAdmin($user) ? $this->userRepository->all() : collect(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', SmsCampaign::class);
        $user = auth()->user();
        $senderNumbers = $this->userSenderNumberService->getActiveForUser($user);
        $defaultSender = $senderNumbers->firstWhere('is_default', true)?->sender_id
            ?? $senderNumbers->first()?->sender_id
            ?? $this->userSenderNumberService->resolveSenderId($user, null);

        return view('admin.campaigns.create', [
            'pageTitle' => 'Yeni Kampanya',
            'contacts' => $this->contactService->getActiveForUser($user),
            'maxRecipients' => config('sms.campaign.max_recipients', 200000),
            'senderNumbers' => $senderNumbers,
            'defaultSenderId' => $defaultSender,
        ]);
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $campaign = $this->campaignService->create(auth()->user(), $request->validated());

        return redirect()
            ->route('admin.campaigns.show', $campaign)
            ->with('success', 'Kampanya kuyruğa alındı.');
    }

    public function show(SmsCampaign $campaign): View
    {
        $this->authorize('view', $campaign);
        $campaign->load(['user', 'recipients' => fn ($q) => $q->latest('id')->limit(50)]);

        return view('admin.campaigns.show', [
            'pageTitle' => 'Kampanya: '.$campaign->name,
            'campaign' => $campaign,
        ]);
    }

    public function cancel(SmsCampaign $campaign): RedirectResponse
    {
        $this->authorize('cancel', $campaign);
        $this->campaignService->cancel($campaign);

        return back()->with('success', 'Kampanya iptal edildi.');
    }
}
