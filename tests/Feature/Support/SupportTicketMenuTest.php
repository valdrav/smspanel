<?php

namespace Tests\Feature\Support;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_sees_support_menu_on_dashboard(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $customer = User::factory()->create(['status' => UserStatus::Active->value]);
        $customer->assignRole(RoleName::Customer->value);

        $response = $this->actingAs($customer)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Destek Talepleri');
    }

    public function test_admin_sees_support_menu(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $admin = User::factory()->create(['status' => UserStatus::Active->value]);
        $admin->assignRole(RoleName::Admin->value);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Destek Talepleri');
    }
}
