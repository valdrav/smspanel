<?php

use App\Enums\RoleName;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $admin = Role::query()->where('name', RoleName::Admin->value)->first();

        if ($admin) {
            if ($admin->hasPermissionTo('tickets.manage')) {
                $admin->revokePermissionTo('tickets.manage');
            }

            $admin->givePermissionTo(['tickets.view', 'tickets.create']);
        }
    }

    public function down(): void
    {
        //
    }
};
