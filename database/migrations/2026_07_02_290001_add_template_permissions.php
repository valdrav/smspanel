<?php

use App\Enums\RoleName;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['templates.view', 'templates.manage'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin = Role::query()->where('name', RoleName::SuperAdmin->value)->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        foreach ([RoleName::Admin->value, RoleName::Customer->value] as $roleName) {
            $role = Role::query()->where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo(['templates.view', 'templates.manage']);
                if ($roleName === RoleName::Admin->value) {
                    $role->revokePermissionTo([
                        'users.view', 'users.create', 'users.update', 'users.delete',
                        'sender-numbers.manage',
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        //
    }
};
