<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SiteVisit>
 */
class SiteVisitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'operator_id' => $this->faker->randomElement(
                \App\Models\User::where('id', '!=', 1)->pluck('id')->toArray()
            ),
            'ward_id' => $this->faker->randomElement(\App\Models\Ward::pluck('id')->toArray()),
            'site_name' => $this->faker->word,
            'site_description' => $this->faker->sentence,
            'visit_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'gps_latitude' => $this->faker->latitude,
            'gps_longitude' => $this->faker->longitude,
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'approved_by' => $this->faker->randomElement(\App\Models\User::pluck('id')->toArray()),
            'approved_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'rejection_reason' => $this->faker->sentence,
        ];
    }
}
