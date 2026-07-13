<?php

namespace Tests\Unit\Sms;

use App\Sms\DTOs\SmsSendRequest;
use App\Sms\Providers\MockSmsProvider;
use Tests\TestCase;

/**
 * Mock SMS sağlayıcı unit testleri.
 */
class MockSmsProviderTest extends TestCase
{
    /**
     * Mock sağlayıcının SMS gönderimini doğrular.
     */
    public function test_mock_provider_sends_sms_successfully(): void
    {
        $provider = new MockSmsProvider;

        $result = $provider->send(new SmsSendRequest(
            to: '5551234567',
            message: 'Test mesajı — Türkçe karakter: şğüöç',
            senderId: 'SMSPANEL',
        ));

        $this->assertTrue($result->success);
        $this->assertNotNull($result->messageId);
        $this->assertSame('delivered', $result->status);
    }

    /**
     * Mock sağlayıcının bakiye sorgusunu doğrular.
     */
    public function test_mock_provider_returns_balance(): void
    {
        $provider = new MockSmsProvider;

        $result = $provider->getBalance();

        $this->assertTrue($result->success);
        $this->assertGreaterThan(0, $result->balance);
    }
}
