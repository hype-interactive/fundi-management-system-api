<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SmsCampaign>
 */
class SmsCampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'message' => $this->faker->text,
            'recipient_type' => $this->faker->randomElement(['fundis', 'operators', 'both']),
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 month'),
            'status' => $this->faker->randomElement(['draft', 'scheduled', 'processing', 'completed']),
            'created_by' => \App\Models\User::factory(),
            'sent_at' => $this->faker->dateTimeBetween('now', '+1 month'),
        ];
    }
}
