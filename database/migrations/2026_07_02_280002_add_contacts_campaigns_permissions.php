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

        foreach (['contacts.view', 'contacts.manage', 'campaigns.view', 'campaigns.create'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin = Role::query()->where('name', RoleName::SuperAdmin->value)->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        $admin = Role::query()->where('name', RoleName::Admin->value)->first();
        if ($admin) {
            $admin->givePermissionTo(['contacts.view', 'contacts.manage', 'campaigns.view', 'campaigns.create']);
            $admin->revokePermissionTo([
                'organizations.view', 'organizations.create', 'organizations.update', 'organizations.delete',
                'providers.view', 'providers.manage',
            ]);
        }

        $customer = Role::query()->where('name', RoleName::Customer->value)->first();
        if ($customer) {
            $customer->givePermissionTo(['contacts.view', 'contacts.manage', 'campaigns.view', 'campaigns.create']);
        }
    }

    public function down(): void
    {
        //
    }
};
