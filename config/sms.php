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

    'default_provider' => env('SMS_DEFAULT_PROVIDER', 'mock'),

    'drivers' => [
        'mock' => \App\Sms\Providers\MockSmsProvider::class,
        'netgsm' => \App\Sms\Providers\NetgsmSmsProvider::class,
        'iletimerkezi' => \App\Sms\Providers\IletiMerkeziSmsProvider::class,
    ],

    'default_sender_id' => env('SMS_DEFAULT_SENDER_ID', 'SMSPANEL'),

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

    'batch_size' => (int) env('SMS_BATCH_SIZE', 100),

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
