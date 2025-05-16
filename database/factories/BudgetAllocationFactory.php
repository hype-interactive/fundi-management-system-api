<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BudgetAllocation>
 */
class BudgetAllocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'total_amount' => $this->faker->randomFloat(2, 1000, 100000),
            'remaining_amount' => $this->faker->randomFloat(2, 1000, 100000),
            'created_by' => $this->faker->randomElement(\App\Models\User::pluck('id')->toArray()),
            'status' => $this->faker->randomElement(['active', 'completed', 'cancelled']),
        ];
    }
}
