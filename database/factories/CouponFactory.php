<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('??####')),
            'discount' => fake()->randomFloat(2, 5, 100),
            'discount_type' => fake()->randomElement(['flat', 'percent']),
            'max_uses' => fake()->optional(0.7)->numberBetween(10, 1000),
            'current_uses' => 0,
            'discount_till' => fake()->optional(0.8)->dateTimeBetween('now', '+6 months'),
        ];
    }
}
