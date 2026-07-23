<?php

namespace Tests\Unit\Sms;

use App\Sms\DTOs\SmsSendRequest;
use App\Sms\Providers\TexcellEimsSmsProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TexcellEimsSmsProviderTest extends TestCase
{
    public function test_send_parses_success_array(): void
    {
        Http::fake([
            '*/sendsms' => Http::response([
                'status' => 0,
                'success' => 1,
                'fail' => 0,
                'array' => [[905551234567, 42]],
            ], 200),
        ]);

        $provider = new TexcellEimsSmsProvider([
            'account' => 'CTU780',
            'password' => 'secret',
            'base_url' => 'http://38.150.64.36:20003',
            'sender' => 'TEST',
        ]);

        $result = $provider->send(new SmsSendRequest(
            to: '5551234567',
            message: 'Merhaba',
            senderId: 'TEST',
        ));

        $this->assertTrue($result->success);
        $this->assertSame('42', $result->messageId);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'http://38.150.64.36:20003/sendsms'
                && ($data['account'] ?? null) === 'CTU780'
                && ($data['numbers'] ?? null) === '905551234567'
                && ($data['content'] ?? null) === 'Merhaba'
                && ($data['smstype'] ?? null) === 0;
        });
    }

    public function test_send_maps_auth_error(): void
    {
        Http::fake([
            '*/sendsms' => Http::response(['status' => -1], 200),
        ]);

        $provider = new TexcellEimsSmsProvider([
            'account' => 'bad',
            'password' => 'bad',
            'base_url' => 'http://38.150.64.36:20003',
        ]);

        $result = $provider->send(new SmsSendRequest(to: '5551234567', message: 'Test'));

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Kimlik doğrulama', (string) $result->errorMessage);
    }

    public function test_get_balance_sums_gift(): void
    {
        Http::fake([
            '*/getbalance*' => Http::response([
                'status' => 0,
                'balance' => '99.990000',
                'gift' => '50.00000',
            ], 200),
        ]);

        $provider = new TexcellEimsSmsProvider([
            'account' => 'CTU780',
            'password' => 'secret',
            'base_url' => 'http://38.150.64.36:20003',
        ]);

        $result = $provider->getBalance();

        $this->assertTrue($result->success);
        $this->assertEqualsWithDelta(149.99, $result->balance, 0.001);
    }

    public function test_send_bulk_groups_same_message(): void
    {
        Http::fake([
            '*/sendsms' => Http::response([
                'status' => 0,
                'success' => 2,
                'fail' => 0,
                'array' => [[905551111111, 1], [905552222222, 2]],
            ], 200),
        ]);

        $provider = new TexcellEimsSmsProvider([
            'account' => 'CTU780',
            'password' => 'secret',
            'base_url' => 'http://38.150.64.36:20003',
        ]);

        $results = $provider->sendBulk([
            new SmsSendRequest(to: '5551111111', message: 'Aynı'),
            new SmsSendRequest(to: '5552222222', message: 'Aynı'),
        ]);

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]->success);
        $this->assertTrue($results[1]->success);
        $this->assertSame('1', $results[0]->messageId);
        $this->assertSame('2', $results[1]->messageId);

        Http::assertSentCount(1);
    }

    public function test_get_delivery_status_delivered(): void
    {
        Http::fake([
            '*/getreport*' => Http::response([
                'status' => 0,
                'array' => [[10, 905551234567, 20171001123015, 3]],
            ], 200),
        ]);

        $provider = new TexcellEimsSmsProvider([
            'account' => 'CTU780',
            'password' => 'secret',
            'base_url' => 'http://38.150.64.36:20003',
        ]);

        $result = $provider->getDeliveryStatus('10');

        $this->assertTrue($result->success);
        $this->assertSame('delivered', $result->status);
    }
}
