<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\MdtDiscussion>
 */
class MdtDiscussionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'discussion_date' => fake()->dateTimeBetween('-1 month', '+1 month'),
            'diagnosis' => fake()->randomElement(['VSD', 'TOF', 'TGA', 'HLHS']),
            'reason_for_discussion' => fake()->sentence(),
            'specialist_fellow_id' => Staff::factory(),
        ];
    }
}
