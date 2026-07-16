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

    public function test_easysendsms_api_key_is_not_rendered_and_blank_update_preserves_it(): void
    {
        $provider = SmsProvider::create([
            'code' => 'easysendsms',
            'name' => 'EasySendSMS',
            'driver' => 'easysendsms',
            'config' => [
                'api_key' => 'very-secret-key',
                'sender_id' => 'INOVAPP',
                'base_url' => 'https://restapi.easysendsms.app/v1/rest',
            ],
            'is_active' => true,
            'is_default' => true,
            'priority' => 1,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.sms-providers.edit', $provider))
            ->assertOk()
            ->assertDontSee('very-secret-key');

        $this->actingAs($this->admin)->put(route('admin.sms-providers.update', $provider), [
            'name' => 'EasySendSMS',
            'driver' => 'easysendsms',
            'config' => [
                'api_key' => '',
                'sender_id' => 'NEWSENDER',
                'base_url' => 'https://restapi.easysendsms.app/v1/rest',
            ],
            'is_active' => true,
            'is_default' => true,
            'priority' => 1,
        ])->assertRedirect(route('admin.sms-providers.index'));

        $provider->refresh();
        $this->assertSame('very-secret-key', $provider->config['api_key']);
        $this->assertSame('NEWSENDER', $provider->config['sender_id']);
    }
}
