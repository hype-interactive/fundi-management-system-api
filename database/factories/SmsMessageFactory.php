<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SmsMessage>
 */
class SmsMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sms_campaign_id' => \App\Models\SmsCampaign::factory(),
            'recipient_type' => $this->faker->randomElement(['fundi', 'operator']),
            'recipient_id' => $this->faker->randomElement(\App\Models\User::pluck('id')->toArray()),
            'phone_number' => $this->faker->phoneNumber,
            'message' => $this->faker->text(160),
            'status' => $this->faker->randomElement(['pending', 'sent', 'failed']),
            'sent_at' => $this->faker->dateTimeBetween('now', '+1 month'),
            'delivered_at' => $this->faker->dateTimeBetween('now', '+1 month'),
            'failure_reason' => $this->faker->sentence,
        ];
    }
}
