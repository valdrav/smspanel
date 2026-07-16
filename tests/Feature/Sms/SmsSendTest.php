<?php

namespace Tests\Feature\Sms;

use App\Enums\RoleName;
use App\Enums\SmsMessageStatus;
use App\Enums\UserStatus;
use App\Models\SmsMessage;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SMS gönderim feature testleri.
 */
class SmsSendTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->user = User::factory()->create([
            'status' => UserStatus::Active->value,
            'sms_balance' => 100.0000,
        ]);
        $this->user->assignRole(RoleName::Admin->value);
    }

    /**
     * SMS gönderim sayfasının görüntülendiğini doğrular.
     */
    public function test_user_can_view_sms_send_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.sms.send.create'));

        $response->assertStatus(200);
        $response->assertSee('SMS Gönder');
        $response->assertSee('Tekil SMS');
    }

    /**
     * Tekil SMS gönderiminin başarılı olduğunu doğrular.
     */
    public function test_user_can_send_single_sms(): void
    {
        $response = $this->actingAs($this->user)->post(route('admin.sms.send.store'), [
            'recipient' => '5551234567',
            'message' => 'Test mesajı — Türkçe karakter: şğüöç',
            'sender_id' => 'SMSPANEL',
        ]);

        $response->assertRedirect(route('admin.sms.history.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sms_messages', [
            'user_id' => $this->user->id,
            'recipient' => '5551234567',
            'status' => SmsMessageStatus::Sent->value,
        ]);
    }

    /**
     * Yetersiz bakiye ile SMS gönderilemediğini doğrular.
     */
    public function test_user_cannot_send_sms_with_insufficient_balance(): void
    {
        $this->user->update(['sms_balance' => 0]);

        $response = $this->actingAs($this->user)->from(route('admin.sms.send.create'))
            ->post(route('admin.sms.send.store'), [
                'recipient' => '5551234567',
                'message' => 'Test mesajı',
            ]);

        $response->assertRedirect(route('admin.sms.send.create'));
        $this->assertDatabaseCount('sms_messages', 0);
    }

    /**
     * Toplu SMS gönderimini doğrular.
     */
    public function test_user_can_send_bulk_sms(): void
    {
        $response = $this->actingAs($this->user)->post(route('admin.sms.send.bulk'), [
            'recipients' => "5551234567\n5559876543",
            'message' => 'Toplu test mesajı',
        ]);

        $response->assertRedirect(route('admin.sms.history.index'));
        $this->assertDatabaseCount('sms_messages', 2);
    }
}
