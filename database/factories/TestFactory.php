<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Test>
 */
class TestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'type' => fake()->randomElement(['academic', 'general_training']),
            'difficulty' => fake()->randomElement(['easy', 'medium', 'hard']),
            'status' => 'published',
            'listening_content' => $this->generateListeningContent(),
            'reading_content' => $this->generateReadingContent(),
            'writing_tasks' => $this->generateWritingTasks(),
            'speaking_tasks' => $this->generateSpeakingTasks(),
        ];
    }

    private function generateListeningContent(): array
    {
        return [
            'sections' => [
                [
                    'section_number' => 1,
                    'audio_file' => 'listening/section1.mp3',
                    'questions' => [
                        ['number' => 1, 'type' => 'multiple_choice', 'question' => 'What is the main topic?', 'options' => ['A', 'B', 'C'], 'answer' => 'A'],
                        ['number' => 2, 'type' => 'fill_blank', 'question' => 'The meeting is at ___', 'answer' => '3pm'],
                        ['number' => 3, 'type' => 'multiple_choice', 'question' => 'Where does the conversation take place?', 'options' => ['Library', 'Office', 'Home'], 'answer' => 'Office'],
                    ],
                ],
                [
                    'section_number' => 2,
                    'audio_file' => 'listening/section2.mp3',
                    'questions' => [
                        ['number' => 4, 'type' => 'multiple_choice', 'question' => 'What is the speaker discussing?', 'options' => ['Weather', 'Sports', 'Travel'], 'answer' => 'Travel'],
                        ['number' => 5, 'type' => 'fill_blank', 'question' => 'The cost is ___', 'answer' => '$50'],
                    ],
                ],
            ],
        ];
    }

    private function generateReadingContent(): array
    {
        return [
            'passages' => [
                [
                    'passage_number' => 1,
                    'title' => 'Climate Change Effects',
                    'text' => 'Climate change is one of the most pressing issues of our time...',
                    'questions' => [
                        ['number' => 1, 'type' => 'true_false', 'question' => 'Climate change affects weather patterns', 'answer' => 'TRUE'],
                        ['number' => 2, 'type' => 'multiple_choice', 'question' => 'What is the main cause?', 'options' => ['A', 'B', 'C'], 'answer' => 'B'],
                    ],
                ],
                [
                    'passage_number' => 2,
                    'title' => 'Technology in Education',
                    'text' => 'The integration of technology in education has transformed learning...',
                    'questions' => [
                        ['number' => 3, 'type' => 'matching', 'question' => 'Match the technology with its benefit', 'answer' => ['tablets-portability', 'computers-processing']],
                    ],
                ],
            ],
        ];
    }

    private function generateWritingTasks(): array
    {
        return [
            'task1' => [
                'type' => 'graph_description',
                'prompt' => 'The chart below shows the percentage of households in owned and rented accommodation in England and Wales between 1918 and 2011.',
                'image' => 'writing/task1_chart.png',
                'word_limit' => 150,
            ],
            'task2' => [
                'type' => 'essay',
                'prompt' => 'Some people think that universities should provide graduates with the knowledge and skills needed in the workplace. Others think that the true function of a university should be to give access to knowledge for its own sake, regardless of whether the course is useful to an employer. What, in your opinion, should be the main function of a university?',
                'word_limit' => 250,
            ],
        ];
    }

    private function generateSpeakingTasks(): array
    {
        return [
            'part1' => [
                'topic' => 'Personal Information',
                'questions' => [
                    'What is your name?',
                    'Where are you from?',
                    'Do you work or study?',
                    'What do you like about your hometown?',
                ],
            ],
            'part2' => [
                'topic' => 'Describe a memorable journey',
                'prompt' => 'You should say: where you went, when you went there, who you went with, and explain why it was memorable',
                'preparation_time' => 60,
                'speaking_time' => 120,
            ],
            'part3' => [
                'topic' => 'Travel and Tourism',
                'questions' => [
                    'How has tourism changed in your country?',
                    'What are the benefits of international travel?',
                    'Do you think tourism has negative effects on local communities?',
                ],
            ],
        ];
    }
}
