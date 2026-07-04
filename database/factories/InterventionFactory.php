<?php

namespace Database\Factories;

use App\Enums\InterventionType;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Intervention>
 */
class InterventionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'date' => fake()->dateTimeBetween('-5 years'),
            'type' => fake()->randomElement(InterventionType::cases()),
            'name' => fake()->randomElement(['BT shunt', 'Glenn', 'Fontan', 'VSD closure', 'ASD device closure', 'PDA ligation', 'Coarctation repair', 'Balloon valvuloplasty']),
        ];
    }
}
