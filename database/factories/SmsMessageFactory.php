<?php

namespace Database\Factories;

use App\Enums\SmsMessageStatus;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SmsMessage>
 */
class SmsMessageFactory extends Factory
{
    protected $model = SmsMessage::class;

    /**
     * Model varsayılan durumunu tanımlar.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'recipient' => '5'.fake()->numerify('#########'),
            'message' => fake('tr_TR')->sentence(),
            'sender_id' => 'SMSPANEL',
            'provider' => 'mock',
            'status' => SmsMessageStatus::Delivered->value,
            'segments' => 1,
            'cost' => 0.05,
            'sent_at' => now(),
            'delivered_at' => now(),
        ];
    }

    /**
     * Kuyrukta bekleyen SMS durumu.
     */
    public function queued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SmsMessageStatus::Queued->value,
            'sent_at' => null,
            'delivered_at' => null,
        ]);
    }

    /**
     * Başarısız SMS durumu.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SmsMessageStatus::Failed->value,
            'error_message' => 'Gönderim başarısız.',
            'sent_at' => null,
            'delivered_at' => null,
        ]);
    }
}
