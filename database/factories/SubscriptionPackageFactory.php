<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubscriptionPackage>
 */
class SubscriptionPackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true).' Package',
            'duration' => fake()->randomElement([30, 60, 90, 180, 365]),
            'price' => fake()->randomFloat(2, 500, 10000),
            'discount' => fake()->randomFloat(2, 0, 500),
            'discount_type' => fake()->randomElement(['flat', 'percent']),
            'discount_till' => fake()->optional()->dateTimeBetween('now', '+6 months'),
            'is_active' => true,
            'full_mock_test_limit' => fake()->numberBetween(5, 20),
            'partial_reading_limit' => fake()->numberBetween(10, 30),
            'partial_writing_limit' => fake()->numberBetween(8, 25),
            'partial_listening_limit' => fake()->numberBetween(10, 30),
            'partial_speaking_limit' => fake()->numberBetween(8, 20),
            'practice_reading_enabled' => true,
            'practice_listening_enabled' => true,
            'practice_writing_enabled' => true,
            'practice_writing_limit' => fake()->numberBetween(10, 50),
            'practice_speaking_enabled' => true,
            'practice_speaking_limit' => fake()->numberBetween(10, 50),
        ];
    }

    /**
     * Package with unlimited limits.
     */
    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'full_mock_test_limit' => null,
            'partial_reading_limit' => null,
            'partial_writing_limit' => null,
            'partial_listening_limit' => null,
            'partial_speaking_limit' => null,
            'practice_writing_limit' => null,
            'practice_speaking_limit' => null,
        ]);
    }

    /**
     * Package with specific limits for testing.
     */
    public function withLimits(int $fullMock = 5, int $partialReading = 10, int $partialWriting = 8, int $partialListening = 12, int $partialSpeaking = 6): static
    {
        return $this->state(fn (array $attributes) => [
            'full_mock_test_limit' => $fullMock,
            'partial_reading_limit' => $partialReading,
            'partial_writing_limit' => $partialWriting,
            'partial_listening_limit' => $partialListening,
            'partial_speaking_limit' => $partialSpeaking,
        ]);
    }

    /**
     * Inactive package.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
