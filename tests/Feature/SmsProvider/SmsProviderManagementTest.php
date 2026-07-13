<?php

namespace Tests\Feature\SmsProvider;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\SmsProvider;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SmsProviderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsProviderManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->admin = User::factory()->create(['status' => UserStatus::Active->value]);
        $this->admin->assignRole(RoleName::SuperAdmin->value);
    }

    public function test_admin_can_view_sms_providers(): void
    {
        $this->seed(SmsProviderSeeder::class);

        $response = $this->actingAs($this->admin)->get(route('admin.sms-providers.index'));

        $response->assertStatus(200);
        $response->assertSee('Mock Sağlayıcı');
    }

    public function test_admin_can_create_sms_provider(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.sms-providers.store'), [
            'code' => 'netgsm-main',
            'name' => 'Netgsm Ana',
            'driver' => 'netgsm',
            'config' => ['usercode' => 'test', 'password' => 'secret', 'msgheader' => 'TEST'],
            'is_active' => true,
            'is_default' => false,
            'priority' => 10,
        ]);

        $response->assertRedirect(route('admin.sms-providers.index'));
        $this->assertDatabaseHas('sms_providers', ['code' => 'netgsm-main']);
    }

    public function test_factory_resolves_mock_provider(): void
    {
        $this->seed(SmsProviderSeeder::class);

        $provider = app(\App\Sms\SmsProviderFactory::class)->resolveDefault();

        $this->assertSame('mock', $provider->getName());
    }
}
