<?php

namespace Database\Seeders;

use App\DTOs\Organization\CreateOrganizationData;
use App\Enums\OrganizationStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\User;
use App\Services\Contracts\OrganizationServiceInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Demo organizasyon ve müşteri kullanıcısı oluşturur.
 */
class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@smspanel.local')->first();

        if ($admin === null) {
            return;
        }

        Auth::login($admin);

        $organizationService = app(OrganizationServiceInterface::class);

        $organization = Organization::where('slug', 'demo-musteri')->first();

        if ($organization === null) {
            $organization = $organizationService->create(new CreateOrganizationData(
                name: 'Demo Müşteri A.Ş.',
                taxNumber: '1234567890',
                email: 'info@demomusteri.local',
                phone: '5551112233',
                address: 'İstanbul, Türkiye',
                status: OrganizationStatus::Active,
                smsSenderId: 'DEMO',
                notes: 'Demo müşteri organizasyonu',
                initialBalance: 500.0,
            ));
        }

        $customer = User::firstOrCreate(
            ['email' => 'musteri@smspanel.local'],
            [
                'organization_id' => $organization->id,
                'name' => 'Demo Müşteri',
                'phone' => '5552223344',
                'password' => Hash::make('Musteri123!'),
                'status' => UserStatus::Active->value,
                'email_verified_at' => now(),
            ]
        );

        $customer->update(['organization_id' => $organization->id]);
        $customer->syncRoles([RoleName::Customer->value]);

        Auth::logout();
    }
}
