<?php

namespace App\Services\Auth;

use App\DTOs\ActivityLog\CreateActivityLogData;
use App\DTOs\Auth\LoginData;
use App\Enums\ActivityAction;
use App\Enums\UserStatus;
use App\Exceptions\AuthenticationException;
use App\Exceptions\BusinessException;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\ActivityLogServiceInterface;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Kimlik doğrulama servis implementasyonu.
 */
class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly ActivityLogServiceInterface $activityLogService,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function login(LoginData $data, string $ipAddress, ?string $userAgent): User
    {
        $user = $this->userRepository->findByEmail($data->email);

        if (! $user || ! Auth::validate(['email' => $data->email, 'password' => $data->password])) {
            $this->activityLogService->record(new CreateActivityLogData(
                action: ActivityAction::LoginFailed,
                description: "Başarısız giriş denemesi: {$data->email}",
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            ));

            throw new AuthenticationException('E-posta veya şifre hatalı.');
        }

        if ($user->status !== UserStatus::Active) {
            throw new BusinessException('Hesabınız aktif değil. Lütfen yönetici ile iletişime geçin.');
        }

        return DB::transaction(function () use ($user, $data, $ipAddress, $userAgent): User {
            Auth::login($user, $data->remember);

            $user->update(['last_login_at' => now()]);

            $this->activityLogService->record(new CreateActivityLogData(
                action: ActivityAction::Login,
                description: "Kullanıcı giriş yaptı: {$user->email}",
                userId: $user->id,
                subjectType: User::class,
                subjectId: $user->id,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            ));

            return $user->fresh();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function logout(): void
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $this->activityLogService->record(new CreateActivityLogData(
                action: ActivityAction::Logout,
                description: "Kullanıcı çıkış yaptı: {$user->email}",
                userId: $user->id,
                subjectType: User::class,
                subjectId: $user->id,
                ipAddress: request()->ip(),
                userAgent: request()->userAgent(),
            ));
        }

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }
}
