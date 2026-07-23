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

    public function test_admin_can_view_create_sms_provider_form(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.sms-providers.create'))
            ->assertOk()
            ->assertSee('Texcell EIMS');
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

    public function test_factory_resolves_texcell_as_default_provider(): void
    {
        $this->seed(SmsProviderSeeder::class);

        $provider = app(\App\Sms\SmsProviderFactory::class)->resolveDefault();

        $this->assertSame('texcell', $provider->getName());
    }

    public function test_texcell_password_is_not_rendered_and_blank_update_preserves_it(): void
    {
        $provider = SmsProvider::create([
            'code' => 'texcell',
            'name' => 'Texcell EIMS',
            'driver' => 'texcell',
            'config' => [
                'account' => 'CTU780',
                'password' => 'very-secret-pass',
                'base_url' => 'http://38.150.64.36:20003',
                'sender' => 'INOVAPP',
            ],
            'is_active' => true,
            'is_default' => true,
            'priority' => 1,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.sms-providers.edit', $provider))
            ->assertOk()
            ->assertDontSee('very-secret-pass');

        $this->actingAs($this->admin)->put(route('admin.sms-providers.update', $provider), [
            'name' => 'Texcell EIMS',
            'driver' => 'texcell',
            'config' => [
                'account' => 'CTU780',
                'password' => '',
                'base_url' => 'http://38.150.64.36:20003',
                'sender' => 'NEWSENDER',
            ],
            'is_active' => true,
            'is_default' => true,
            'priority' => 1,
        ])->assertRedirect(route('admin.sms-providers.index'));

        $provider->refresh();
        $this->assertSame('very-secret-pass', $provider->config['password']);
        $this->assertSame('NEWSENDER', $provider->config['sender']);
    }
}
