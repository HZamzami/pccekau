<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Admission>
 */
class AdmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id'             => Patient::factory(),
            'admitted_at'            => now()->subDays(fake()->numberBetween(0, 10)),
            'ward'                   => fake()->randomElement(['Cardiology A', 'Cardiology B', 'PICU']),
            'bed'                    => (string) fake()->numberBetween(1, 30),
            'admission_note'         => fake()->paragraph(),
            'presentation_diagnosis' => fake()->sentence(),
            'active_issues'          => fake()->sentence(),
        ];
    }

    public function discharged(): static
    {
        return $this->state(fn () => [
            'discharged_at'  => now(),
            'discharge_note' => fake()->paragraph(),
        ]);
    }
}
