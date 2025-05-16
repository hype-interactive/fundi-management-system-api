<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'disbursement_batch_id' => \App\Models\DisbursementBatch::factory(),
            'fundi_id' => \App\Models\Fundi::factory(),
            'amount' => $this->faker->randomFloat(2, 1000, 10000),
            'transaction_reference' => $this->faker->uuid,
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'failed']),
            'mno_response' => $this->faker->text,
            'failure_reason' => $this->faker->sentence,
            'processed_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
