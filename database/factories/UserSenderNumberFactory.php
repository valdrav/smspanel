<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserSenderNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<UserSenderNumber> */
class UserSenderNumberFactory extends Factory
{
    protected $model = UserSenderNumber::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'sender_id' => strtoupper(fake()->bothify('??######')),
            'label' => fake()->optional()->words(2, true),
            'is_default' => false,
            'is_active' => true,
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
