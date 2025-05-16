<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SmsCampaignFilter>
 */
class SmsCampaignFilterFactory extends Factory
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
            'filter_type' => $this->faker->randomElement(['region', 'district', 'ward', 'ranking', 'status']),
            'filter_value' => $this->faker->randomElement(['value1', 'value2', 'value3']),
        ];
    }
}
