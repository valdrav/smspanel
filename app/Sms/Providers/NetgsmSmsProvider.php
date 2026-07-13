<?php

namespace App\Sms\Providers;

use App\Sms\DTOs\SmsBalanceResult;
use App\Sms\DTOs\SmsSendRequest;
use App\Sms\DTOs\SmsSendResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Netgsm SMS API entegrasyonu.
 *
 * @see https://www.netgsm.com.tr/dokuman/
 */
class NetgsmSmsProvider extends AbstractSmsProvider
{
    private const DEFAULT_SEND_URL = 'https://api.netgsm.com.tr/sms/send/get';

    private const DEFAULT_BALANCE_URL = 'https://api.netgsm.com.tr/balance/list/get';

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'netgsm';
    }

    /**
     * {@inheritDoc}
     */
    public function send(SmsSendRequest $request): SmsSendResult
    {
        $usercode = (string) $this->config('usercode');
        $password = (string) $this->config('password');

        if ($usercode === '' || $password === '') {
            return new SmsSendResult(
                success: false,
                errorMessage: 'Netgsm kullanıcı adı veya şifre yapılandırılmamış.',
            );
        }

        try {
            $response = Http::timeout(30)->get($this->config('send_url', self::DEFAULT_SEND_URL), [
                'usercode' => $usercode,
                'password' => $password,
                'gsmno' => $request->to,
                'message' => $request->message,
                'msgheader' => $request->senderId ?? $this->config('msgheader'),
                'dil' => 'TR',
            ]);

            $body = trim($response->body());

            if (! $response->successful()) {
                return new SmsSendResult(
                    success: false,
                    errorMessage: "Netgsm HTTP hatası: {$response->status()}",
                );
            }

            return $this->parseSendResponse($body);
        } catch (\Throwable $exception) {
            Log::channel('daily')->error('Netgsm SMS gönderim hatası', [
                'message' => $exception->getMessage(),
            ]);

            return new SmsSendResult(
                success: false,
                errorMessage: 'Netgsm bağlantı hatası: '.$exception->getMessage(),
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getBalance(): SmsBalanceResult
    {
        $usercode = (string) $this->config('usercode');
        $password = (string) $this->config('password');

        if ($usercode === '' || $password === '') {
            return new SmsBalanceResult(
                success: false,
                errorMessage: 'Netgsm kimlik bilgileri eksik.',
            );
        }

        try {
            $response = Http::timeout(30)->get($this->config('balance_url', self::DEFAULT_BALANCE_URL), [
                'usercode' => $usercode,
                'password' => $password,
                'stip' => 1,
            ]);

            $body = trim($response->body());

            if (! $response->successful()) {
                return new SmsBalanceResult(
                    success: false,
                    errorMessage: "Netgsm bakiye sorgu hatası: {$response->status()}",
                );
            }

            if (! is_numeric($body)) {
                return new SmsBalanceResult(
                    success: false,
                    errorMessage: "Netgsm bakiye yanıtı geçersiz: {$body}",
                );
            }

            return new SmsBalanceResult(
                success: true,
                balance: (float) $body,
                currency: 'TRY',
            );
        } catch (\Throwable $exception) {
            return new SmsBalanceResult(
                success: false,
                errorMessage: 'Netgsm bakiye sorgu hatası: '.$exception->getMessage(),
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDeliveryStatus(string $messageId): SmsSendResult
    {
        return new SmsSendResult(
            success: true,
            messageId: $messageId,
            status: 'sent',
        );
    }

    /**
     * Netgsm gönderim yanıtını ayrıştırır.
     */
    private function parseSendResponse(string $body): SmsSendResult
    {
        if (str_starts_with($body, '00')) {
            $parts = preg_split('/\s+/', $body) ?: [];
            $messageId = $parts[1] ?? $body;

            return new SmsSendResult(
                success: true,
                messageId: $messageId,
                status: 'sent',
            );
        }

        $errors = [
            '20' => 'Mesaj metni hatalı veya boş.',
            '30' => 'Geçersiz kullanıcı adı, şifre veya API erişim izni yok.',
            '40' => 'Gönderici başlığı (msgheader) sistemde tanımlı değil.',
            '50' => 'Abone numarası hatalı.',
            '51' => 'Tekrar eden gönderim.',
            '70' => 'Hatalı parametre.',
            '85' => 'Mükerrer gönderim sınır aşımı.',
        ];

        $code = substr($body, 0, 2);

        return new SmsSendResult(
            success: false,
            errorMessage: $errors[$code] ?? "Netgsm hata kodu: {$body}",
        );
    }
}
