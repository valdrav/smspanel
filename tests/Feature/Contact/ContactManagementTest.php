<?php

namespace Tests\Feature\Contact;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Contact;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_own_contacts(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $user = User::factory()->create(['status' => UserStatus::Active->value]);
        $user->assignRole(RoleName::Customer->value);

        $this->actingAs($user)->post(route('admin.contacts.store'), [
            'name' => 'Ali Veli',
            'phone' => '5551234567',
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('contacts', ['user_id' => $user->id, 'phone' => '5551234567']);
    }

    public function test_user_cannot_view_other_users_contacts(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $owner = User::factory()->create(['status' => UserStatus::Active->value]);
        $owner->assignRole(RoleName::Admin->value);

        $other = User::factory()->create(['status' => UserStatus::Active->value]);
        $other->assignRole(RoleName::Admin->value);

        $contact = Contact::create([
            'user_id' => $owner->id,
            'name' => 'Gizli Kişi',
            'phone' => '5559876543',
            'is_active' => true,
        ]);

        $this->actingAs($other)->get(route('admin.contacts.edit', $contact))->assertStatus(403);
        $this->actingAs($other)->get(route('admin.contacts.index'))->assertStatus(200)->assertDontSee('Gizli Kişi');
    }
}
