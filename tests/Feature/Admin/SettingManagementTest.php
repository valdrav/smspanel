<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_settings(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(SettingSeeder::class);

        $admin = User::factory()->create(['status' => UserStatus::Active->value]);
        $admin->assignRole(RoleName::SuperAdmin->value);

        $response = $this->actingAs($admin)->get(route('admin.settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Sistem Ayarları');
    }

    public function test_admin_cannot_view_settings(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $admin = User::factory()->create(['status' => UserStatus::Active->value]);
        $admin->assignRole(RoleName::Admin->value);

        $response = $this->actingAs($admin)->get(route('admin.settings.index'));

        $response->assertStatus(403);
    }
}
