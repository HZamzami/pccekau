<?php

namespace Database\Factories;

use App\Enums\ImagingType;
use App\Enums\ReportStatus;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ImagingReport>
 */
class ImagingReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'type' => fake()->randomElement(ImagingType::cases()),
            'status' => ReportStatus::Draft,
            'date' => fake()->dateTimeBetween('-1 year'),
            'report' => fake()->paragraph(),
        ];
    }
}
