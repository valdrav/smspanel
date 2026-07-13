<?php

namespace Tests\Unit\Sms;

use App\Sms\DTOs\SmsSendRequest;
use App\Sms\Providers\NetgsmSmsProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NetgsmSmsProviderTest extends TestCase
{
    public function test_netgsm_send_parses_success_response(): void
    {
        Http::fake(['*' => Http::response('00 123456789')]);

        $provider = new NetgsmSmsProvider([
            'usercode' => 'testuser',
            'password' => 'testpass',
            'msgheader' => 'TEST',
        ]);

        $result = $provider->send(new SmsSendRequest(
            to: '5551234567',
            message: 'Test mesajı',
            senderId: 'TEST',
        ));

        $this->assertTrue($result->success);
        $this->assertSame('123456789', $result->messageId);
    }

    public function test_netgsm_send_parses_error_response(): void
    {
        Http::fake(['*' => Http::response('30')]);

        $provider = new NetgsmSmsProvider([
            'usercode' => 'bad',
            'password' => 'bad',
        ]);

        $result = $provider->send(new SmsSendRequest(to: '5551234567', message: 'Test'));

        $this->assertFalse($result->success);
    }
}
