<?php

namespace App\Sms\Providers;

use App\Sms\DTOs\SmsBalanceResult;
use App\Sms\DTOs\SmsSendRequest;
use App\Sms\DTOs\SmsSendResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Texcell / EJOIN EIMS HTTP API v3.5 entegrasyonu.
 *
 * Endpoints: /getbalance, /sendsms, /getreport, /getsms, /smsjob
 * Kimlik: account + password (şifreleme açıksa MD5).
 *
 * @see TEXCELL EIMS HTTP API_V3.5 EN.pdf
 */
class TexcellEimsSmsProvider extends AbstractSmsProvider
{
    private const DEFAULT_BASE_URL = 'http://38.150.64.36:20003';

    /** POST ile tek istekte en fazla 10000 numara; panel batch'i 30 olduğu için güvenli üst sınır. */
    private const MAX_RECIPIENTS_PER_REQUEST = 500;

    private const MAX_REPORT_IDS = 200;

    private const MAX_CONTENT_LENGTH = 1024;

    public function getName(): string
    {
        return 'texcell';
    }

    public function send(SmsSendRequest $request): SmsSendResult
    {
        return $this->sendBulk([$request])[0];
    }

    /**
     * Aynı metin + gönderici için numaraları gruplayıp POST /sendsms ile gönderir.
     *
     * @param  list<SmsSendRequest>  $requests
     * @return list<SmsSendResult>
     */
    public function sendBulk(array $requests): array
    {
        if ($requests === []) {
            return [];
        }

        if ($this->account() === '' || $this->password() === '') {
            return array_map(
                fn () => $this->failure('Texcell hesap adı veya şifre yapılandırılmamış.'),
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

        $results = array_fill(0, count($requests), $this->failure('Texcell: beklenmeyen durum.'));

        foreach ($grouped as $group) {
            if (mb_strlen($group['message']) > self::MAX_CONTENT_LENGTH) {
                foreach ($group['items'] as $item) {
                    $results[$item['index']] = $this->failure('Texcell: Mesaj metni en fazla 1024 karakter olabilir.');
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
                    $results[$item['index']] = $chunkResults[$offset]
                        ?? $this->failure('Texcell: Gönderim yanıtı eksik.');
                }
            }
        }

        return $results;
    }

    public function getBalance(): SmsBalanceResult
    {
        if ($this->account() === '' || $this->password() === '') {
            return new SmsBalanceResult(
                success: false,
                errorMessage: 'Texcell hesap adı veya şifre yapılandırılmamış.',
            );
        }

        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->withHeaders(['Content-Type' => 'application/json;charset=utf-8'])
                ->get($this->endpoint('getbalance'), $this->authQuery());

            $data = $response->json();

            if (! $response->successful() || ! is_array($data)) {
                return new SmsBalanceResult(
                    success: false,
                    errorMessage: "Texcell bakiye HTTP hatası: {$response->status()}",
                );
            }

            $status = (int) ($data['status'] ?? -99);

            if ($status !== 0) {
                return new SmsBalanceResult(
                    success: false,
                    errorMessage: $this->statusMessage($status),
                );
            }

            $balance = (float) ($data['balance'] ?? 0);
            $gift = (float) ($data['gift'] ?? 0);

            return new SmsBalanceResult(
                success: true,
                balance: $balance + $gift,
                currency: 'SMS',
            );
        } catch (\Throwable $exception) {
            Log::error('Texcell bakiye sorgu hatası', ['message' => $exception->getMessage()]);

            return new SmsBalanceResult(
                success: false,
                errorMessage: 'Texcell bağlantı hatası: '.$exception->getMessage(),
            );
        }
    }

    public function getDeliveryStatus(string $messageId): SmsSendResult
    {
        $reports = $this->getReports([$messageId]);

        return $reports[$messageId] ?? new SmsSendResult(
            success: false,
            messageId: $messageId,
            errorMessage: 'Texcell: Rapor bulunamadı.',
        );
    }

    /**
     * Birden fazla gönderim ID'si için /getreport sorgular (en fazla 200).
     *
     * @param  list<string|int>  $ids
     * @return array<string, SmsSendResult> messageId => result
     */
    public function getReports(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map(
            static fn ($id): string => trim((string) $id),
            $ids
        ), static fn (string $id): bool => $id !== '')));

        if ($ids === []) {
            return [];
        }

        if ($this->account() === '' || $this->password() === '') {
            return [];
        }

        $out = [];

        foreach (array_chunk($ids, self::MAX_REPORT_IDS) as $chunk) {
            try {
                $response = Http::timeout(30)
                    ->acceptJson()
                    ->withHeaders(['Content-Type' => 'application/json;charset=utf-8'])
                    ->get($this->endpoint('getreport'), array_merge($this->authQuery(), [
                        'ids' => implode(',', $chunk),
                    ]));

                $data = $response->json();

                if (! $response->successful() || ! is_array($data) || (int) ($data['status'] ?? -1) !== 0) {
                    Log::warning('Texcell getreport başarısız', [
                        'status' => is_array($data) ? ($data['status'] ?? null) : null,
                        'http' => $response->status(),
                    ]);

                    continue;
                }

                $rows = is_array($data['array'] ?? null) ? $data['array'] : [];

                foreach ($rows as $row) {
                    if (! is_array($row) || count($row) < 4) {
                        continue;
                    }

                    $id = (string) $row[0];
                    $code = (int) $row[3];
                    $out[$id] = $this->mapReportStatus($id, $code);
                }
            } catch (\Throwable $exception) {
                Log::error('Texcell getreport hatası', ['message' => $exception->getMessage()]);
            }
        }

        return $out;
    }

    /**
     * Gelen SMS'leri çeker (/getsms). İçerik base64 + utf-8.
     *
     * @return list<array{id: string, number: string, time: string, content: string}>
     */
    public function fetchInboundSms(?int $startTime = null): array
    {
        if ($this->account() === '' || $this->password() === '') {
            return [];
        }

        try {
            $query = $this->authQuery();
            if ($startTime !== null) {
                $query['start_time'] = $startTime;
            }

            $response = Http::timeout(30)
                ->acceptJson()
                ->withHeaders(['Content-Type' => 'application/json;charset=utf-8'])
                ->get($this->endpoint('getsms'), $query);

            $data = $response->json();

            if (! $response->successful() || ! is_array($data) || (int) ($data['status'] ?? -1) !== 0) {
                return [];
            }

            $rows = is_array($data['array'] ?? null) ? $data['array'] : [];
            $messages = [];

            foreach ($rows as $row) {
                if (! is_array($row) || count($row) < 4) {
                    continue;
                }

                $encoded = (string) $row[3];
                $decoded = base64_decode($encoded, true);
                $content = $decoded !== false ? $decoded : $encoded;

                $messages[] = [
                    'id' => (string) $row[0],
                    'number' => (string) $row[1],
                    'time' => (string) $row[2],
                    'content' => mb_convert_encoding($content, 'UTF-8', 'UTF-8'),
                ];
            }

            return $messages;
        } catch (\Throwable $exception) {
            Log::error('Texcell getsms hatası', ['message' => $exception->getMessage()]);

            return [];
        }
    }

    /**
     * @param  list<SmsSendRequest>  $requests
     * @return list<SmsSendResult>
     */
    private function sendChunk(array $requests, string $sender, string $message): array
    {
        $numbers = array_map(
            fn (SmsSendRequest $request) => $this->internationalNumber($request->to),
            $requests
        );

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
                fn () => $this->failure('Texcell: Telefon numarası geçersiz.'),
                $requests
            );
        }

        $payload = array_merge($this->authBody(), [
            'smstype' => 0,
            'numbers' => implode(',', $uniqueNumbers),
            'content' => $message,
        ]);

        if ($sender !== '') {
            $payload['sender'] = $sender;
        }

        try {
            $response = Http::timeout(45)
                ->acceptJson()
                ->asJson()
                ->withHeaders(['Content-Type' => 'application/json;charset=utf-8'])
                ->post($this->endpoint('sendsms'), $payload);

            $data = $response->json();

            if (! is_array($data)) {
                $failure = $this->failure("Texcell HTTP hatası: {$response->status()}");

                return array_fill(0, count($requests), $failure);
            }

            $status = (int) ($data['status'] ?? -99);

            if ($status !== 0) {
                $failure = $this->failure($this->statusMessage($status));

                return array_fill(0, count($requests), $failure);
            }

            $resultByUniqueIndex = [];
            $rows = is_array($data['array'] ?? null) ? $data['array'] : [];

            // array: [[number, id], ...]
            $idByNumber = [];
            foreach ($rows as $row) {
                if (! is_array($row) || count($row) < 2) {
                    continue;
                }
                $idByNumber[(string) $row[0]] = (string) $row[1];
            }

            foreach ($uniqueNumbers as $index => $number) {
                if (! isset($idByNumber[$number])) {
                    $resultByUniqueIndex[$index] = $this->failure('Texcell: Numara gönderim yanıtında yok.');

                    continue;
                }

                $resultByUniqueIndex[$index] = new SmsSendResult(
                    success: true,
                    messageId: $idByNumber[$number],
                    status: 'sent',
                );
            }

            return array_map(function (SmsSendRequest $request, int $offset) use ($numbers, $uniqueIndexByNumber, $resultByUniqueIndex): SmsSendResult {
                $number = $numbers[$offset];

                if ($number === '' || ! isset($uniqueIndexByNumber[$number])) {
                    return $this->failure('Texcell: Telefon numarası geçersiz.');
                }

                return $resultByUniqueIndex[$uniqueIndexByNumber[$number]]
                    ?? $this->failure('Texcell: Gönderim yanıtı eksik.');
            }, $requests, array_keys($requests));
        } catch (ConnectionException $exception) {
            Log::error('Texcell bağlantı hatası', ['message' => $exception->getMessage()]);
            $failure = $this->failure('Texcell bağlantı hatası: '.$exception->getMessage());

            return array_fill(0, count($requests), $failure);
        } catch (\Throwable $exception) {
            Log::error('Texcell gönderim hatası', ['message' => $exception->getMessage()]);
            $failure = $this->failure('Texcell gönderim hatası: '.$exception->getMessage());

            return array_fill(0, count($requests), $failure);
        }
    }

    private function mapReportStatus(string $messageId, int $code): SmsSendResult
    {
        // Send: 0 success, 1 unsent, 2 sending; Deliver: 3 success, 2 fail, 4 timeout
        return match (true) {
            $code === 0, $code === 3 => new SmsSendResult(
                success: true,
                messageId: $messageId,
                status: $code === 3 ? 'delivered' : 'sent',
            ),
            $code === 1, $code === 2 => new SmsSendResult(
                success: true,
                messageId: $messageId,
                status: 'pending',
            ),
            default => new SmsSendResult(
                success: false,
                messageId: $messageId,
                status: 'failed',
                errorMessage: $this->reportFailureMessage($code),
            ),
        };
    }

    private function reportFailureMessage(int $code): string
    {
        return match ($code) {
            4 => 'Texcell: Teslim zaman aşımı.',
            1001 => 'Texcell: NoRoute',
            1002 => 'Texcell: NoChannel',
            1003 => 'Texcell: Bakiye yetersiz',
            1004 => 'Texcell: Bilinmeyen hata',
            1005 => 'Texcell: Gönderim reddedildi',
            1006 => 'Texcell: Gönderim zaman aşımı',
            1007 => 'Texcell: Sunucu zaman aşımı',
            1008 => 'Texcell: Supplier MCC/MNC limiti',
            1009 => 'Texcell: Consumer MCC/MNC limiti',
            1010 => 'Texcell: Tedarikçi yok',
            1011 => 'Texcell: Kara liste numarası',
            1012 => 'Texcell: Hassas kelime',
            1013 => 'Texcell: Günlük limit',
            1014 => 'Texcell: Destination MCC/MNC limiti',
            1016 => 'Texcell: SMS şablon limiti',
            1017 => 'Texcell: Tedarikçi bakiyesi yetersiz',
            1018 => 'Texcell: Kullanıcı kâr limiti',
            1019 => 'Texcell: Kanal kâr limiti',
            1020 => 'Texcell: MCC numara uzunluk limiti',
            1021 => 'Texcell: Job bulunamadı',
            1022 => 'Texcell: Çin SMS limiti',
            1023 => 'Texcell: Route MCC/MNC limiti',
            default => "Texcell rapor hata kodu: {$code}",
        };
    }

    private function statusMessage(int $status): string
    {
        return match ($status) {
            -1 => 'Texcell: Kimlik doğrulama hatası.',
            -2 => 'Texcell: IP erişim kısıtı (whitelist).',
            -3 => 'Texcell: Mesaj hassas karakter içeriyor.',
            -4 => 'Texcell: Mesaj içeriği boş.',
            -5 => 'Texcell: Mesaj çok uzun.',
            -6 => 'Texcell: Şablon SMS değil.',
            -7 => 'Texcell: Numara limiti aşıldı.',
            -8 => 'Texcell: Numara boş.',
            -9 => 'Texcell: Geçersiz numara.',
            -10 => 'Texcell: Kanal bakiyesi yetersiz.',
            -11 => 'Texcell: Zamanlama hatalı.',
            -12 => 'Texcell: Platform toplu gönderim hatası.',
            -13 => 'Texcell: Kullanıcı kilitli.',
            -14 => 'Texcell: Numara kaynağı hatalı.',
            -15 => 'Texcell: Görev adı hatalı.',
            -16 => 'Texcell: Görev tipi hatalı.',
            -17 => 'Texcell: Diğer hata.',
            default => "Texcell hata kodu: {$status}",
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function authQuery(): array
    {
        $query = [
            'account' => $this->account(),
            'password' => $this->resolvedPassword(),
            'version' => $this->config('version', '1.0'),
        ];

        if ($this->encryptionEnabled()) {
            $query['seq'] = $this->nextSeq();
            $query['time'] = time();
            $query['password'] = $this->encryptedPassword((int) $query['seq'], (int) $query['time']);
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function authBody(): array
    {
        return $this->authQuery();
    }

    private function encryptionEnabled(): bool
    {
        return trim((string) $this->config('encryption_key', config('sms.texcell.encryption_key'))) !== '';
    }

    private function encryptedPassword(int $seq, int $time): string
    {
        $key = trim((string) $this->config('encryption_key', config('sms.texcell.encryption_key')));

        return md5($this->account().$this->password().$seq.$time.$key);
    }

    private function nextSeq(): int
    {
        $seq = (int) $this->config('seq', 1);

        return max(1, $seq);
    }

    private function account(): string
    {
        return trim((string) $this->config('account', config('sms.texcell.account')));
    }

    private function password(): string
    {
        return (string) $this->config('password', config('sms.texcell.password'));
    }

    private function resolvedPassword(): string
    {
        return $this->password();
    }

    private function resolveSender(?string $senderId): string
    {
        return trim((string) ($senderId ?: $this->config('sender', config('sms.texcell.sender', ''))));
    }

    private function endpoint(string $path): string
    {
        $base = trim((string) $this->config('base_url', config('sms.texcell.base_url', self::DEFAULT_BASE_URL)));

        if ($base === '') {
            $base = self::DEFAULT_BASE_URL;
        }

        return rtrim($base, '/').'/'.ltrim($path, '/');
    }

    /**
     * Uluslararası format: 905XXXXXXXXX (+ / 00 olmadan).
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

    private function failure(string $message): SmsSendResult
    {
        return new SmsSendResult(success: false, errorMessage: $message);
    }
}
