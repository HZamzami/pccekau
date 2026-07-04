<?php

namespace Database\Factories;

use App\Models\ImagingReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\EchoMeasurement>
 */
class EchoMeasurementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'imaging_report_id' => ImagingReport::factory()->state(['type' => 'echo']),
            'height_cm' => fake()->randomFloat(1, 50, 160),
            'weight_kg' => fake()->randomFloat(2, 4, 55),
            'lvidd' => fake()->randomFloat(2, 2.0, 4.8),
        ];
    }
}
