<?php

namespace App\Sms\Providers;

use App\Sms\DTOs\SmsBalanceResult;
use App\Sms\DTOs\SmsSendRequest;
use App\Sms\DTOs\SmsSendResult;
use App\Sms\Support\SmsSegmentCalculator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * EasySendSMS REST API v1 entegrasyonu.
 *
 * @see https://www.easysendsms.com/rest-api
 * @see https://github.com/EasySendSMS/REST-API-v1
 */
class EasySendSmsProvider extends AbstractSmsProvider
{
    private const DEFAULT_HOST = 'https://restapi.easysendsms.app';

    private const MAX_RECIPIENTS_PER_REQUEST = 30;

    public function getName(): string
    {
        return 'easysendsms';
    }

    public function send(SmsSendRequest $request): SmsSendResult
    {
        return $this->sendBulk([$request])[0];
    }

    /**
     * Aynı metin + gönderici için en fazla 30 alıcıyı tek istekte gönderir.
     *
     * @param  list<SmsSendRequest>  $requests
     * @return list<SmsSendResult>
     */
    public function sendBulk(array $requests): array
    {
        if ($requests === []) {
            return [];
        }

        if ($this->apiKey() === '') {
            return array_map(
                fn () => $this->failure('EasySendSMS API anahtarı yapılandırılmamış. Account Settings → REST API bölümünden anahtar alın.'),
                $requests
            );
        }

        $grouped = [];
        foreach ($requests as $index => $request) {
            $sender = $this->resolveSender($request->senderId);
            $key = $sender."\0".$request->message;
            $grouped[$key]['sender'] = $sender;
            $grouped[$key]['message'] = $request->message;
            $grouped[$key]['items'][] = ['index' => $index, 'request' => $request];
        }

        $results = array_fill(0, count($requests), $this->failure('EasySendSMS: beklenmeyen durum.'));

        foreach ($grouped as $group) {
            if ($group['sender'] === '') {
                foreach ($group['items'] as $item) {
                    $results[$item['index']] = $this->failure('EasySendSMS gönderici başlığı yapılandırılmamış veya geçersiz.');
                }

                continue;
            }

            $senderError = $this->validateSender($group['sender']);
            if ($senderError !== null) {
                foreach ($group['items'] as $item) {
                    $results[$item['index']] = $this->failure($senderError);
                }

                continue;
            }

            foreach (array_chunk($group['items'], self::MAX_RECIPIENTS_PER_REQUEST) as $chunk) {
                $chunkResults = $this->sendChunk(
                    array_column($chunk, 'request'),
                    $group['sender'],
                    $group['message'],
                );

                foreach ($chunk as $offset => $item) {
                    $results[$item['index']] = $chunkResults[$offset];
                }
            }
        }

        return $results;
    }

    public function getBalance(): SmsBalanceResult
    {
        $apiKey = $this->apiKey();
        if ($apiKey === '') {
            return new SmsBalanceResult(
                success: false,
                errorMessage: 'EasySendSMS API anahtarı yapılandırılmamış.',
            );
        }

        try {
            // Doküman: balance endpoint APIKEY başlığı ister; body olmamalı.
            $response = Http::timeout(30)
                ->withHeaders([
                    'APIKEY' => $apiKey,
                    'Accept' => 'application/json',
                ])
                ->get($this->endpoint('sms/balance'));

            $data = $response->json();

            if ($response->status() === 429) {
                return new SmsBalanceResult(
                    success: false,
                    errorMessage: 'EasySendSMS bakiye limiti aşıldı (2 istek/dk). 60 saniye sonra tekrar deneyin.',
                );
            }

            if (! $response->successful() || ! is_array($data) || ! is_numeric($data['balance'] ?? null)) {
                return new SmsBalanceResult(
                    success: false,
                    errorMessage: $this->responseError($response, is_array($data) ? $data : null),
                );
            }

            return new SmsBalanceResult(
                success: true,
                balance: (float) $data['balance'],
                currency: 'SMS',
            );
        } catch (\Throwable $exception) {
            Log::error('EasySendSMS bakiye sorgu hatası', ['message' => $exception->getMessage()]);

            return new SmsBalanceResult(
                success: false,
                errorMessage: 'EasySendSMS bağlantı hatası: '.$exception->getMessage(),
            );
        }
    }

    public function getDeliveryStatus(string $messageId): SmsSendResult
    {
        // REST v1 dokümanında messageId ile tekil DLR sorgu endpoint'i yok; webhook kullanılır.
        return new SmsSendResult(
            success: true,
            messageId: $messageId,
            status: 'sent',
        );
    }

    /**
     * @param  list<SmsSendRequest>  $requests
     * @return list<SmsSendResult>
     */
    private function sendChunk(array $requests, string $senderId, string $message): array
    {
        $calculator = new SmsSegmentCalculator;

        if ($calculator->calculateSegments($message) > 5) {
            return array_map(
                fn () => $this->failure('EasySendSMS: Mesaj en fazla 5 SMS segmenti olabilir.'),
                $requests,
            );
        }

        $numbers = array_map(
            fn (SmsSendRequest $request) => $this->internationalNumber($request->to),
            $requests
        );

        // API yinelenen numaraları yok sayar; messageIds benzersiz sıraya göre döner.
        $uniqueNumbers = [];
        $uniqueIndexByNumber = [];
        foreach ($numbers as $number) {
            if ($number === '' || isset($uniqueIndexByNumber[$number])) {
                continue;
            }
            $uniqueIndexByNumber[$number] = count($uniqueNumbers);
            $uniqueNumbers[] = $number;
        }

        if ($uniqueNumbers === []) {
            return array_map(
                fn () => $this->failure('EasySendSMS: Telefon numarası geçersiz.'),
                $requests
            );
        }

        $payload = [
            'from' => $senderId,
            'to' => implode(',', $uniqueNumbers),
            'text' => $message,
            'type' => $calculator->requiresUnicodeEncoding($message) ? '1' : '0',
        ];

        try {
            $response = $this->postSendWithRateLimitRetry($payload);
            $data = $response->json();

            if (! is_array($data) || ($data['status'] ?? null) !== 'OK' || ! $response->successful()) {
                $failure = $this->failure($this->responseError($response, is_array($data) ? $data : null));

                return array_fill(0, count($requests), $failure);
            }

            $messageIds = is_array($data['messageIds'] ?? null) ? array_values($data['messageIds']) : [];
            $resultByUniqueIndex = [];

            foreach ($uniqueNumbers as $index => $number) {
                $rawId = (string) ($messageIds[$index] ?? '');

                if ($rawId === '' || str_starts_with($rawId, 'ERR:')) {
                    $code = $rawId !== '' ? trim(substr($rawId, 4)) : '4012';
                    $resultByUniqueIndex[$index] = $this->failure($this->errorMessage($code));

                    continue;
                }

                $messageId = trim((string) preg_replace('/^OK:\s*/i', '', $rawId));
                $resultByUniqueIndex[$index] = new SmsSendResult(
                    success: true,
                    messageId: $messageId !== '' ? $messageId : null,
                    status: 'sent',
                );
            }

            return array_map(function (SmsSendRequest $request, int $offset) use ($numbers, $uniqueIndexByNumber, $resultByUniqueIndex): SmsSendResult {
                $number = $numbers[$offset];

                if ($number === '' || ! isset($uniqueIndexByNumber[$number])) {
                    return $this->failure('EasySendSMS: Telefon numarası geçersiz.');
                }

                return $resultByUniqueIndex[$uniqueIndexByNumber[$number]]
                    ?? $this->failure('EasySendSMS: Gönderim yanıtı eksik.');
            }, $requests, array_keys($requests));
        } catch (ConnectionException $exception) {
            Log::error('EasySendSMS bağlantı hatası', ['message' => $exception->getMessage()]);
            $failure = $this->failure('EasySendSMS bağlantı hatası: '.$exception->getMessage());

            return array_fill(0, count($requests), $failure);
        } catch (\Throwable $exception) {
            Log::error('EasySendSMS gönderim hatası', ['message' => $exception->getMessage()]);
            $failure = $this->failure('EasySendSMS gönderim hatası: '.$exception->getMessage());

            return array_fill(0, count($requests), $failure);
        }
    }

    /**
     * @param  array<string, string>  $payload
     */
    private function postSendWithRateLimitRetry(array $payload): Response
    {
        $attempt = 0;

        while (true) {
            $attempt++;

            $response = Http::timeout(45)
                ->acceptJson()
                ->asJson()
                ->withHeaders(['apikey' => $this->apiKey()])
                ->post($this->endpoint('sms/send'), $payload);

            // 4008/4009 için doküman "aynı isteği tekrarlama" diyor.
            if (in_array($response->status(), [429], true) && $attempt < 3) {
                usleep(350_000 * $attempt);

                continue;
            }

            return $response;
        }
    }

    private function apiKey(): string
    {
        return trim((string) $this->config('api_key', config('sms.easysendsms.api_key')));
    }

    private function endpoint(string $path): string
    {
        $configured = trim((string) $this->config('base_url', config('sms.easysendsms.base_url')));

        if ($configured === '') {
            $configured = self::DEFAULT_HOST.'/v1/rest';
        } elseif (! str_contains($configured, '/v1/rest')) {
            $configured = rtrim($configured, '/').'/v1/rest';
        }

        return rtrim($configured, '/').'/'.ltrim($path, '/');
    }

    private function resolveSender(?string $senderId): string
    {
        return trim((string) ($senderId ?: $this->config('sender_id', config('sms.easysendsms.sender_id'))));
    }

    private function validateSender(string $sender): ?string
    {
        $normalized = ltrim($sender, '+');

        if ($normalized !== '' && ctype_digit($normalized)) {
            if (strlen($normalized) > 15) {
                return 'EasySendSMS: Sayısal gönderici en fazla 15 karakter olabilir.';
            }

            return null;
        }

        if (mb_strlen($sender) > 11) {
            return 'EasySendSMS: Alfanumerik gönderici en fazla 11 karakter olabilir.';
        }

        return null;
    }

    /**
     * Doküman: + veya 00 kullanma; ülke kodu ile gönder (örn. 905551234567).
     */
    private function internationalNumber(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '5')) {
            return '90'.$digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '0') && str_starts_with(substr($digits, 1), '5')) {
            return '90'.substr($digits, 1);
        }

        return $digits;
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function responseError(Response $response, ?array $data): string
    {
        $code = (string) ($data['error'] ?? $response->status());
        $description = isset($data['description']) ? (string) $data['description'] : null;

        if ($description !== null && $description !== '') {
            return "EasySendSMS hatası {$code}: {$description}";
        }

        return $this->errorMessage($code);
    }

    private function errorMessage(string $code): string
    {
        $code = trim($code);

        return match ($code) {
            '4001' => 'EasySendSMS: Zorunlu parametre eksik.',
            '4002' => 'EasySendSMS: API anahtarı gönderilmedi.',
            '4003' => 'EasySendSMS: API anahtarı geçersiz.',
            '4004' => 'EasySendSMS: Sunucu IP adresine izin verilmemiş (panelde IP whitelist kontrol edin).',
            '4005' => 'EasySendSMS: API anahtarı pasif.',
            '4006' => 'EasySendSMS: Hesap pasif.',
            '4007' => 'EasySendSMS: Demo hesabın süresi dolmuş.',
            '4008' => 'EasySendSMS: Sunucu iç hatası (aynı istek tekrarlanmamalı).',
            '4009' => 'EasySendSMS: Servis geçici olarak kullanılamıyor.',
            '4010' => 'EasySendSMS: Mesaj tipi (type) geçersiz.',
            '4011' => 'EasySendSMS: Mesaj metni geçersiz.',
            '4012' => 'EasySendSMS: Telefon numarası geçersiz.',
            '4013' => 'EasySendSMS: En fazla 30 alıcı gönderilebilir.',
            '4014' => 'EasySendSMS: Gönderici adı geçersiz veya onaysız.',
            '4015' => 'EasySendSMS: Sağlayıcı kredisi yetersiz.',
            '4016' => 'EasySendSMS: Ülke veya operatör rotası kullanılamıyor.',
            '4017' => 'EasySendSMS: Zamanlama tarihi geçersiz veya geçmişte.',
            '405' => 'EasySendSMS: HTTP metodu desteklenmiyor.',
            '415' => 'EasySendSMS: Content-Type application/json olmalı.',
            '429' => 'EasySendSMS istek limiti aşıldı; kısa süre sonra tekrar deneyin.',
            default => "EasySendSMS hata kodu: {$code}",
        };
    }

    private function failure(string $message): SmsSendResult
    {
        return new SmsSendResult(success: false, errorMessage: $message);
    }
}
