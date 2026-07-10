<?php

namespace Database\Factories;

use App\Enums\EpStudyType;
use App\Enums\ReportStatus;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\EpStudy>
 */
class EpStudyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'type' => fake()->randomElement(EpStudyType::cases()),
            'status' => ReportStatus::Draft,
            'date' => fake()->dateTimeBetween('-1 year'),
            'report' => fake()->paragraph(),
        ];
    }
}
