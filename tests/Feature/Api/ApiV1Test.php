<?php

namespace Tests\Feature\Api;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Jobs\Sms\ProcessCampaignChunkJob;
use App\Models\Contact;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $plainToken = 'test-api-token-secret';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->user = User::factory()->create([
            'status' => UserStatus::Active->value,
            'sms_balance' => 500,
            'api_token' => hash('sha256', $this->plainToken),
        ]);
        $this->user->assignRole(RoleName::Customer->value);
    }

    private function apiHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->plainToken];
    }

    public function test_bulk_sms_api_queues_messages(): void
    {
        $response = $this->postJson('/api/v1/sms/bulk', [
            'recipients' => "5551234567\n5559876543",
            'message' => 'API toplu test',
        ], $this->apiHeaders());

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseCount('sms_messages', 2);
    }

    public function test_campaign_api_creates_campaign(): void
    {
        Queue::fake();

        Contact::create([
            'user_id' => $this->user->id,
            'phone' => '5551234567',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/campaigns', [
            'name' => 'API Kampanya',
            'message' => 'Merhaba',
        ], $this->apiHeaders());

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'API Kampanya');
        Queue::assertPushed(ProcessCampaignChunkJob::class);
    }
}
