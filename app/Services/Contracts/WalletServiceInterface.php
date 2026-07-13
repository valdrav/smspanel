<?php

namespace App\Services\Contracts;

use App\DTOs\Wallet\CreditWalletData;
use App\Models\Organization;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;

/**
 * Cüzdan işlem servis sözleşmesi.
 */
interface WalletServiceInterface
{
    /**
     * Kullanıcının kullanılabilir bakiyesini döndürür.
     */
    public function getAvailableBalance(User $user): float;

    /**
     * Bakiye düşümü yapar.
     */
    public function debit(User $user, float $amount, string $description, ?Model $reference = null): WalletTransaction;

    /**
     * Bakiye iadesi yapar.
     */
    public function refund(User $user, float $amount, string $description, ?Model $reference = null): WalletTransaction;

    /**
     * Kullanıcıya kişisel SMS hakkı yükler.
     */
    public function creditUser(User $user, float $amount, string $description, ?Model $reference = null): WalletTransaction;

    /**
     * Kullanıcının kişisel SMS hakkını döndürür.
     */
    public function getPersonalBalance(User $user): float;

    /**
     * Kullanıcının kullanılabilir bakiyesine (org veya kişisel) SMS hakkı yükler.
     */
    public function creditAvailableBalance(User $user, float $amount, string $description, ?Model $reference = null): WalletTransaction;

    /**
     * Organizasyona bakiye yükler.
     */
    public function creditOrganization(Organization $organization, CreditWalletData $data, User $performedBy): WalletTransaction;
}
