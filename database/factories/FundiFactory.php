<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Fundi>
 */
class FundiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unique_id' => $this->faker->unique()->uuid,
            'fname' => $this->faker->firstName(),
            'mname' => $this->faker->firstNameMale(),
            'lname' => $this->faker->lastName(),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'nida_number' => $this->faker->unique()->numerify('##########'),
            'primary_phone' => $this->faker->phoneNumber(),
            'transaction_phone' => $this->faker->phoneNumber(),
            'ward_id' => $this->faker->randomElement(\App\Models\Ward::pluck('id')->toArray()),
            'ranking' => $this->faker->randomElement(['fundi_kuu', 'fundi_msaidizi']),
            'profile_photo' => $this->faker->imageUrl(640, 480, 'people'),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending']),
            'created_by' => \App\Models\User::factory(),
            'approved_by' => \App\Models\User::factory(),
            'approved_at' => $this->faker->dateTimeBetween('2000-01-01', 'now')->format('Y-m-d H:i:s'),
            'legacy_match' => $this->faker->boolean(),
            'legacy_id' => $this->faker->unique()->numerify('######'),
        ];
    }
}
