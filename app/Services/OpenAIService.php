<?php

namespace App\Services;

use App\Services\Contracts\AIServiceInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class OpenAIService implements AIServiceInterface
{
    private string $apiKey;

    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model');
    }

    /**
     * Evaluate IELTS writing tasks using GPT-4o
     *
     * @param  array<int, array{q: string, ans: string}>  $writingTasks
     * @return array{
     *     task1_ta: float,
     *     task1_cc: float,
     *     task1_lr: float,
     *     task1_gra: float,
     *     task1_feedback: string,
     *     task2_ta: float,
     *     task2_cc: float,
     *     task2_lr: float,
     *     task2_gra: float,
     *     task2_feedback: string,
     *     teacher_feedback: string
     * }
     *
     * @throws RequestException|ConnectionException
     */
    public function evaluateIELTSWriting(array $writingTasks): array
    {
        $prompt = $this->buildEvaluationPrompt($writingTasks);
        info('OpenAI IELTS Writing Evaluation Prompt: '.$prompt);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
        ])
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->getSystemPrompt(),
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_tokens' => 4096,
                'temperature' => 0.3,
                'response_format' => ['type' => 'json_object'],
            ]);

        if ($response->failed()) {
            throw new RequestException($response);
        }

        $content = $response->json('choices.0.message.content');

        return json_decode($content, true);
    }

    private function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert IELTS examiner with years of experience evaluating Writing tasks.
Your role is to assess IELTS Academic Writing responses according to the official IELTS band descriptors.

For each task, evaluate these four criteria:
1. Task Achievement (TA) / Task Response (TR for Task 2)
2. Coherence and Cohesion (CC)
3. Lexical Resource (LR)
4. Grammatical Range and Accuracy (GRA)

Scoring Guidelines:
- Scores range from 0 to 9 in 0.5 increments (e.g., 5.0, 5.5, 6.0, 6.5)
- Be fair but rigorous in your assessment
- Consider the task requirements carefully
- Task 1 should be approximately 150 words (describing data/process/map)
- Task 2 should be approximately 250 words (argumentative essay)

Always respond in valid JSON format with the exact structure specified.
PROMPT;
    }

    private function buildEvaluationPrompt(array $writingTasks): string
    {
        $task1Question = $writingTasks[0]['q'] ?? 'No question provided';
        $task1Answer = $writingTasks[0]['ans'] ?? 'No answer provided';
        $task2Question = $writingTasks[1]['q'] ?? 'No question provided';
        $task2Answer = $writingTasks[1]['ans'] ?? 'No answer provided';

        return <<<PROMPT
Please evaluate the following IELTS Academic Writing responses:

## Task 1 (Academic Writing - 150+ words)
**Question:** {$task1Question}

**Student's Response:**
{$task1Answer}

---

## Task 2 (Essay Writing - 250+ words)
**Question:** {$task2Question}

**Student's Response:**
{$task2Answer}

---

Please provide your evaluation in the following JSON format:
{
    "task1_ta": <score 0-9 in 0.5 increments>,
    "task1_cc": <score 0-9 in 0.5 increments>,
    "task1_lr": <score 0-9 in 0.5 increments>,
    "task1_gra": <score 0-9 in 0.5 increments>,
    "task1_feedback": "<detailed feedback for Task 1 including strengths, weaknesses, and specific suggestions for improvement>",
    "task2_ta": <score 0-9 in 0.5 increments>,
    "task2_cc": <score 0-9 in 0.5 increments>,
    "task2_lr": <score 0-9 in 0.5 increments>,
    "task2_gra": <score 0-9 in 0.5 increments>,
    "task2_feedback": "<detailed feedback for Task 2 including strengths, weaknesses, and specific suggestions for improvement>",
    "teacher_feedback": "<overall summary feedback covering both tasks with general advice for improvement>"
}

Important:
- All scores must be numbers (not strings)
- Provide constructive, specific feedback that helps the student improve
- Reference specific examples from their writing when giving feedback
PROMPT;
    }
}
