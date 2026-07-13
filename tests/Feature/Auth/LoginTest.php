<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Giriş işlemleri feature testleri.
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    /**
     * Giriş sayfasının görüntülendiğini doğrular.
     */
    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('SMS Panel Giriş');
    }

    /**
     * Geçerli kimlik bilgileri ile giriş yapılabildiğini doğrular.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@smspanel.local',
            'password' => Hash::make('Password123!'),
            'status' => UserStatus::Active->value,
        ]);
        $user->assignRole(RoleName::Admin->value);

        $response = $this->post(route('login.submit'), [
            'email' => 'test@smspanel.local',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Hatalı şifre ile girişin reddedildiğini doğrular.
     */
    public function test_user_cannot_login_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'test@smspanel.local',
            'password' => Hash::make('Password123!'),
            'status' => UserStatus::Active->value,
        ]);

        $response = $this->from(route('login'))->post(route('login.submit'), [
            'email' => 'test@smspanel.local',
            'password' => 'WrongPassword!',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    /**
     * Pasif kullanıcının giriş yapamadığını doğrular.
     */
    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactive@smspanel.local',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->from(route('login'))->post(route('login.submit'), [
            'email' => 'inactive@smspanel.local',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
