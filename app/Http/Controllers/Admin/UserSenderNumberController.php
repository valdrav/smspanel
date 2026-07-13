<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\UserSenderNumber\CreateUserSenderNumberData;
use App\DTOs\UserSenderNumber\UpdateUserSenderNumberData;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserSenderNumber\StoreUserSenderNumberRequest;
use App\Http\Requests\UserSenderNumber\UpdateUserSenderNumberRequest;
use App\Models\UserSenderNumber;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\UserSenderNumberServiceInterface;
use App\Support\UserScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserSenderNumberController extends Controller
{
    public function __construct(
        private readonly UserSenderNumberServiceInterface $userSenderNumberService,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', UserSenderNumber::class);

        $authUser = auth()->user();
        $filters = $request->only(['search', 'user_id', 'is_active']);

        if (! UserScope::isPlatformAdmin($authUser)) {
            $filters['user_id'] = $authUser->id;
        }

        return view('admin.user-sender-numbers.index', [
            'pageTitle' => 'Gönderici Numaraları',
            'senderNumbers' => $this->userSenderNumberService->list($filters),
            'users' => UserScope::isPlatformAdmin($authUser)
                ? $this->userRepository->all()
                : collect([$authUser]),
            'filters' => $filters,
            'canManage' => UserScope::isPlatformAdmin($authUser),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', UserSenderNumber::class);

        return view('admin.user-sender-numbers.create', [
            'pageTitle' => 'Yeni Gönderici Numarası',
            'users' => $this->userRepository->all(),
            'selectedUserId' => $request->integer('user_id') ?: auth()->id(),
        ]);
    }

    public function store(StoreUserSenderNumberRequest $request): RedirectResponse
    {
        $this->userSenderNumberService->create(CreateUserSenderNumberData::fromArray($request->validated()));

        return redirect()
            ->route('admin.user-sender-numbers.index')
            ->with('success', 'Gönderici numarası tanımlandı.');
    }

    public function edit(UserSenderNumber $userSenderNumber): View
    {
        $this->authorize('update', $userSenderNumber);

        $userSenderNumber->load('user');

        return view('admin.user-sender-numbers.edit', [
            'pageTitle' => 'Gönderici Numarası Düzenle',
            'senderNumber' => $userSenderNumber,
        ]);
    }

    public function update(UpdateUserSenderNumberRequest $request, UserSenderNumber $userSenderNumber): RedirectResponse
    {
        $this->userSenderNumberService->update(
            $userSenderNumber,
            UpdateUserSenderNumberData::fromArray($request->validated()),
        );

        return redirect()
            ->route('admin.user-sender-numbers.index')
            ->with('success', 'Gönderici numarası güncellendi.');
    }

    public function destroy(UserSenderNumber $userSenderNumber): RedirectResponse
    {
        $this->authorize('delete', $userSenderNumber);

        $this->userSenderNumberService->delete($userSenderNumber);

        return redirect()
            ->route('admin.user-sender-numbers.index')
            ->with('success', 'Gönderici numarası silindi.');
    }
}
