<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'log_type' => $this->faker->randomElement(['create', 'update', 'delete', 'access', 'excel']),
            'description' => $this->faker->sentence,
            'model_type' => $this->faker->word,
            'model_id' => $this->faker->randomNumber(),
            'properties' => json_encode($this->faker->words(3)),
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
            'created_at' => now(),
        ];
    }
}
