<?php

use App\Providers\AppServiceProvider;
use App\Providers\RepositoryServiceProvider;
use App\Providers\SmsServiceProvider;

return [
    AppServiceProvider::class,
    RepositoryServiceProvider::class,
    SmsServiceProvider::class,
];
