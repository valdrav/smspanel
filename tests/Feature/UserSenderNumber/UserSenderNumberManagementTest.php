<?php

namespace Tests\Feature\UserSenderNumber;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\UserSenderNumber;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSenderNumberManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->admin = User::factory()->create(['status' => UserStatus::Active->value]);
        $this->admin->assignRole(RoleName::SuperAdmin->value);
    }

    public function test_admin_can_create_user_sender_number(): void
    {
        $targetUser = User::factory()->create(['status' => UserStatus::Active->value]);

        $response = $this->actingAs($this->admin)->post(route('admin.user-sender-numbers.store'), [
            'user_id' => $targetUser->id,
            'sender_id' => 'TEST123',
            'label' => 'Test hattı',
            'is_default' => true,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.user-sender-numbers.index'));
        $this->assertDatabaseHas('user_sender_numbers', [
            'user_id' => $targetUser->id,
            'sender_id' => 'TEST123',
        ]);
    }

    public function test_customer_can_only_view_own_sender_numbers(): void
    {
        $customer = User::factory()->create(['status' => UserStatus::Active->value]);
        $customer->assignRole(RoleName::Customer->value);

        $otherUser = User::factory()->create(['status' => UserStatus::Active->value]);

        UserSenderNumber::factory()->create(['user_id' => $customer->id, 'sender_id' => 'MINE']);
        UserSenderNumber::factory()->create(['user_id' => $otherUser->id, 'sender_id' => 'OTHER']);

        $response = $this->actingAs($customer)->get(route('admin.user-sender-numbers.index'));

        $response->assertStatus(200);
        $response->assertSee('MINE');
        $response->assertDontSee('OTHER');
    }

    public function test_sms_send_uses_assigned_sender_number(): void
    {
        $customer = User::factory()->create([
            'status' => UserStatus::Active->value,
            'sms_balance' => 100,
        ]);
        $customer->assignRole(RoleName::Customer->value);

        UserSenderNumber::factory()->create([
            'user_id' => $customer->id,
            'sender_id' => 'ASSIGNED',
            'is_default' => true,
        ]);

        $response = $this->actingAs($customer)->post(route('admin.sms.send.store'), [
            'recipient' => '5551234567',
            'message' => 'Test mesaj',
            'sender_id' => 'ASSIGNED',
        ]);

        $response->assertRedirect(route('admin.sms.history.index'));
        $this->assertDatabaseHas('sms_messages', [
            'user_id' => $customer->id,
            'sender_id' => 'ASSIGNED',
        ]);
    }

    public function test_sms_send_rejects_unassigned_sender_number(): void
    {
        $customer = User::factory()->create([
            'status' => UserStatus::Active->value,
            'sms_balance' => 100,
        ]);
        $customer->assignRole(RoleName::Customer->value);

        UserSenderNumber::factory()->create([
            'user_id' => $customer->id,
            'sender_id' => 'ALLOWED',
            'is_default' => true,
        ]);

        $response = $this->actingAs($customer)->post(route('admin.sms.send.store'), [
            'recipient' => '5551234567',
            'message' => 'Test mesaj',
            'sender_id' => 'BLOCKED',
        ]);

        $response->assertSessionHas('error');
    }
}
