<?php

namespace Tests\Feature\Sms;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\SmsTemplate;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_list_templates(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $user = User::factory()->create(['status' => UserStatus::Active->value]);
        $user->assignRole(RoleName::Customer->value);

        $this->actingAs($user)->post(route('admin.sms-templates.store'), [
            'name' => 'Hoş Geldin',
            'body' => 'Merhaba {name}',
            'is_active' => '1',
        ])->assertRedirect(route('admin.sms-templates.index'));

        $this->assertDatabaseHas('sms_templates', [
            'user_id' => $user->id,
            'name' => 'Hoş Geldin',
        ]);

        $this->actingAs($user)->get(route('admin.sms-templates.index'))
            ->assertOk()
            ->assertSee('Hoş Geldin');
    }

    public function test_user_cannot_edit_other_users_template(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $owner = User::factory()->create(['status' => UserStatus::Active->value]);
        $owner->assignRole(RoleName::Customer->value);

        $other = User::factory()->create(['status' => UserStatus::Active->value]);
        $other->assignRole(RoleName::Customer->value);

        $template = SmsTemplate::create([
            'user_id' => $owner->id,
            'name' => 'Gizli Şablon',
            'body' => 'Test',
            'is_active' => true,
        ]);

        $this->actingAs($other)->get(route('admin.sms-templates.edit', $template))->assertForbidden();
    }
}
