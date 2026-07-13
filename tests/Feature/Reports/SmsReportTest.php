<?php

namespace Tests\Feature\Reports;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\SmsMessage;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_sms_reports(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $admin = User::factory()->create(['status' => UserStatus::Active->value]);
        $admin->assignRole(RoleName::Admin->value);

        SmsMessage::factory()->count(3)->create(['user_id' => $admin->id]);

        $response = $this->actingAs($admin)->get(route('admin.reports.sms'));

        $response->assertStatus(200);
        $response->assertSee('SMS Raporları');
        $response->assertSee('Toplam SMS');
    }
}
