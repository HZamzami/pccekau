<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Clinic>
 */
class ClinicFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company() . ' Cardiac Center';

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 99999),
        ];
    }
}
