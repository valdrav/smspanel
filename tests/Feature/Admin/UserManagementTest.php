<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kullanıcı yönetimi feature testleri.
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create([
            'status' => UserStatus::Active->value,
        ]);
        $this->admin->assignRole(RoleName::SuperAdmin->value);
    }

    /**
     * Yönetici kullanıcı listesini görüntüleyebilir.
     */
    public function test_admin_can_view_users_list(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertSee('Kullanıcı Yönetimi');
    }

    /**
     * Yönetici yeni kullanıcı oluşturabilir.
     */
    public function test_admin_can_create_user(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Test Kullanıcı',
            'email' => 'yeni@smspanel.local',
            'phone' => '5551234567',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'status' => UserStatus::Active->value,
            'roles' => [RoleName::Customer->value],
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['email' => 'yeni@smspanel.local']);
    }

    /**
     * Admin rolü kullanıcı yönetimine erişemez (yalnızca süper yönetici).
     */
    public function test_admin_role_cannot_access_user_management(): void
    {
        $admin = User::factory()->create(['status' => UserStatus::Active->value]);
        $admin->assignRole(RoleName::Admin->value);

        $this->actingAs($admin)->get(route('admin.users.index'))->assertForbidden();
    }

    /**
     * Müşteri rolündeki kullanıcı yönetim sayfasına erişemez.
     */
    public function test_customer_cannot_access_user_management(): void
    {
        $customer = User::factory()->create(['status' => UserStatus::Active->value]);
        $customer->assignRole(RoleName::Customer->value);

        $response = $this->actingAs($customer)->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    /**
     * Yönetici kullanıcı silebilir.
     */
    public function test_admin_can_delete_user(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active->value]);
        $user->assignRole(RoleName::Customer->value);

        $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $user));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
