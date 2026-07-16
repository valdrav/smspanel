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
        'easysendsms' => \App\Sms\Providers\EasySendSmsProvider::class,
    ],

    'default_sender_id' => env('SMS_DEFAULT_SENDER_ID', 'SMSPANEL'),

    /*
    |--------------------------------------------------------------------------
    | EasySendSMS REST API v1
    |--------------------------------------------------------------------------
    |
    | @see https://www.easysendsms.com/rest-api
    | Base host: https://restapi.easysendsms.app
    | Send:  POST /v1/rest/sms/send   (header: apikey)
    | Balance: GET /v1/rest/sms/balance (header: APIKEY)
    | Max 30 recipients per send request.
    |
    */

    'easysendsms' => [
        'api_key' => env('EASYSENDSMS_API_KEY'),
        'sender_id' => env('EASYSENDSMS_SENDER_ID', env('SMS_DEFAULT_SENDER_ID', 'SMSPANEL')),
        'base_url' => env('EASYSENDSMS_BASE_URL', 'https://restapi.easysendsms.app/v1/rest'),
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
