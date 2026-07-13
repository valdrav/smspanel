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

        $permissions = [
            'packages.manage', 'packages.view', 'packages.purchase',
            'tickets.view', 'tickets.create', 'tickets.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin = Role::query()->where('name', RoleName::SuperAdmin->value)->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        $admin = Role::query()->where('name', RoleName::Admin->value)->first();
        if ($admin) {
            $admin->givePermissionTo(['tickets.manage']);
            $admin->revokePermissionTo('packages.manage');
        }

        $customer = Role::query()->where('name', RoleName::Customer->value)->first();
        if ($customer) {
            $customer->givePermissionTo([
                'packages.view',
                'packages.purchase',
                'tickets.view',
                'tickets.create',
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};
