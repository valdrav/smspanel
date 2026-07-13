<?php

namespace Database\Factories;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Organization> */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        $name = fake('tr_TR')->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 9999),
            'tax_number' => fake()->numerify('##########'),
            'email' => fake()->companyEmail(),
            'phone' => '5'.fake()->numerify('#########'),
            'address' => fake('tr_TR')->address(),
            'status' => OrganizationStatus::Active->value,
            'sms_balance' => 100.0000,
            'sms_sender_id' => 'TEST',
        ];
    }
}
