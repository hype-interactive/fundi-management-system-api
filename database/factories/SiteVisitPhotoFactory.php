<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SiteVisitPhoto>
 */
class SiteVisitPhotoFactory extends Factory
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
            'photo_path' => $this->faker->imageUrl(),
        ];
    }
}
