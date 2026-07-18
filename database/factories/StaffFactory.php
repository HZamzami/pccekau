<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Staff>
 */
class StaffFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Dr. ' . fake()->name(),
            'role' => fake()->randomElement(['consultant', 'fellow']),
            'specialty' => fake()->randomElement(['ep', 'cath', 'advanced_imaging', 'general']),
            'is_active' => true,
        ];
    }
}
