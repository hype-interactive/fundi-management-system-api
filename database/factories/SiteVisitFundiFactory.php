<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SiteVisitFundi>
 */
class SiteVisitFundiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_visit_id' => \App\Models\SiteVisit::factory(),
            'fundi_id' => \App\Models\Fundi::factory(),
            'notes' => $this->faker->sentence,
        ];
    }
}
