<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OperatorIncentive>
 */
class OperatorIncentiveFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'operator_id' => \App\Models\User::factory(),
            'site_visit_id' => \App\Models\SiteVisit::factory(),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'description' => $this->faker->sentence,
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'approved_by' => \App\Models\User::factory(),
            'approved_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'paid_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'transaction_reference' => $this->faker->uuid,
        ];
    }
}
