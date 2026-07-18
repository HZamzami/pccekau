<?php

namespace Database\Factories;

use App\Enums\ApprovalProcedure;
use App\Enums\ApprovalStatus;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ApprovalRequest>
 */
class ApprovalRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id'     => Patient::factory(),
            'procedure_date' => fake()->boolean(70) ? fake()->dateTimeBetween('now', '+2 months') : null,
            'diagnosis'      => fake()->sentence(),
            'procedure'      => fake()->randomElement(ApprovalProcedure::cases()),
            'status'         => ApprovalStatus::Pending,
        ];
    }
}
