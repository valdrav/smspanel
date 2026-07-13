<?php

namespace Tests\Feature\Wallet;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_wallet_transactions(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $organization = Organization::factory()->create(['sms_balance' => 200]);
        $customer = User::factory()->create([
            'organization_id' => $organization->id,
            'status' => UserStatus::Active->value,
        ]);
        $customer->assignRole(RoleName::Customer->value);

        $response = $this->actingAs($customer)->get(route('admin.wallet.index'));

        $response->assertStatus(200);
        $response->assertSee('Cüzdan İşlemleri');
    }
}
