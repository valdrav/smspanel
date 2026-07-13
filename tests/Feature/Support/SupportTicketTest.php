<?php

namespace Tests\Feature\Support;

use App\Enums\RoleName;
use App\Enums\TicketStatus;
use App\Enums\UserStatus;
use App\Models\SupportTicket;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_support_ticket(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $customer = User::factory()->create(['status' => UserStatus::Active->value]);
        $customer->assignRole(RoleName::Customer->value);

        $response = $this->actingAs($customer)->post(route('admin.support-tickets.store'), [
            'subject' => 'Bakiye yüklenmedi',
            'category' => 'billing',
            'priority' => 'normal',
            'body' => 'Ödeme yaptım ama bakiye gelmedi.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $customer->id,
            'subject' => 'Bakiye yüklenmedi',
            'status' => TicketStatus::Open->value,
        ]);
        $this->assertDatabaseCount('support_ticket_messages', 1);
    }

    public function test_super_admin_can_reply_and_update_any_ticket(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $customer = User::factory()->create(['status' => UserStatus::Active->value]);
        $customer->assignRole(RoleName::Customer->value);

        $superAdmin = User::factory()->create(['status' => UserStatus::Active->value]);
        $superAdmin->assignRole(RoleName::SuperAdmin->value);

        $ticket = SupportTicket::create([
            'ticket_number' => 'TKT-TEST001',
            'user_id' => $customer->id,
            'subject' => 'Test',
            'category' => 'general',
            'priority' => 'normal',
            'status' => TicketStatus::Open,
        ]);

        $this->actingAs($superAdmin)->post(route('admin.support-tickets.reply', $ticket), [
            'body' => 'İnceliyoruz',
        ])->assertRedirect();

        $this->actingAs($superAdmin)->put(route('admin.support-tickets.update', $ticket), [
            'status' => TicketStatus::InProgress->value,
            'priority' => 'high',
            'assigned_to' => $superAdmin->id,
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertEquals(TicketStatus::InProgress, $ticket->status);
        $this->assertEquals($superAdmin->id, $ticket->assigned_to);
    }

    public function test_admin_cannot_view_other_users_ticket(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $owner = User::factory()->create(['status' => UserStatus::Active->value]);
        $owner->assignRole(RoleName::Admin->value);

        $otherAdmin = User::factory()->create(['status' => UserStatus::Active->value]);
        $otherAdmin->assignRole(RoleName::Admin->value);

        $ticket = SupportTicket::create([
            'ticket_number' => 'TKT-ADMIN01',
            'user_id' => $owner->id,
            'subject' => 'Gizli admin talebi',
            'category' => 'general',
            'priority' => 'normal',
            'status' => TicketStatus::Open,
        ]);

        $this->actingAs($otherAdmin)->get(route('admin.support-tickets.show', $ticket))->assertStatus(403);
        $this->actingAs($otherAdmin)->get(route('admin.support-tickets.index'))->assertStatus(200)->assertDontSee('Gizli admin talebi');
    }

    public function test_admin_can_manage_own_ticket_only(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $admin = User::factory()->create(['status' => UserStatus::Active->value]);
        $admin->assignRole(RoleName::Admin->value);

        $ticket = SupportTicket::create([
            'ticket_number' => 'TKT-OWN001',
            'user_id' => $admin->id,
            'subject' => 'Kendi talebim',
            'category' => 'general',
            'priority' => 'normal',
            'status' => TicketStatus::Open,
        ]);

        $this->actingAs($admin)->get(route('admin.support-tickets.show', $ticket))->assertStatus(200);
        $this->actingAs($admin)->post(route('admin.support-tickets.reply', $ticket), [
            'body' => 'Ek bilgi',
        ])->assertRedirect();
        $this->actingAs($admin)->put(route('admin.support-tickets.update', $ticket), [
            'status' => TicketStatus::InProgress->value,
            'priority' => 'high',
            'assigned_to' => $admin->id,
        ])->assertStatus(403);
    }

    public function test_customer_cannot_view_other_users_ticket(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $customer = User::factory()->create(['status' => UserStatus::Active->value]);
        $customer->assignRole(RoleName::Customer->value);

        $other = User::factory()->create(['status' => UserStatus::Active->value]);
        $other->assignRole(RoleName::Customer->value);

        $ticket = SupportTicket::create([
            'ticket_number' => 'TKT-OTHER01',
            'user_id' => $other->id,
            'subject' => 'Gizli',
            'category' => 'general',
            'priority' => 'normal',
            'status' => TicketStatus::Open,
        ]);

        $this->actingAs($customer)->get(route('admin.support-tickets.show', $ticket))->assertStatus(403);
    }

    public function test_customer_can_create_ticket_with_attachment(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $customer = User::factory()->create(['status' => UserStatus::Active->value]);
        $customer->assignRole(RoleName::Customer->value);

        $file = \Illuminate\Http\UploadedFile::fake()->image('ekran.jpg');

        $response = $this->actingAs($customer)->post(route('admin.support-tickets.store'), [
            'subject' => 'Görsel ekli talep',
            'category' => 'technical',
            'priority' => 'normal',
            'body' => 'Sorun ekran görüntüsünde.',
            'attachments' => [$file],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('support_ticket_attachments', 1);
    }

    public function test_ticket_show_includes_status_tracking(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $customer = User::factory()->create(['status' => UserStatus::Active->value]);
        $customer->assignRole(RoleName::Customer->value);

        $this->actingAs($customer)->post(route('admin.support-tickets.store'), [
            'subject' => 'Durum testi',
            'category' => 'general',
            'priority' => 'normal',
            'body' => 'Test mesajı',
        ]);

        $ticket = SupportTicket::first();

        $response = $this->actingAs($customer)->get(route('admin.support-tickets.show', $ticket));

        $response->assertStatus(200);
        $response->assertSee('Durum Takibi');
        $response->assertSee('Talep oluşturuldu');
    }
}
