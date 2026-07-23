<?php

namespace Tests\Feature\Webhooks;

use App\Enums\SmsMessageStatus;
use App\Enums\SmsProviderDriver;
use App\Models\SmsMessage;
use App\Models\SmsProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TexcellReportWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_push_report_marks_message_delivered(): void
    {
        config(['sms.texcell.webhook_token' => 'secret-token']);
        config(['sms.texcell.provider_code' => 'texcell']);

        SmsProvider::create([
            'code' => 'texcell',
            'name' => 'Texcell',
            'driver' => SmsProviderDriver::Texcell,
            'config' => [
                'account' => 'CTU780',
                'password' => 'x',
                'base_url' => 'http://38.150.64.36:20003',
            ],
            'is_active' => true,
            'is_default' => true,
            'priority' => 1,
        ]);

        $user = User::factory()->create();

        $message = SmsMessage::create([
            'user_id' => $user->id,
            'recipient' => '5551234567',
            'message' => 'Test',
            'sender_id' => 'TEST',
            'provider' => 'texcell',
            'provider_message_id' => '99',
            'status' => SmsMessageStatus::Sent->value,
            'segments' => 1,
            'cost' => 1,
            'sent_at' => now(),
        ]);

        $response = $this->putJson('/api/webhooks/texcell/secret-token/report', [
            'type' => 'report',
            'cnt' => 1,
            'array' => [
                [99, '905551234567', 20180801123015, 0, 'success'],
            ],
        ]);

        $response->assertOk()->assertJson(['status' => 0, 'applied' => 1]);

        $message->refresh();
        $this->assertSame(SmsMessageStatus::Delivered, $message->status);
        $this->assertNotNull($message->delivered_at);
    }

    public function test_rejects_invalid_token_when_configured(): void
    {
        config(['sms.texcell.webhook_token' => 'secret-token']);

        $this->putJson('/api/webhooks/texcell/wrong/report', [
            'type' => 'report',
            'array' => [],
        ])->assertUnauthorized();
    }
}
