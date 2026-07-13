<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use App\Repositories\Contracts\SmsMessageRepositoryInterface;
use App\Repositories\Contracts\SmsProviderRepositoryInterface;
use App\Repositories\Eloquent\SmsProviderRepository;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\UserSenderNumberRepositoryInterface;
use App\Repositories\Contracts\WalletTransactionRepositoryInterface;
use App\Repositories\Eloquent\ActivityLogRepository;
use App\Repositories\Eloquent\OrganizationRepository;
use App\Repositories\Eloquent\SmsMessageRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Eloquent\UserSenderNumberRepository;
use App\Repositories\Eloquent\WalletTransactionRepository;
use App\Services\ActivityLog\ActivityLogListingService;
use App\Services\ActivityLog\ActivityLogService;
use App\Services\Auth\AuthService;
use App\Services\Contracts\ActivityLogListingServiceInterface;
use App\Services\Contracts\ActivityLogServiceInterface;
use App\Services\Contracts\AuthServiceInterface;
use App\Services\Contracts\OrganizationServiceInterface;
use App\Services\Contracts\SmsHistoryServiceInterface;
use App\Services\Contracts\SmsSendServiceInterface;
use App\Services\Contracts\SettingServiceInterface;
use App\Services\Contracts\UserServiceInterface;
use App\Services\Setting\SettingService;
use App\Services\Contracts\UserSenderNumberServiceInterface;
use App\Services\Contracts\WalletServiceInterface;
use App\Services\Organization\OrganizationService;
use App\Services\Contracts\SmsProviderServiceInterface;
use App\Services\Contracts\SmsReportServiceInterface;
use App\Services\SmsProvider\SmsProviderService;
use App\Services\Sms\SmsHistoryService;
use App\Services\Sms\SmsReportService;
use App\Sms\SmsProviderFactory;
use App\Services\Sms\SmsSendService;
use App\Services\User\UserService;
use App\Services\UserSenderNumber\UserSenderNumberService;
use App\Services\Wallet\WalletService;
use App\Sms\Support\PhoneNormalizer;
use App\Sms\Support\SmsSegmentCalculator;
use Illuminate\Support\ServiceProvider;

/**
 * Repository ve Service katmanı bağımlılık enjeksiyonu.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Servis kayıtları.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ActivityLogRepositoryInterface::class, ActivityLogRepository::class);
        $this->app->bind(SmsMessageRepositoryInterface::class, SmsMessageRepository::class);
        $this->app->bind(OrganizationRepositoryInterface::class, OrganizationRepository::class);
        $this->app->bind(WalletTransactionRepositoryInterface::class, WalletTransactionRepository::class);
        $this->app->bind(SmsProviderRepositoryInterface::class, SmsProviderRepository::class);
        $this->app->bind(UserSenderNumberRepositoryInterface::class, UserSenderNumberRepository::class);

        $this->app->bind(SmsProviderServiceInterface::class, SmsProviderService::class);
        $this->app->bind(SmsReportServiceInterface::class, SmsReportService::class);
        $this->app->bind(UserSenderNumberServiceInterface::class, UserSenderNumberService::class);
        $this->app->singleton(SmsProviderFactory::class);

        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(SettingServiceInterface::class, SettingService::class);

        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(ActivityLogServiceInterface::class, ActivityLogService::class);
        $this->app->bind(ActivityLogListingServiceInterface::class, ActivityLogListingService::class);
        $this->app->bind(SmsSendServiceInterface::class, SmsSendService::class);
        $this->app->bind(SmsHistoryServiceInterface::class, SmsHistoryService::class);
        $this->app->bind(OrganizationServiceInterface::class, OrganizationService::class);
        $this->app->bind(WalletServiceInterface::class, WalletService::class);

        $this->app->singleton(SmsSegmentCalculator::class);
        $this->app->singleton(PhoneNormalizer::class);
    }

    /**
     * Bootstrap işlemleri.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
    }
}
