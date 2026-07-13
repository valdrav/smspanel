<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Role\UpdateRolePermissionsRequest;
use App\Support\PermissionLabel;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function index(): View
    {
        $this->authorize('roles.manage');

        $roles = Role::query()->withCount('permissions')->orderBy('name')->get();
        $permissions = Permission::query()->orderBy('name')->get()
            ->groupBy(fn (Permission $p): string => explode('.', $p->name)[0]);

        return view('admin.roles.index', [
            'pageTitle' => 'Roller & Yetkiler',
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function edit(Role $role): View
    {
        $this->authorize('roles.manage');

        $permissions = Permission::query()->orderBy('name')->get()
            ->groupBy(fn (Permission $p): string => explode('.', $p->name)[0]);

        return view('admin.roles.edit', [
            'pageTitle' => 'Rol Yetkileri: '.RoleName::labelFor($role->name),
            'role' => $role->load('permissions'),
            'permissions' => $permissions,
        ]);
    }

    public function update(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        if ($role->name === RoleName::SuperAdmin->value && ! auth()->user()?->hasRole(RoleName::SuperAdmin->value)) {
            abort(403);
        }

        $role->syncPermissions($request->validated('permissions', []));

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Rol yetkileri güncellendi.');
    }
}
