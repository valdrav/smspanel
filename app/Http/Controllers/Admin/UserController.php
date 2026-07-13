<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\User\CreateUserData;
use App\DTOs\User\UpdateUserData;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\Contracts\UserServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * Kullanıcı yönetimi controller'ı.
 */
class UserController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService,
    ) {}

    /**
     * Kullanıcı listesini gösterir.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = $this->userService->list(
            filters: $request->only(['search', 'status', 'role']),
            perPage: 15,
        );

        return view('admin.users.index', [
            'pageTitle' => 'Kullanıcı Yönetimi',
            'users' => $users,
            'statuses' => UserStatus::cases(),
            'roles' => Role::all(),
            'filters' => $request->only(['search', 'status', 'role']),
        ]);
    }

    /**
     * Kullanıcı oluşturma formunu gösterir.
     */
    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', [
            'pageTitle' => 'Yeni Kullanıcı',
            'statuses' => UserStatus::cases(),
            'roles' => Role::all(),
        ]);
    }

    /**
     * Yeni kullanıcı oluşturur.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->userService->create(CreateUserData::fromArray($request->validated()));

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Kullanıcı başarıyla oluşturuldu.');
    }

    /**
     * Kullanıcı detayını gösterir.
     */
    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $user->load(['roles', 'senderNumbers', 'activityLogs' => fn ($query) => $query->latest()->limit(10)]);

        return view('admin.users.show', [
            'pageTitle' => 'Kullanıcı Detayı',
            'user' => $user,
        ]);
    }

    /**
     * Kullanıcı düzenleme formunu gösterir.
     */
    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $user->load('roles');

        return view('admin.users.edit', [
            'pageTitle' => 'Kullanıcı Düzenle',
            'user' => $user,
            'statuses' => UserStatus::cases(),
            'roles' => Role::all(),
        ]);
    }

    /**
     * Kullanıcı bilgilerini günceller.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userService->update($user, UpdateUserData::fromArray($request->validated()));

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Kullanıcı başarıyla güncellendi.');
    }

    /**
     * Kullanıcıyı siler.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $this->userService->delete($user);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Kullanıcı başarıyla silindi.');
    }
}
