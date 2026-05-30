<?php

namespace Database\Factories;

use App\Models\SubscriptionPackage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserSubscription>
 */
class UserSubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subscribedAt = fake()->dateTimeBetween('-30 days', 'now');
        $duration = 30;

        return [
            'user_id' => User::factory(),
            'package_id' => SubscriptionPackage::factory(),
            'subscribed_at' => $subscribedAt,
            'expires_at' => now()->parse($subscribedAt)->addDays($duration),
            'is_active' => true,
            'full_mock_tests_used' => 0,
            'partial_reading_used' => 0,
            'partial_writing_used' => 0,
            'partial_listening_used' => 0,
            'partial_speaking_used' => 0,
            'practice_writing_used' => 0,
            'practice_speaking_used' => 0,
        ];
    }

    /**
     * Active subscription that expires in the future.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscribed_at' => now()->subDays(5),
            'expires_at' => now()->addDays(25),
            'is_active' => true,
        ]);
    }

    /**
     * Expired subscription.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscribed_at' => now()->subDays(40),
            'expires_at' => now()->subDays(10),
            'is_active' => true,
        ]);
    }

    /**
     * Subscription expiring soon (within 7 days).
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscribed_at' => now()->subDays(25),
            'expires_at' => now()->addDays(5),
            'is_active' => true,
        ]);
    }

    /**
     * Inactive subscription.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Subscription with specific usage.
     */
    public function withUsage(
        int $fullMock = 0,
        int $partialReading = 0,
        int $partialWriting = 0,
        int $partialListening = 0,
        int $partialSpeaking = 0,
        int $practiceWriting = 0,
        int $practiceSpeaking = 0
    ): static {
        return $this->state(fn (array $attributes) => [
            'full_mock_tests_used' => $fullMock,
            'partial_reading_used' => $partialReading,
            'partial_writing_used' => $partialWriting,
            'partial_listening_used' => $partialListening,
            'partial_speaking_used' => $partialSpeaking,
            'practice_writing_used' => $practiceWriting,
            'practice_speaking_used' => $practiceSpeaking,
        ]);
    }
}
