<?php

namespace Tests\Feature\Sms;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\SmsMessage;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SMS geçmişi feature testleri.
 */
class SmsHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    /**
     * Kullanıcının kendi SMS geçmişini görebildiğini doğrular.
     */
    public function test_user_can_view_own_sms_history(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active->value, 'sms_balance' => 50]);
        $user->assignRole(RoleName::Customer->value);

        SmsMessage::factory()->create([
            'user_id' => $user->id,
            'recipient' => '5551112233',
            'message' => 'Kendi mesajım',
        ]);

        $otherUser = User::factory()->create(['status' => UserStatus::Active->value]);
        SmsMessage::factory()->create(['user_id' => $otherUser->id, 'message' => 'Başka kullanıcı mesajı']);

        $response = $this->actingAs($user)->get(route('admin.sms.history.index'));

        $response->assertStatus(200);
        $response->assertSee('Kendi mesajım');
        $response->assertDontSee('Başka kullanıcı mesajı');
    }

    /**
     * Admin yalnızca kendi SMS geçmişini görebilir.
     */
    public function test_admin_sees_only_own_sms_history(): void
    {
        $admin = User::factory()->create(['status' => UserStatus::Active->value]);
        $admin->assignRole(RoleName::Admin->value);

        SmsMessage::factory()->create(['user_id' => $admin->id, 'message' => 'Admin mesajı']);
        SmsMessage::factory()->create(['message' => 'Başka kullanıcı mesajı']);

        $response = $this->actingAs($admin)->get(route('admin.sms.history.index'));

        $response->assertStatus(200);
        $response->assertSee('Admin mesajı');
        $response->assertDontSee('Başka kullanıcı mesajı');
    }

    /**
     * SMS detay sayfasının görüntülendiğini doğrular.
     */
    public function test_user_can_view_sms_detail(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active->value, 'sms_balance' => 50]);
        $user->assignRole(RoleName::Customer->value);

        $message = SmsMessage::factory()->create([
            'user_id' => $user->id,
            'message' => 'Detay test mesajı',
        ]);

        $response = $this->actingAs($user)->get(route('admin.sms.history.show', $message));

        $response->assertStatus(200);
        $response->assertSee('Detay test mesajı');
    }
}
