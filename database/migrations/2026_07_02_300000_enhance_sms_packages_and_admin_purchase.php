<?php

use App\Enums\RoleName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_packages', function (Blueprint $table): void {
            $table->string('badge', 50)->nullable()->after('description');
            $table->json('features')->nullable()->after('badge');
            $table->string('theme', 30)->default('indigo')->after('features');
            $table->boolean('is_featured')->default(false)->after('is_public');
        });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $admin = Role::query()->where('name', RoleName::Admin->value)->first();
        if ($admin) {
            $admin->givePermissionTo(['packages.view', 'packages.purchase']);
        }

        $superAdmin = Role::query()->where('name', RoleName::SuperAdmin->value)->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }
    }

    public function down(): void
    {
        Schema::table('sms_packages', function (Blueprint $table): void {
            $table->dropColumn(['badge', 'features', 'theme', 'is_featured']);
        });
    }
};
