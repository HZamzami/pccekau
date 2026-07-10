<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mrn' => (string) fake()->unique()->numberBetween(100000, 999999),
            'name' => fake()->name(),
            'date_of_birth' => fake()->dateTimeBetween('-15 years', '-1 month'),
            'gender' => fake()->randomElement(['male', 'female']),
            'nationality' => 'Saudi Arabian',
            'blood_type' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'weight_kg' => fake()->randomFloat(2, 3, 60),
            'height_cm' => fake()->randomFloat(1, 45, 170),
            'primary_diagnosis' => fake()->randomElement(['VSD', 'ASD', 'TOF', 'TGA', 'PDA']),
            'status' => 'active',
        ];
    }
}
