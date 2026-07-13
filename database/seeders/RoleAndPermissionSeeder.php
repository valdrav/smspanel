<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'users.view', 'users.create', 'users.update', 'users.delete',
            'organizations.view', 'organizations.create', 'organizations.update', 'organizations.delete',
            'wallet.view', 'wallet.credit',
            'providers.view', 'providers.manage',
            'sender-numbers.view', 'sender-numbers.manage',
            'activity.view',
            'dashboard.view', 'sms.send', 'sms.view', 'reports.view', 'settings.manage',
            'roles.view', 'roles.manage',
            'packages.manage', 'packages.view', 'packages.purchase',
            'tickets.view', 'tickets.create', 'tickets.manage',
            'contacts.view', 'contacts.manage', 'campaigns.view', 'campaigns.create',
            'templates.view', 'templates.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => RoleName::SuperAdmin->value, 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => RoleName::Admin->value, 'guard_name' => 'web']);
        $customer = Role::firstOrCreate(['name' => RoleName::Customer->value, 'guard_name' => 'web']);

        $superAdmin->syncPermissions(Permission::all());

        $admin->syncPermissions([
            'wallet.view', 'wallet.credit',
            'sender-numbers.view',
            'dashboard.view', 'sms.send', 'sms.view', 'reports.view',
            'tickets.view', 'tickets.create',
            'contacts.view', 'contacts.manage', 'campaigns.view', 'campaigns.create',
            'templates.view', 'templates.manage',
        ]);

        $customer->syncPermissions([
            'dashboard.view', 'sms.send', 'sms.view', 'wallet.view',
            'sender-numbers.view',
            'packages.view', 'packages.purchase',
            'tickets.view', 'tickets.create',
            'contacts.view', 'contacts.manage', 'campaigns.view', 'campaigns.create',
            'templates.view', 'templates.manage',
        ]);
    }
}
