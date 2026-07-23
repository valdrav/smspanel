<?php

namespace App\Sms\Providers;

use App\Sms\DTOs\SmsBalanceResult;
use App\Sms\DTOs\SmsSendRequest;
use App\Sms\DTOs\SmsSendResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Texcell / EJOIN EIMS HTTP API v3.5 entegrasyonu.
 *
 * PDF: sender opsiyonel; Content-Type application/json;charset=utf-8;
 * POST /sendsms body JSON; GET /getbalance?account=&password=
 * Charge Rule: Send billing.
 *
 * @see TEXCELL EIMS HTTP API_V3.5 EN.pdf
 */
class TexcellEimsSmsProvider extends AbstractSmsProvider
{
    private const DEFAULT_BASE_URL = 'http://38.150.64.36:20003';

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
                fn () => $this->failure('Texcell account/password yapılandırılmamış. SMS Sağlayıcılar → Texcell kaydına girin.'),
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
                errorMessage: 'Texcell account/password yapılandırılmamış.',
            );
        }

        try {
            // PDF: GET /getbalance?account=***&password=*** (body yok, Content-Type gerekmez)
            $response = Http::timeout(30)
                ->acceptJson()
                ->get($this->endpoint('getbalance'), $this->authParams());
            $data = $this->json($response);

            if ($data === null) {
                return new SmsBalanceResult(
                    success: false,
                    errorMessage: "Texcell bakiye HTTP hatası: {$response->status()} — ".$response->body(),
                );
            }

            $status = (int) ($data['status'] ?? -99);

            if ($status !== 0) {
                return new SmsBalanceResult(
                    success: false,
                    errorMessage: $this->apiErrorMessage($status, $data),
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
     * @param  list<string|int>  $ids
     * @return array<string, SmsSendResult>
     */
    public function getReports(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map(
            static fn ($id): string => trim((string) $id),
            $ids
        ), static fn (string $id): bool => $id !== '')));

        if ($ids === [] || $this->account() === '' || $this->password() === '') {
            return [];
        }

        $out = [];

        foreach (array_chunk($ids, self::MAX_REPORT_IDS) as $chunk) {
            try {
                $response = Http::timeout(30)
                    ->acceptJson()
                    ->get($this->endpoint('getreport'), array_merge($this->authParams(), [
                        'ids' => implode(',', $chunk),
                    ]));
                $data = $this->json($response);

                if ($data === null || (int) ($data['status'] ?? -1) !== 0) {
                    continue;
                }

                foreach ((array) ($data['array'] ?? []) as $row) {
                    if (! is_array($row) || count($row) < 4) {
                        continue;
                    }
                    $id = (string) $row[0];
                    $out[$id] = $this->mapReportStatus($id, (int) $row[3]);
                }
            } catch (\Throwable $exception) {
                Log::error('Texcell getreport hatası', ['message' => $exception->getMessage()]);
            }
        }

        return $out;
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

        // PDF POST body örneği: account, password, content, smstype, numbers — sender opsiyonel.
        $payload = array_merge($this->authParams(), [
            'smstype' => 0,
            'numbers' => implode(',', $uniqueNumbers),
            'content' => $message,
        ]);

        // Yalnızca Texcell panelinde tanımlı gönderici varsa gönder; SMSPANEL vb. uydurma başlık gönderme.
        if ($sender !== '') {
            $payload['sender'] = $sender;
        }

        try {
            $response = Http::timeout(45)
                ->acceptJson()
                ->withBody(json_encode($payload, JSON_UNESCAPED_UNICODE), 'application/json;charset=utf-8')
                ->post($this->endpoint('sendsms'));

            $data = $this->json($response);

            if ($data === null) {
                $failure = $this->failure("Texcell HTTP hatası: {$response->status()} — ".$response->body());

                return array_fill(0, count($requests), $failure);
            }

            $status = (int) ($data['status'] ?? -99);

            if ($status !== 0) {
                Log::warning('Texcell sendsms reddedildi', [
                    'status' => $status,
                    'response' => $data,
                    'account' => $this->account(),
                    'numbers' => $uniqueNumbers,
                    'has_sender' => $sender !== '',
                ]);

                $failure = $this->failure($this->apiErrorMessage($status, $data));

                return array_fill(0, count($requests), $failure);
            }

            $idByNumber = [];
            foreach ((array) ($data['array'] ?? []) as $row) {
                if (! is_array($row) || count($row) < 2) {
                    continue;
                }
                $idByNumber[$this->digits((string) $row[0])] = (string) $row[1];
            }

            $resultByUniqueIndex = [];
            foreach ($uniqueNumbers as $index => $number) {
                $messageId = $this->matchMessageId($number, $idByNumber);

                if ($messageId === null) {
                    // status=0 ama eşleşme yoksa yine başarılı say (PDF: success sayacı)
                    if ((int) ($data['success'] ?? 0) > 0 && $idByNumber !== []) {
                        $messageId = (string) reset($idByNumber);
                    }
                }

                if ($messageId === null) {
                    $resultByUniqueIndex[$index] = $this->failure('Texcell: Numara gönderim yanıtında yok.');

                    continue;
                }

                $resultByUniqueIndex[$index] = new SmsSendResult(
                    success: true,
                    messageId: $messageId,
                    status: 'sent',
                );
            }

            return array_map(function (int $offset) use ($numbers, $uniqueIndexByNumber, $resultByUniqueIndex): SmsSendResult {
                $number = $numbers[$offset];

                if ($number === '' || ! isset($uniqueIndexByNumber[$number])) {
                    return $this->failure('Texcell: Telefon numarası geçersiz.');
                }

                return $resultByUniqueIndex[$uniqueIndexByNumber[$number]]
                    ?? $this->failure('Texcell: Gönderim yanıtı eksik.');
            }, array_keys($requests));
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

    /**
     * @param  array<string, string>  $idByNumber
     */
    private function matchMessageId(string $number, array $idByNumber): ?string
    {
        $digits = $this->digits($number);

        if (isset($idByNumber[$digits])) {
            return $idByNumber[$digits];
        }

        foreach ($idByNumber as $returned => $id) {
            if ($returned === $digits || str_ends_with($digits, $returned) || str_ends_with($returned, $digits)) {
                return $id;
            }
        }

        return null;
    }

    private function mapReportStatus(string $messageId, int $code): SmsSendResult
    {
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
            1011 => 'Texcell: Kara liste numarası',
            1012 => 'Texcell: Hassas kelime',
            1013 => 'Texcell: Günlük limit',
            default => "Texcell rapor hata kodu: {$code}",
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function apiErrorMessage(int $status, array $data): string
    {
        $detail = trim((string) ($data['reason'] ?? $data['desc'] ?? $data['message'] ?? ''));
        $base = $this->statusMessage($status);

        if ($detail !== '' && ! str_contains(strtolower($base), strtolower($detail))) {
            return "{$base} ({$detail})";
        }

        return $base;
    }

    private function statusMessage(int $status): string
    {
        return match ($status) {
            -1 => 'Texcell: Kimlik doğrulama hatası — account/password veya IP whitelist kontrol edin.',
            -2 => 'Texcell: IP erişim kısıtı (sunucu IP’nizi whitelist’e ekleyin).',
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
            default => "Texcell hata kodu: {$status}",
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function authParams(): array
    {
        // PDF zorunlu: account + password. version opsiyonel (default 1.0) — göndermiyoruz.
        $params = [
            'account' => $this->account(),
            'password' => $this->password(),
        ];

        if ($this->encryptionEnabled()) {
            $seq = max(1, (int) $this->config('seq', 1));
            $time = time();
            $params['seq'] = $seq;
            $params['time'] = $time;
            $params['password'] = md5($this->account().$this->password().$seq.$time.$this->encryptionKey());
        }

        return $params;
    }

    private function encryptionEnabled(): bool
    {
        return $this->encryptionKey() !== '';
    }

    private function encryptionKey(): string
    {
        return trim((string) $this->config('encryption_key', config('sms.texcell.encryption_key')));
    }

    private function account(): string
    {
        return trim((string) $this->config('account', config('sms.texcell.account')));
    }

    private function password(): string
    {
        return trim((string) $this->config('password', config('sms.texcell.password')));
    }

    /**
     * PDF: sender N (opsiyonel). Panel SMSPANEL’ini Texcell’e göndermeyiz.
     * Yalnızca istekte veya Texcell config.sender doluysa kullanılır.
     */
    private function resolveSender(?string $senderId): string
    {
        $fromRequest = trim((string) ($senderId ?? ''));
        $configured = trim((string) $this->config('sender', config('sms.texcell.sender', '')));

        // Panel varsayılanı SMSPANEL ise Texcell hesabına zorla gönderme.
        $ignored = ['SMSPANEL', 'smspanel'];

        if ($fromRequest !== '' && ! in_array($fromRequest, $ignored, true)) {
            return $fromRequest;
        }

        if ($configured !== '' && ! in_array($configured, $ignored, true)) {
            return $configured;
        }

        return '';
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
     * @return array<string, mixed>|null
     */
    private function json(Response $response): ?array
    {
        $data = $response->json();

        return is_array($data) ? $data : null;
    }

    private function internationalNumber(string $phone): string
    {
        $digits = $this->digits($phone);

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '5')) {
            return '90'.$digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '05')) {
            return '90'.substr($digits, 1);
        }

        return $digits;
    }

    private function digits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private function failure(string $message): SmsSendResult
    {
        return new SmsSendResult(success: false, errorMessage: $message);
    }
}
