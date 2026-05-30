<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EvaluateWritingRequest;
use App\Services\AIServiceFactory;
use App\Services\Contracts\AIServiceInterface;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WritingEvaluationController extends Controller
{
    private AIServiceInterface $aiService;

    public function __construct()
    {
        $this->aiService = AIServiceFactory::create();
    }

    /**
     * Evaluate a single writing task for practice mode.
     * Uses the same AI service as the main evaluate method.
     */
    public function practiceEvaluate(Request $request): JsonResponse
    {
        $request->validate([
            'writing' => ['required', 'array', 'min:1', 'max:1'],
            'writing.0.q' => ['required', 'string'],
            'writing.0.ans' => ['required', 'string', 'min:50'],
        ], [
            'writing.0.ans.min' => 'Your answer must be at least 50 characters.',
        ]);

        try {
            $task = $request->input('writing.0');
            $taskType = $this->detectTaskType($task['q']);

            // Create a placeholder for the other task so we can reuse the existing AI service
            $placeholderTask = [
                'q' => 'N/A - Only one task was submitted for practice evaluation.',
                'ans' => 'N/A - This task was not completed. Please evaluate only the submitted task and set scores to 0 for this placeholder.',
            ];

            // Build the writing tasks array based on which task type is being evaluated
            $writingTasks = $taskType === 'task1'
                ? [$task, $placeholderTask]
                : [$placeholderTask, $task];

            // Use the existing AI service (same as evaluate method)
            $evaluation = $this->aiService->evaluateIELTSWriting($writingTasks);

            // Validate scores are within the IELTS range
            $scoreKeys = [
                'task1_ta', 'task1_cc', 'task1_lr', 'task1_gra',
                'task2_ta', 'task2_cc', 'task2_lr', 'task2_gra',
            ];

            foreach ($scoreKeys as $key) {
                if (isset($evaluation[$key])) {
                    $score = $evaluation[$key];
                    if (! is_numeric($score) || $score < 0 || $score > 9) {
                        $evaluation[$key] = max(0, min(9, (float) $score));
                    }
                    $evaluation[$key] = round($evaluation[$key] * 2) / 2;
                }
            }

            // Increment practice writing usage counter
            $user = $request->user();
            $updatedSubscription = null;

            if ($user && $user->hasActiveSubscription()) {
                $user->incrementUsage('practice_writing');

                // Calculate overall band score
                $taskNumber = $taskType === 'task1' ? 1 : 2;
                $prefix = "task{$taskNumber}_";
                $overallBand = round((
                    ($evaluation["{$prefix}ta"] ?? 0) +
                    ($evaluation["{$prefix}cc"] ?? 0) +
                    ($evaluation["{$prefix}lr"] ?? 0) +
                    ($evaluation["{$prefix}gra"] ?? 0)
                ) / 4, 1);

                // Save practice result to database
                \App\Models\PracticeResult::create([
                    'user_id' => $user->id,
                    'module' => 'writing',
                    'exam_name' => "Writing Task {$taskNumber}",
                    'score' => $overallBand,
                    'total' => 9,
                    'band_score' => $overallBand,
                    'details' => [
                        'task_type' => $taskType,
                        'task_number' => $taskNumber,
                        'task_achievement' => $evaluation["{$prefix}ta"] ?? 0,
                        'coherence_cohesion' => $evaluation["{$prefix}cc"] ?? 0,
                        'lexical_resource' => $evaluation["{$prefix}lr"] ?? 0,
                        'grammatical_range' => $evaluation["{$prefix}gra"] ?? 0,
                        'question' => $task['q'] ?? '',
                    ],
                ]);

                // Refresh user to get updated subscription data
                $user->refresh();
                $activeSubscription = $user->activeSubscription;
                $package = $activeSubscription->package;

                $updatedSubscription = [
                    'practice_writing_enabled' => $package->practice_writing_enabled,
                    'practice_writing_limit' => $package->practice_writing_limit,
                    'practice_writing_used' => $activeSubscription->practice_writing_used,
                    'practice_writing_remaining' => $user->getRemainingWritingPractice(),
                    'is_expired' => $user->isSubscriptionExpired(),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Writing evaluation completed successfully.',
                'data' => $evaluation,
                'subscription' => $updatedSubscription,
            ]);
        } catch (RequestException $e) {
            $errorBody = $e->response?->json() ?? [];
            $errorMessage = $errorBody['error']['message'] ?? $e->getMessage();
            Log::error('AI API Error (Practice)', ['error' => $errorBody, 'message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'AI service error: '.$errorMessage,
                'data' => null,
            ], 503);
        } catch (\Exception $e) {
            Log::error('Practice Evaluation Error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during evaluation: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Detect if the task is Task 1 or Task 2 based on question content.
     */
    private function detectTaskType(string $question): string
    {
        $task1Keywords = ['diagram', 'chart', 'graph', 'table', 'map', 'process', 'summarise', 'summarize', 'pie', 'bar'];
        $questionLower = strtolower($question);

        foreach ($task1Keywords as $keyword) {
            if (str_contains($questionLower, $keyword)) {
                return 'task1';
            }
        }

        return 'task2';
    }

    public function evaluate(EvaluateWritingRequest $request): JsonResponse
    {
        try {
            $writingTasks = $request->validated()['writing'];

            $evaluation = $this->aiService->evaluateIELTSWriting($writingTasks);

            // Validate the response structure
            $requiredKeys = [
                'task1_ta', 'task1_cc', 'task1_lr', 'task1_gra', 'task1_feedback',
                'task2_ta', 'task2_cc', 'task2_lr', 'task2_gra', 'task2_feedback',
                'teacher_feedback',
            ];

            foreach ($requiredKeys as $key) {
                if (! array_key_exists($key, $evaluation)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'AI evaluation returned incomplete data.',
                        'data' => null,
                    ], 500);
                }
            }

            // Validate scores are within the IELTS range
            $scoreKeys = [
                'task1_ta', 'task1_cc', 'task1_lr', 'task1_gra',
                'task2_ta', 'task2_cc', 'task2_lr', 'task2_gra',
            ];

            foreach ($scoreKeys as $key) {
                $score = $evaluation[$key];
                if (! is_numeric($score) || $score < 0 || $score > 9) {
                    $evaluation[$key] = max(0, min(9, (float) $score));
                }
                // Round to nearest 0.5
                $evaluation[$key] = round($evaluation[$key] * 2) / 2;
            }

            return response()->json([
                'success' => true,
                'message' => 'Writing evaluation completed successfully.',
                'data' => $evaluation,
            ]);
        } catch (RequestException $e) {
            $errorBody = $e->response?->json() ?? [];
            $errorMessage = $errorBody['error']['message'] ?? $e->getMessage();
            Log::error('OpenAI API Error', ['error' => $errorBody, 'message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'AI service error: '.$errorMessage,
                'data' => null,
            ], 503);
        } catch (\Exception $e) {
            Log::error('Writing Evaluation Error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during evaluation: '.$e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}
