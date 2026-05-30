<?php

namespace Database\Factories;

use App\Models\Highlight;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Highlight>
 */
class HighlightFactory extends Factory
{
    protected $model = Highlight::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $text = fake()->sentence();
        $startOffset = fake()->numberBetween(0, 100);

        return [
            'phone' => fake()->numerify('##########'),
            'exam_id' => fake()->regexify('IELTS[0-9]{3}'),
            'section' => fake()->randomElement(['writing', 'reading', 'listening']),
            'part' => 'part'.fake()->numberBetween(1, 4),
            'text' => $text,
            'color' => fake()->randomElement(['yellow', 'green', 'blue', 'pink', 'orange']),
            'start_offset' => $startOffset,
            'end_offset' => $startOffset + strlen($text),
        ];
    }
}
