<?php

namespace App\Sms\Providers;

use App\Sms\DTOs\SmsBalanceResult;
use App\Sms\DTOs\SmsSendRequest;
use App\Sms\DTOs\SmsSendResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * İleti Merkezi SMS API entegrasyonu.
 */
class IletiMerkeziSmsProvider extends AbstractSmsProvider
{
    private const DEFAULT_SEND_URL = 'https://api.iletimerkezi.com/v1/send-sms/json';

    private const DEFAULT_BALANCE_URL = 'https://api.iletimerkezi.com/v1/get-balance/json';

    public function getName(): string
    {
        return 'iletimerkezi';
    }

    public function send(SmsSendRequest $request): SmsSendResult
    {
        $apiKey = (string) $this->config('api_key');
        $secret = (string) $this->config('secret');

        if ($apiKey === '' || $secret === '') {
            return new SmsSendResult(success: false, errorMessage: 'İleti Merkezi API bilgileri eksik.');
        }

        try {
            $response = Http::timeout(30)->post($this->config('send_url', self::DEFAULT_SEND_URL), [
                'request' => [
                    'authentication' => [
                        'key' => $apiKey,
                        'hash' => $secret,
                    ],
                    'order' => [
                        'sender' => $request->senderId ?? $this->config('sender'),
                        'message' => [
                            'text' => $request->message,
                            'receipents' => [
                                'number' => [$request->to],
                            ],
                        ],
                    ],
                ],
            ]);

            $json = $response->json();

            if (! $response->successful()) {
                return new SmsSendResult(success: false, errorMessage: 'İleti Merkezi HTTP hatası.');
            }

            $status = data_get($json, 'response.status.code');

            if ((string) $status === '200') {
                return new SmsSendResult(
                    success: true,
                    messageId: (string) data_get($json, 'response.order.id', uniqid('IM-')),
                    status: 'sent',
                );
            }

            return new SmsSendResult(
                success: false,
                errorMessage: (string) data_get($json, 'response.status.message', 'Gönderim başarısız'),
            );
        } catch (\Throwable $e) {
            Log::channel('daily')->error('İleti Merkezi SMS hatası', ['error' => $e->getMessage()]);

            return new SmsSendResult(success: false, errorMessage: $e->getMessage());
        }
    }

    public function getBalance(): SmsBalanceResult
    {
        $apiKey = (string) $this->config('api_key');
        $secret = (string) $this->config('secret');

        try {
            $response = Http::timeout(30)->post($this->config('balance_url', self::DEFAULT_BALANCE_URL), [
                'request' => [
                    'authentication' => [
                        'key' => $apiKey,
                        'hash' => $secret,
                    ],
                ],
            ]);

            $amount = data_get($response->json(), 'response.balance.amount');

            if ($amount !== null) {
                return new SmsBalanceResult(success: true, balance: (float) $amount, currency: 'SMS');
            }

            return new SmsBalanceResult(success: false, errorMessage: 'Bakiye sorgulanamadı.');
        } catch (\Throwable $e) {
            return new SmsBalanceResult(success: false, errorMessage: $e->getMessage());
        }
    }

    public function getDeliveryStatus(string $messageId): SmsSendResult
    {
        return new SmsSendResult(success: true, messageId: $messageId, status: 'sent');
    }
}
