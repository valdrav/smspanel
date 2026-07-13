<?php

namespace Tests\Feature\Campaign;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Jobs\Sms\ProcessCampaignChunkJob;
use App\Models\Contact;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SmsCampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_campaign_from_contacts(): void
    {
        Queue::fake();
        $this->seed(RoleAndPermissionSeeder::class);

        $user = User::factory()->create(['status' => UserStatus::Active->value, 'sms_balance' => 1000]);
        $user->assignRole(RoleName::Customer->value);

        Contact::create([
            'user_id' => $user->id,
            'name' => 'Test',
            'phone' => '5551234567',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('admin.campaigns.store'), [
            'name' => 'Test Kampanya',
            'message' => 'Merhaba',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sms_campaigns', ['user_id' => $user->id, 'name' => 'Test Kampanya']);
        $this->assertDatabaseCount('sms_campaign_recipients', 1);
        Queue::assertPushed(ProcessCampaignChunkJob::class);
    }

    public function test_user_cannot_view_other_users_campaign(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $owner = User::factory()->create(['status' => UserStatus::Active->value]);
        $owner->assignRole(RoleName::Admin->value);

        $other = User::factory()->create(['status' => UserStatus::Active->value]);
        $other->assignRole(RoleName::Admin->value);

        $campaign = \App\Models\SmsCampaign::create([
            'user_id' => $owner->id,
            'name' => 'Gizli Kampanya',
            'message' => 'Test',
            'status' => 'pending',
            'total_recipients' => 0,
        ]);

        $this->actingAs($other)->get(route('admin.campaigns.show', $campaign))->assertStatus(403);
    }
}
