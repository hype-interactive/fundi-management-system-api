<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DisbursementBatch>
 */
class DisbursementBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'budget_allocation_id' => \App\Models\BudgetAllocation::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'total_amount' => $this->faker->randomFloat(2, 1000, 100000),
            'disbursement_date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed', 'failed']),
            'created_by' => \App\Models\User::factory(),
        ];
    }
}
