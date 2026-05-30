<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'roll_number' => fake()->unique()->numerify('ROLL####'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('+880##########'),
            'passport_number' => fake()->optional()->numerify('P########'),
            'institute' => fake()->optional()->company(),
            'reference' => fake()->optional()->text(200),
        ];
    }
}
