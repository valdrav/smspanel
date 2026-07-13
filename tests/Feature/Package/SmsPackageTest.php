<?php

namespace Tests\Feature\Package;

use App\Enums\PackageOrderStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\SmsPackage;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsPackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_and_publish_package(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $superAdmin = User::factory()->create(['status' => UserStatus::Active->value]);
        $superAdmin->assignRole(RoleName::SuperAdmin->value);

        $response = $this->actingAs($superAdmin)->post(route('admin.packages.store'), [
            'name' => 'Başlangıç Paketi',
            'description' => 'Küçük işletmeler için',
            'badge' => 'Popüler',
            'features' => "Anında yükleme\nDestek hattı",
            'theme' => 'emerald',
            'sms_amount' => 1000,
            'price' => 99.90,
            'is_active' => '1',
            'is_public' => '1',
            'is_featured' => '1',
            'sort_order' => 10,
        ]);

        $response->assertRedirect(route('admin.packages.index'));
        $this->assertDatabaseHas('sms_packages', [
            'name' => 'Başlangıç Paketi',
            'is_public' => true,
            'is_featured' => true,
            'theme' => 'emerald',
            'badge' => 'Popüler',
        ]);
    }

    public function test_customer_can_view_catalog_and_request_purchase(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $package = SmsPackage::create([
            'name' => 'Standart',
            'slug' => 'standart',
            'sms_amount' => 500,
            'is_active' => true,
            'is_public' => true,
            'sort_order' => 1,
        ]);

        $customer = User::factory()->create(['status' => UserStatus::Active->value, 'sms_balance' => 0]);
        $customer->assignRole(RoleName::Customer->value);

        $this->actingAs($customer)->get(route('admin.packages.catalog'))->assertStatus(200)->assertSee('Standart');

        $response = $this->actingAs($customer)->post(route('admin.packages.purchase', $package), [
            'user_note' => 'Havale yaptım',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('package_orders', [
            'user_id' => $customer->id,
            'sms_package_id' => $package->id,
            'status' => PackageOrderStatus::Pending->value,
        ]);
    }

    public function test_super_admin_can_approve_order_and_credit_user(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $package = SmsPackage::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'sms_amount' => 2000,
            'is_active' => true,
            'is_public' => true,
            'sort_order' => 1,
        ]);

        $customer = User::factory()->create(['status' => UserStatus::Active->value, 'sms_balance' => 100]);
        $customer->assignRole(RoleName::Customer->value);

        $superAdmin = User::factory()->create(['status' => UserStatus::Active->value]);
        $superAdmin->assignRole(RoleName::SuperAdmin->value);

        $order = \App\Models\PackageOrder::create([
            'user_id' => $customer->id,
            'sms_package_id' => $package->id,
            'status' => PackageOrderStatus::Pending,
        ]);

        $response = $this->actingAs($superAdmin)->post(route('admin.package-orders.approve', $order));

        $response->assertRedirect();
        $this->assertEquals(2100, (float) $customer->fresh()->sms_balance);
        $this->assertEquals(PackageOrderStatus::Approved, $order->fresh()->status);
    }

    public function test_customer_cannot_access_package_management(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $customer = User::factory()->create(['status' => UserStatus::Active->value]);
        $customer->assignRole(RoleName::Customer->value);

        $this->actingAs($customer)->get(route('admin.packages.index'))->assertStatus(403);
        $this->actingAs($customer)->get(route('admin.packages.catalog'))->assertStatus(200);
    }

    public function test_admin_can_purchase_but_cannot_manage_packages(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $package = SmsPackage::create([
            'name' => 'Admin Paket',
            'slug' => 'admin-paket',
            'sms_amount' => 750,
            'is_active' => true,
            'is_public' => true,
            'sort_order' => 1,
        ]);

        $admin = User::factory()->create(['status' => UserStatus::Active->value]);
        $admin->assignRole(RoleName::Admin->value);

        $this->actingAs($admin)->get(route('admin.packages.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.packages.catalog'))->assertOk()->assertSee('Admin Paket');

        $this->actingAs($admin)->post(route('admin.packages.purchase', $package), [
            'user_note' => 'Admin talep',
        ])->assertRedirect();

        $this->assertDatabaseHas('package_orders', [
            'user_id' => $admin->id,
            'sms_package_id' => $package->id,
            'status' => PackageOrderStatus::Pending->value,
        ]);
    }
}
