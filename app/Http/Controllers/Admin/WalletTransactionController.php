<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Enums\WalletTransactionType;
use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\WalletTransactionRepositoryInterface;
use App\Services\Contracts\WalletServiceInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cüzdan işlemleri controller'ı.
 */
class WalletTransactionController extends Controller
{
    public function __construct(
        private readonly WalletServiceInterface $walletService,
        private readonly UserRepositoryInterface $userRepository,
        private readonly WalletTransactionRepositoryInterface $walletTransactionRepository,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', WalletTransaction::class);

        $user = auth()->user();
        $isSuperAdmin = $user->hasRole(RoleName::SuperAdmin->value);

        return view('admin.wallet.index', [
            'pageTitle' => 'Cüzdan İşlemleri',
            'transactions' => $this->walletTransactionRepository->paginateWithFilters(
                $user,
                filters: $request->only(['user_id', 'type', 'date_from', 'date_to']),
            ),
            'types' => WalletTransactionType::cases(),
            'balance' => $this->walletService->getPersonalBalance($user),
            'availableBalance' => $this->walletService->getAvailableBalance($user),
            'filters' => $request->only(['user_id', 'type', 'date_from', 'date_to']),
            'isSuperAdmin' => $isSuperAdmin,
            'users' => $isSuperAdmin ? $this->userRepository->all() : collect(),
        ]);
    }
}
