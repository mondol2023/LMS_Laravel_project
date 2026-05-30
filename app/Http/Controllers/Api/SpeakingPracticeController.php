<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AIServiceFactory;
use App\Services\Contracts\AIServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SpeakingPracticeController extends Controller
{
    private AIServiceInterface $aiService;

    public function __construct()
    {
        $this->aiService = AIServiceFactory::create();
    }

    /**
     * Start a new speaking practice session and get the first question
     */
    public function startSession(Request $request): JsonResponse
    {
        $request->validate([
            'part' => ['required', 'in:1,2,3,full'],
        ]);

        $part = $request->input('part');

        try {
            $question = $this->generateQuestion($part, []);

            return response()->json([
                'success' => true,
                'data' => [
                    'part' => $part,
                    'question' => $question,
                    'conversation' => [
                        ['role' => 'examiner', 'content' => $question],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Speaking Session Start Error', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start speaking session: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get next question based on conversation history
     */
    public function getNextQuestion(Request $request): JsonResponse
    {
        $request->validate([
            'part' => ['required', 'in:1,2,3,full'],
            'conversation' => ['required', 'array'],
            'user_response' => ['required', 'string'],
        ]);

        $part = $request->input('part');
        $conversation = $request->input('conversation');
        $userResponse = $request->input('user_response');

        // Add user response to conversation
        $conversation[] = ['role' => 'candidate', 'content' => $userResponse];

        try {
            // Check if we should continue or end the conversation
            $questionCount = collect($conversation)->where('role', 'examiner')->count();

            // Part 1: 4-5 questions, Part 3: 4-5 questions
            $maxQuestions = match ($part) {
                '1' => 5,
                '2' => 1, // Part 2 is just one cue card
                '3' => 5,
                'full' => 12, // Combined all parts
                default => 5,
            };

            if ($questionCount >= $maxQuestions) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'finished' => true,
                        'conversation' => $conversation,
                    ],
                ]);
            }

            $nextQuestion = $this->generateQuestion($part, $conversation);
            $conversation[] = ['role' => 'examiner', 'content' => $nextQuestion];

            return response()->json([
                'success' => true,
                'data' => [
                    'finished' => false,
                    'question' => $nextQuestion,
                    'conversation' => $conversation,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Speaking Next Question Error', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get next question: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Evaluate speaking performance
     */
    public function evaluate(Request $request): JsonResponse
    {
        $request->validate([
            'part' => ['required', 'in:1,2,3,full'],
            'conversation' => ['required', 'array', 'min:2'],
        ]);

        $part = $request->input('part');
        $conversation = $request->input('conversation');

        try {
            $evaluation = $this->evaluateSpeaking($part, $conversation);

            // Increment practice speaking usage counter
            $user = $request->user();
            $updatedSubscription = null;

            if ($user && $user->hasActiveSubscription()) {
                $user->incrementUsage('practice_speaking');

                // Calculate overall band score
                $overallBand = round(($evaluation['overall_band'] ?? 0), 1);

                // Generate exam name based on part
                $examName = match ($part) {
                    'full' => 'Speaking Full Test',
                    default => "Speaking Part {$part}",
                };

                // Save practice result to database
                \App\Models\PracticeResult::create([
                    'user_id' => $user->id,
                    'module' => 'speaking',
                    'exam_name' => $examName,
                    'score' => $overallBand,
                    'total' => 9,
                    'band_score' => $overallBand,
                    'details' => [
                        'part' => $part,
                        'fluency_coherence' => $evaluation['fluency_coherence'] ?? 0,
                        'lexical_resource' => $evaluation['lexical_resource'] ?? 0,
                        'grammatical_range' => $evaluation['grammatical_range'] ?? 0,
                        'pronunciation' => $evaluation['pronunciation'] ?? 0,
                        'strengths' => $evaluation['strengths'] ?? '',
                        'weaknesses' => $evaluation['weaknesses'] ?? '',
                    ],
                ]);

                // Refresh user to get updated subscription data
                $user->refresh();
                $activeSubscription = $user->activeSubscription;
                $package = $activeSubscription->package;

                $updatedSubscription = [
                    'practice_speaking_enabled' => $package->practice_speaking_enabled,
                    'practice_speaking_limit' => $package->practice_speaking_limit,
                    'practice_speaking_used' => $activeSubscription->practice_speaking_used,
                    'practice_speaking_remaining' => $user->getRemainingSpeakingPractice(),
                    'is_expired' => $user->isSubscriptionExpired(),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Speaking evaluation completed successfully.',
                'data' => $evaluation,
                'subscription' => $updatedSubscription,
            ]);
        } catch (\Exception $e) {
            Log::error('Speaking Evaluation Error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during evaluation: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate a question based on the part and conversation history
     */
    private function generateQuestion(string $part, array $conversation): string
    {
        $provider = config('services.ai_provider', 'gemini');
        $apiKey = config("services.{$provider}.api_key");
        $model = config("services.{$provider}.model");

        $systemPrompt = $this->getQuestionGenerationSystemPrompt($part);
        $userPrompt = $this->buildQuestionGenerationPrompt($part, $conversation);

        if ($provider === 'openai') {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'max_tokens' => 500,
                    'temperature' => 0.8,
                ]);

            if ($response->failed()) {
                throw new \Exception('Failed to generate question from AI');
            }

            return $response->json('choices.0.message.content');
        } else {
            // Gemini
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $systemPrompt."\n\n".$userPrompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.8,
                        'maxOutputTokens' => 500,
                    ],
                ]);

            if ($response->failed()) {
                throw new \Exception('Failed to generate question from AI');
            }

            return $response->json('candidates.0.content.parts.0.text');
        }
    }

    /**
     * Evaluate speaking performance using AI
     */
    private function evaluateSpeaking(string $part, array $conversation): array
    {
        $provider = config('services.ai_provider', 'gemini');
        $apiKey = config("services.{$provider}.api_key");
        $model = config("services.{$provider}.model");

        $systemPrompt = $this->getEvaluationSystemPrompt();
        $userPrompt = $this->buildEvaluationPrompt($part, $conversation);

        if ($provider === 'openai') {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'max_tokens' => 2048,
                    'temperature' => 0.3,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if ($response->failed()) {
                throw new \Exception('Failed to evaluate speaking performance');
            }

            $content = $response->json('choices.0.message.content');

            return json_decode($content, true);
        } else {
            // Gemini
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
                ->timeout(60)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $systemPrompt."\n\n".$userPrompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.3,
                        'maxOutputTokens' => 2048,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            if ($response->failed()) {
                throw new \Exception('Failed to evaluate speaking performance');
            }

            $content = $response->json('candidates.0.content.parts.0.text');

            return json_decode($content, true);
        }
    }

    private function getQuestionGenerationSystemPrompt(string $part): string
    {
        $partDescriptions = [
            '1' => 'You are an IELTS Speaking examiner conducting Part 1 (Introduction and Interview). Ask personal questions about familiar topics like home, family, work, studies, and interests. Keep questions simple and conversational.',
            '2' => 'You are an IELTS Speaking examiner conducting Part 2 (Long Turn). Provide a cue card with a topic for the candidate to speak about for 1-2 minutes. The cue card should include the topic and 3-4 bullet points to guide their response.',
            '3' => 'You are an IELTS Speaking examiner conducting Part 3 (Discussion). Ask abstract and analytical questions that require the candidate to express opinions, give reasons, and discuss issues in depth. Questions should be more complex than Part 1.',
            'full' => 'You are an IELTS Speaking examiner conducting a full speaking test. Start with Part 1 questions, then move to Part 2 (cue card), and finish with Part 3 (discussion).',
        ];

        return $partDescriptions[$part] ?? $partDescriptions['1'];
    }

    private function buildQuestionGenerationPrompt(string $part, array $conversation): string
    {
        if (empty($conversation)) {
            if ($part === '2') {
                return "Generate the first cue card for IELTS Speaking Part 2. Format it clearly with a topic and bullet points. Start with 'Describe...' or 'Talk about...'";
            }

            return "Generate the first question for IELTS Speaking Part {$part}. Make it natural and appropriate for the test level.";
        }

        $conversationText = collect($conversation)
            ->map(fn ($msg) => strtoupper($msg['role']).': '.$msg['content'])
            ->join("\n\n");

        return <<<PROMPT
Here is the conversation so far:

{$conversationText}

Based on the candidate's previous response, generate the next appropriate question for IELTS Speaking Part {$part}.
- Make it relevant and natural
- Don't repeat similar questions
- Maintain appropriate difficulty level
- Keep it concise

Provide only the question text, nothing else.
PROMPT;
    }

    private function getEvaluationSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert IELTS Speaking examiner with years of experience.
Evaluate the candidate's speaking performance according to the official IELTS Speaking band descriptors.

Assess these four criteria:
1. Fluency and Coherence (FC)
2. Lexical Resource (LR)
3. Grammatical Range and Accuracy (GRA)
4. Pronunciation (P)

Scoring Guidelines:
- Scores range from 0 to 9 in 0.5 increments (e.g., 5.0, 5.5, 6.0)
- Be fair but rigorous in your assessment
- Consider natural pauses, self-correction, vocabulary range, grammar accuracy, and clear pronunciation

Always respond in valid JSON format with the exact structure specified.
PROMPT;
    }

    private function buildEvaluationPrompt(string $part, array $conversation): string
    {
        $conversationText = collect($conversation)
            ->map(fn ($msg) => strtoupper($msg['role']).': '.$msg['content'])
            ->join("\n\n");

        return <<<PROMPT
Please evaluate this IELTS Speaking Part {$part} performance:

## Conversation:
{$conversationText}

---

Please provide your evaluation in the following JSON format:
{
    "fluency_coherence": <score 0-9 in 0.5 increments>,
    "lexical_resource": <score 0-9 in 0.5 increments>,
    "grammatical_range": <score 0-9 in 0.5 increments>,
    "pronunciation": <score 0-9 in 0.5 increments>,
    "overall_band": <average of the four scores, 0-9 in 0.5 increments>,
    "strengths": "<list 2-3 specific strengths with examples>",
    "weaknesses": "<list 2-3 areas for improvement with examples>",
    "feedback": "<detailed constructive feedback with specific suggestions for improvement>"
}

Important:
- All scores must be numbers (not strings)
- Provide constructive, specific feedback
- Reference specific examples from the conversation
PROMPT;
    }
}
