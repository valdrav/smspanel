<?php

namespace Tests\Unit\Sms;

use App\Sms\DTOs\SmsSendRequest;
use App\Sms\Providers\EasySendSmsProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EasySendSmsProviderTest extends TestCase
{
    public function test_it_sends_turkish_unicode_sms_with_international_number(): void
    {
        Http::fake([
            '*/v1/rest/sms/send' => Http::response([
                'status' => 'OK',
                'scheduled' => 'Now',
                'messageIds' => ['OK: message-123'],
            ]),
        ]);

        $provider = new EasySendSmsProvider([
            'api_key' => 'secret-key',
            'sender_id' => 'INOVAPP',
        ]);

        $result = $provider->send(new SmsSendRequest(
            to: '5551234567',
            message: 'Türkçe mesaj',
        ));

        $this->assertTrue($result->success);
        $this->assertSame('message-123', $result->messageId);
        $this->assertSame('sent', $result->status);

        Http::assertSent(function (Request $request): bool {
            return str_ends_with($request->url(), '/v1/rest/sms/send')
                && $request->hasHeader('apikey', 'secret-key')
                && $request['from'] === 'INOVAPP'
                && $request['to'] === '905551234567'
                && $request['text'] === 'Türkçe mesaj'
                && $request['type'] === '1';
        });
    }

    public function test_it_sends_at_most_thirty_recipients_per_api_request(): void
    {
        Http::fake(function (Request $request) {
            $count = count(explode(',', (string) $request['to']));

            return Http::response([
                'status' => 'OK',
                'messageIds' => array_map(
                    fn (int $index) => 'OK: id-'.$index,
                    range(1, $count)
                ),
            ]);
        });

        $provider = new EasySendSmsProvider([
            'api_key' => 'secret-key',
            'sender_id' => 'INOVAPP',
        ]);

        $requests = array_map(
            fn (int $index) => new SmsSendRequest(to: '555'.str_pad((string) $index, 7, '0', STR_PAD_LEFT), message: 'Test'),
            range(1, 31)
        );

        $results = $provider->sendBulk($requests);

        $this->assertCount(31, $results);
        $this->assertCount(2, Http::recorded());
        $this->assertTrue(collect($results)->every(fn ($result) => $result->success));
    }

    public function test_it_deduplicates_numbers_in_single_request(): void
    {
        Http::fake([
            '*/v1/rest/sms/send' => Http::response([
                'status' => 'OK',
                'messageIds' => ['OK: only-one'],
            ]),
        ]);

        $provider = new EasySendSmsProvider([
            'api_key' => 'secret-key',
            'sender_id' => 'INOVAPP',
        ]);

        $results = $provider->sendBulk([
            new SmsSendRequest(to: '5551234567', message: 'Aynı'),
            new SmsSendRequest(to: '05551234567', message: 'Aynı'),
        ]);

        $this->assertTrue($results[0]->success);
        $this->assertTrue($results[1]->success);
        $this->assertSame('only-one', $results[0]->messageId);
        $this->assertSame('only-one', $results[1]->messageId);

        Http::assertSent(fn (Request $request) => $request['to'] === '905551234567');
    }

    public function test_it_reads_sms_balance_with_apikey_header_via_get(): void
    {
        Http::fake([
            '*/v1/rest/sms/balance' => Http::response(['balance' => 247247]),
        ]);

        $provider = new EasySendSmsProvider(['api_key' => 'secret-key']);
        $result = $provider->getBalance();

        $this->assertTrue($result->success);
        $this->assertSame(247247.0, $result->balance);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && str_ends_with($request->url(), '/v1/rest/sms/balance')
                && $request->hasHeader('APIKEY', 'secret-key');
        });
    }

    public function test_it_maps_partial_invalid_number_errors(): void
    {
        Http::fake([
            '*/v1/rest/sms/send' => Http::response([
                'status' => 'OK',
                'messageIds' => [
                    'OK: ok-id',
                    'ERR: 4012',
                ],
            ]),
        ]);

        $provider = new EasySendSmsProvider([
            'api_key' => 'secret-key',
            'sender_id' => 'INOVAPP',
        ]);

        $results = $provider->sendBulk([
            new SmsSendRequest(to: '5551234567', message: 'Test'),
            new SmsSendRequest(to: '1234', message: 'Test'),
        ]);

        $this->assertTrue($results[0]->success);
        $this->assertFalse($results[1]->success);
        $this->assertStringContainsString('Telefon numarası geçersiz', (string) $results[1]->errorMessage);
    }

    public function test_it_rejects_overlong_alphanumeric_sender(): void
    {
        Http::fake();

        $provider = new EasySendSmsProvider([
            'api_key' => 'secret-key',
            'sender_id' => 'TOOLONGSENDER',
        ]);

        $result = $provider->send(new SmsSendRequest(to: '5551234567', message: 'Test'));

        $this->assertFalse($result->success);
        $this->assertStringContainsString('11 karakter', (string) $result->errorMessage);
        Http::assertNothingSent();
    }

    public function test_it_returns_provider_error_without_throwing(): void
    {
        Http::fake([
            '*/v1/rest/sms/send' => Http::response([
                'error' => 4015,
                'description' => 'Insufficient credits.',
            ], 402),
        ]);

        $provider = new EasySendSmsProvider([
            'api_key' => 'secret-key',
            'sender_id' => 'INOVAPP',
        ]);

        $result = $provider->send(new SmsSendRequest(to: '5551234567', message: 'Test'));

        $this->assertFalse($result->success);
        $this->assertStringContainsString('4015', (string) $result->errorMessage);
    }
}
