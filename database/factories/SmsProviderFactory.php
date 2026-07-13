<?php

namespace Database\Factories;

use App\Enums\SmsProviderDriver;
use App\Models\SmsProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SmsProvider> */
class SmsProviderFactory extends Factory
{
    protected $model = SmsProvider::class;

    public function definition(): array
    {
        return [
            'code' => 'mock-'.fake()->unique()->numberBetween(1000, 9999),
            'name' => 'Test Sağlayıcı',
            'driver' => SmsProviderDriver::Mock->value,
            'config' => [],
            'is_active' => true,
            'is_default' => false,
            'priority' => 100,
        ];
    }
}
