<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TestAttempt>
 */
class TestAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-30 days', 'now');
        $status = fake()->randomElement(['completed', 'in_progress', 'abandoned']);
        $completedAt = $status === 'completed' ? fake()->dateTimeBetween($startedAt, 'now') : null;

        return [
            'user_id' => \App\Models\User::factory(),
            'test_id' => \App\Models\Test::factory(),
            'attempt_number' => 1,
            'status' => $status,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'listening_score' => $status === 'completed' ? fake()->randomFloat(1, 4.0, 9.0) : null,
            'reading_score' => $status === 'completed' ? fake()->randomFloat(1, 4.0, 9.0) : null,
            'writing_score' => $status === 'completed' ? fake()->randomFloat(1, 4.0, 9.0) : null,
            'speaking_score' => $status === 'completed' ? fake()->randomFloat(1, 4.0, 9.0) : null,
            'overall_band' => $status === 'completed' ? fake()->randomFloat(1, 4.0, 9.0) : null,
            'time_spent' => [
                'listening' => fake()->numberBetween(1500, 2100), // 25-35 minutes
                'reading' => fake()->numberBetween(3300, 3900), // 55-65 minutes
                'writing' => fake()->numberBetween(3300, 3900), // 55-65 minutes
                'speaking' => fake()->numberBetween(660, 900),  // 11-15 minutes
            ],
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => fake()->dateTimeBetween($attributes['started_at'] ?? '-1 day', 'now'),
            'listening_score' => fake()->randomFloat(1, 4.0, 9.0),
            'reading_score' => fake()->randomFloat(1, 4.0, 9.0),
            'writing_score' => fake()->randomFloat(1, 4.0, 9.0),
            'speaking_score' => fake()->randomFloat(1, 4.0, 9.0),
            'overall_band' => fake()->randomFloat(1, 4.0, 9.0),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'completed_at' => null,
            'listening_score' => null,
            'reading_score' => null,
            'writing_score' => null,
            'speaking_score' => null,
            'overall_band' => null,
        ]);
    }
}
