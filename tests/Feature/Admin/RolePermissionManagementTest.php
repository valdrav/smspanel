<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_manage_roles(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $admin = User::factory()->create(['status' => UserStatus::Active->value]);
        $admin->assignRole(RoleName::SuperAdmin->value);

        $response = $this->actingAs($admin)->get(route('admin.roles.index'));
        $response->assertStatus(200);
        $response->assertSee('Roller & Yetkiler');

        $customerRole = Role::findByName(RoleName::Customer->value);

        $response = $this->actingAs($admin)->put(route('admin.roles.update', $customerRole), [
            'permissions' => ['dashboard.view', 'sms.view'],
        ]);

        $response->assertRedirect(route('admin.roles.index'));
        $this->assertFalse($customerRole->fresh()->hasPermissionTo('sms.send'));
    }

    public function test_role_edit_shows_turkish_permission_labels(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $superAdmin = User::factory()->create(['status' => UserStatus::Active->value]);
        $superAdmin->assignRole(RoleName::SuperAdmin->value);

        $customerRole = Role::findByName(RoleName::Customer->value);

        $response = $this->actingAs($superAdmin)->get(route('admin.roles.edit', $customerRole));

        $response->assertStatus(200);
        $response->assertSee('Destek Sistemi');
        $response->assertSee('Destek taleplerini görüntüle');
        $response->assertSee('Müşteri');
    }
}
