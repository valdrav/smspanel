<?php

namespace Tests\Feature\Sms;

use App\Enums\RoleName;
use App\Enums\SmsProviderDriver;
use App\Enums\SmsMessageStatus;
use App\Enums\UserStatus;
use App\Models\SmsMessage;
use App\Models\SmsProvider;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
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

    public function test_easysendsms_bulk_uses_settings_sender_and_batches_thirty_recipients(): void
    {
        SmsProvider::create([
            'code' => 'easysendsms',
            'name' => 'EasySendSMS',
            'driver' => SmsProviderDriver::EasySendSms,
            'config' => [
                'api_key' => 'settings-api-key',
                'sender_id' => 'INOVAPP',
                'base_url' => 'https://restapi.easysendsms.app/v1/rest',
            ],
            'is_active' => true,
            'is_default' => true,
            'priority' => 1,
        ]);

        Http::fake(function (Request $request) {
            $recipientCount = count(explode(',', (string) $request['to']));

            return Http::response([
                'status' => 'OK',
                'messageIds' => array_map(
                    fn (int $index) => 'OK: message-'.$index,
                    range(1, $recipientCount),
                ),
            ]);
        });

        $recipients = array_map(
            fn (int $index) => '555'.str_pad((string) $index, 7, '0', STR_PAD_LEFT),
            range(1, 60),
        );

        $response = $this->actingAs($this->user)->post(route('admin.sms.send.bulk'), [
            'recipients' => implode("\n", $recipients),
            'message' => 'Toplu gönderim',
        ]);

        $response->assertRedirect(route('admin.sms.history.index'));
        $this->assertDatabaseCount('sms_messages', 60);
        $this->assertCount(2, Http::recorded());

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('apikey', 'settings-api-key')
            && $request['from'] === 'INOVAPP'
            && count(explode(',', (string) $request['to'])) === 30);
    }
}
