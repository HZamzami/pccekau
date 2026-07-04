<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ClinicVisit>
 */
class ClinicVisitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'visit_date' => fake()->dateTimeBetween('-6 months'),
            'subjective' => fake()->sentence(),
            'objective' => fake()->sentence(),
            'assessment' => fake()->sentence(),
            'plan' => fake()->sentence(),
        ];
    }
}
