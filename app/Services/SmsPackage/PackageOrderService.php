<?php

namespace App\Services\SmsPackage;

use App\Enums\PackageOrderStatus;
use App\Exceptions\BusinessException;
use App\Models\PackageOrder;
use App\Models\SmsPackage;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\WalletServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PackageOrderService
{
    public function __construct(
        private readonly WalletServiceInterface $walletService,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function request(User $user, SmsPackage $package, ?string $note = null): PackageOrder
    {
        if (! $package->is_active || ! $package->is_public) {
            throw new BusinessException('Bu paket şu an satın alınamaz.');
        }

        $pending = PackageOrder::where('user_id', $user->id)
            ->where('sms_package_id', $package->id)
            ->where('status', PackageOrderStatus::Pending)
            ->exists();

        if ($pending) {
            throw new BusinessException('Bu paket için zaten bekleyen bir talebiniz var.');
        }

        return PackageOrder::create([
            'user_id' => $user->id,
            'sms_package_id' => $package->id,
            'status' => PackageOrderStatus::Pending,
            'user_note' => $note,
        ]);
    }

    public function listForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return PackageOrder::query()
            ->with('smsPackage')
            ->where('user_id', $user->id)
            ->latest('id')
            ->paginate($perPage);
    }

    public function listAdmin(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = PackageOrder::query()->with(['user', 'smsPackage', 'processor']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function approve(PackageOrder $order, User $processor, ?string $adminNote = null): PackageOrder
    {
        if ($order->status !== PackageOrderStatus::Pending) {
            throw new BusinessException('Yalnızca bekleyen talepler onaylanabilir.');
        }

        return DB::transaction(function () use ($order, $processor, $adminNote): PackageOrder {
            $order->load('smsPackage');

            $this->walletService->creditAvailableBalance(
                $this->userRepository->findByIdOrFail($order->user_id),
                (float) $order->smsPackage->sms_amount,
                "Paket satın alma onayı: {$order->smsPackage->name}",
                $order,
            );

            $order->update([
                'status' => PackageOrderStatus::Approved,
                'admin_note' => $adminNote,
                'processed_by' => $processor->id,
                'processed_at' => now(),
            ]);

            return $order->fresh(['user', 'smsPackage']);
        });
    }

    public function reject(PackageOrder $order, User $processor, ?string $adminNote = null): PackageOrder
    {
        if ($order->status !== PackageOrderStatus::Pending) {
            throw new BusinessException('Yalnızca bekleyen talepler reddedilebilir.');
        }

        $order->update([
            'status' => PackageOrderStatus::Rejected,
            'admin_note' => $adminNote,
            'processed_by' => $processor->id,
            'processed_at' => now(),
        ]);

        return $order->fresh(['user', 'smsPackage']);
    }

    /**
     * Paketi doğrudan kullanıcıya dağıtır (talep beklemeden onaylı yükleme).
     */
    public function distribute(User $target, SmsPackage $package, User $processor, ?string $adminNote = null): PackageOrder
    {
        if (! $package->is_active) {
            throw new BusinessException('Pasif paket dağıtılamaz.');
        }

        return DB::transaction(function () use ($target, $package, $processor, $adminNote): PackageOrder {
            $order = PackageOrder::create([
                'user_id' => $target->id,
                'sms_package_id' => $package->id,
                'status' => PackageOrderStatus::Approved,
                'admin_note' => $adminNote ?: 'Yönetici tarafından paket dağıtımı',
                'processed_by' => $processor->id,
                'processed_at' => now(),
            ]);

            $this->walletService->creditAvailableBalance(
                $this->userRepository->findByIdOrFail($target->id),
                (float) $package->sms_amount,
                "Paket dağıtımı: {$package->name}",
                $order,
            );

            return $order->fresh(['user', 'smsPackage']);
        });
    }
}
