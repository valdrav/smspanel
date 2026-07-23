<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Varsayılan SMS Sağlayıcı
    |--------------------------------------------------------------------------
    |
    | Sistemde kullanılacak varsayılan SMS sağlayıcı kod adı.
    |
    */

    'default_provider' => env('SMS_DEFAULT_PROVIDER', 'texcell'),

    'drivers' => [
        'mock' => \App\Sms\Providers\MockSmsProvider::class,
        'netgsm' => \App\Sms\Providers\NetgsmSmsProvider::class,
        'iletimerkezi' => \App\Sms\Providers\IletiMerkeziSmsProvider::class,
        'texcell' => \App\Sms\Providers\TexcellEimsSmsProvider::class,
    ],

    'default_sender_id' => env('SMS_DEFAULT_SENDER_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Texcell / EJOIN EIMS HTTP API v3.5
    |--------------------------------------------------------------------------
    |
    | Charge Rule: Send billing (gönderim anında ücretlendirme).
    | Base: http://IP:20003  (getbalance / sendsms)
    | sender PDF'de opsiyonel — boş bırakılırsa API'ye gönderilmez.
    |
    */

    'texcell' => [
        /*
         * Boş .env değerlerinde üretim hesabı kullanılır.
         * phpunit APP_ENV=testing iken boş kalır (test izolasyonu).
         */
        'account' => (($__texcellAccount = env('TEXCELL_ACCOUNT')) !== null && $__texcellAccount !== '')
            ? $__texcellAccount
            : (env('APP_ENV') === 'testing' ? '' : 'CTU780'),
        'password' => (($__texcellPassword = env('TEXCELL_PASSWORD')) !== null && $__texcellPassword !== '')
            ? $__texcellPassword
            : (env('APP_ENV') === 'testing' ? '' : 'EZM9lh3MVh1i'),
        'base_url' => env('TEXCELL_BASE_URL', 'http://38.150.64.36:20003'),
        'sender' => env('TEXCELL_SENDER', ''),
        'encryption_key' => env('TEXCELL_ENCRYPTION_KEY', ''),
        'webhook_token' => env('TEXCELL_WEBHOOK_TOKEN'),
        'provider_code' => env('TEXCELL_PROVIDER_CODE', 'texcell'),
        'sync_balance_to_admin' => env('TEXCELL_SYNC_BALANCE_TO_ADMIN', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Kredi Modeli
    |--------------------------------------------------------------------------
    |
    | Her segment = 1 SMS hakkı (adet). Bakiye para birimi değil, SMS adedi
    | olarak tutulur ve gönderimde segment sayısı kadar düşülür.
    |
    */

    'queue' => env('SMS_QUEUE', 'sms'),

    /*
    | auto: <= sync_threshold ise anında gönder (worker gerekmez)
    | sync: her zaman anında
    | queue: her zaman kuyruk (worker zorunlu)
    */
    'dispatch_mode' => env('SMS_DISPATCH_MODE', 'auto'),
    'sync_threshold' => (int) env('SMS_SYNC_THRESHOLD', 300),

    'batch_size' => (int) env('SMS_BATCH_SIZE', 1000),

    'campaign' => [
        'max_recipients' => (int) env('SMS_CAMPAIGN_MAX_RECIPIENTS', 200000),
        'chunk_size' => (int) env('SMS_CAMPAIGN_CHUNK_SIZE', 500),
        'chunk_delay_seconds' => (int) env('SMS_CAMPAIGN_CHUNK_DELAY', 1),
    ],

    'rate_limit' => [
        'max_attempts' => (int) env('SMS_RATE_LIMIT', 60),
        'decay_minutes' => 1,
    ],

];
