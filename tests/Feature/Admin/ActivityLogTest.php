<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_activity_logs(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $superAdmin = User::factory()->create(['status' => UserStatus::Active->value]);
        $superAdmin->assignRole(RoleName::SuperAdmin->value);

        $response = $this->actingAs($superAdmin)->get(route('admin.activity-logs.index'));

        $response->assertStatus(200);
        $response->assertSee('Aktivite Logları');
    }

    public function test_admin_cannot_view_activity_logs(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $admin = User::factory()->create(['status' => UserStatus::Active->value]);
        $admin->assignRole(RoleName::Admin->value);

        $response = $this->actingAs($admin)->get(route('admin.activity-logs.index'));

        $response->assertStatus(403);
    }
}
