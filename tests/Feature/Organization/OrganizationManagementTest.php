<?php

namespace Tests\Feature\Organization;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->admin = User::factory()->create(['status' => UserStatus::Active->value, 'sms_balance' => 1000]);
        $this->admin->assignRole(RoleName::SuperAdmin->value);
    }

    public function test_admin_can_view_organizations(): void
    {
        Organization::factory()->create(['name' => 'Test Firma']);

        $response = $this->actingAs($this->admin)->get(route('admin.organizations.index'));

        $response->assertStatus(200);
        $response->assertSee('Test Firma');
    }

    public function test_admin_can_create_organization(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.organizations.store'), [
            'name' => 'Yeni Firma Ltd.',
            'status' => 'active',
            'initial_balance' => 250,
        ]);

        $response->assertRedirect(route('admin.organizations.index'));
        $this->assertDatabaseHas('organizations', ['name' => 'Yeni Firma Ltd.']);
    }

    public function test_admin_can_credit_organization(): void
    {
        $organization = Organization::factory()->create(['sms_balance' => 100]);

        $response = $this->actingAs($this->admin)->post(route('admin.organizations.credit', $organization), [
            'amount' => 50,
            'description' => 'Test yükleme',
        ]);

        $response->assertRedirect();
        $this->assertEquals(150.0, (float) $organization->fresh()->sms_balance);
    }
}
