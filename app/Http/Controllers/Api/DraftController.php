<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Draft;
use App\Models\Exam;
use App\Models\Result;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DraftController extends Controller
{
    public function saveFinalDraft(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'exam_id' => 'required|string',
            'section' => 'required|string|in:reading,listening,writing,speaking',
            'answers' => 'required|array',
        ]);

        $phone = $request->get('phone');
        $examId = $request->get('exam_id');
        $section = $request->get('section');
        $answers = $request->get('answers');

        // Save each answer as a draft record
        foreach ($answers as $questionKey => $answer) {
            Draft::updateOrCreate(
                [
                    'phone' => $phone,
                    'exam_id' => $examId,
                    'section' => $section,
                    'part' => 'final', // Mark as final submission
                    'question_key' => (string) $questionKey,
                ],
                [
                    'answer' => $answer,
                    'last_saved_at' => now(),
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Final draft saved successfully',
            'saved_answers' => count($answers),
        ]);
    }

    public function getDraftAnswers(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'exam_id' => 'required|string',
            'section' => 'required|string|in:reading,listening,writing,speaking',
        ]);

        $phone = $request->get('phone');
        $examId = $request->get('exam_id');
        $section = $request->get('section');

        // Get all draft answers for this user, exam and section
        $drafts = Draft::where('phone', $phone)
            ->where('exam_id', $examId)
            ->where('section', $section)
            ->get();

        // Convert to key-value pairs
        $answers = [];
        foreach ($drafts as $draft) {
            $answers[$draft->question_key] = $draft->answer;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'phone' => $phone,
                'exam_id' => $examId,
                'section' => $section,
                'answers' => $answers,
                'total_questions' => count($answers),
            ],
        ]);
    }

    public function calculateScore(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'exam_id' => 'required|string',
            'section' => 'required|string|in:reading,listening,writing,speaking',
            'correct_answers' => 'required|array',
        ]);

        $phone = $request->get('phone');
        $examId = $request->get('exam_id');
        $section = $request->get('section');
        $correctAnswers = $request->get('correct_answers');

        // Get user's draft answers
        $drafts = Draft::where('phone', $phone)
            ->where('exam_id', $examId)
            ->where('section', $section)
            ->get();

        $userAnswers = [];
        foreach ($drafts as $draft) {
            $userAnswers[$draft->question_key] = $draft->answer;
        }

        // Calculate correct answers
        $correctCount = 0;
        $totalQuestions = count($correctAnswers);
        $detailedResults = [];

        foreach ($correctAnswers as $questionKey => $correctAnswer) {
            $userAnswer = $userAnswers[$questionKey] ?? '';
            $isCorrect = $this->compareAnswers($userAnswer, $correctAnswer);

            if ($isCorrect) {
                $correctCount++;
            }

            $detailedResults[$questionKey] = [
                'user_answer' => $userAnswer,
                'correct_answer' => $correctAnswer,
                'is_correct' => $isCorrect,
                'marks' => $isCorrect ? 1 : 0,
            ];
        }

        // Calculate IELTS band score
        $bandScore = $this->calculateBandScore($correctCount, $totalQuestions);

        return response()->json([
            'success' => true,
            'data' => [
                'phone' => $phone,
                'exam_id' => $examId,
                'section' => $section,
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctCount,
                'incorrect_answers' => $totalQuestions - $correctCount,
                'raw_score' => $correctCount,
                'band_score' => $bandScore,
                'detailed_results' => $detailedResults,
            ],
        ]);
    }

    private function compareAnswers(string $userAnswer, string $correctAnswer): bool
    {
        // Normalize answers for comparison
        $userAnswer = trim(strtolower($userAnswer));
        $correctAnswer = trim(strtolower($correctAnswer));

        // Handle multiple correct answers (separated by / or comma)
        if (strpos($correctAnswer, '/') !== false) {
            $possibleAnswers = array_map('trim', explode('/', $correctAnswer));

            return in_array($userAnswer, $possibleAnswers);
        }

        if (strpos($correctAnswer, ',') !== false) {
            $possibleAnswers = array_map('trim', explode(',', $correctAnswer));

            return in_array($userAnswer, $possibleAnswers);
        }

        return $userAnswer === $correctAnswer;
    }

    private function calculateBandScore(int $correctCount, int $totalQuestions): float
    {
        // IELTS Reading band score calculation for Academic test
        // Based on the number of correct answers out of 40

        if ($totalQuestions === 40) {
            if ($correctCount >= 39) {
                return 9.0;
            }
            if ($correctCount >= 37) {
                return 8.5;
            }
            if ($correctCount >= 35) {
                return 8.0;
            }
            if ($correctCount >= 33) {
                return 7.5;
            }
            if ($correctCount >= 30) {
                return 7.0;
            }
            if ($correctCount >= 27) {
                return 6.5;
            }
            if ($correctCount >= 23) {
                return 6.0;
            }
            if ($correctCount >= 19) {
                return 5.5;
            }
            if ($correctCount >= 15) {
                return 5.0;
            }
            if ($correctCount >= 13) {
                return 4.5;
            }
            if ($correctCount >= 10) {
                return 4.0;
            }
            if ($correctCount >= 8) {
                return 3.5;
            }
            if ($correctCount >= 6) {
                return 3.0;
            }
            if ($correctCount >= 4) {
                return 2.5;
            }
            if ($correctCount >= 3) {
                return 2.0;
            }
            if ($correctCount >= 2) {
                return 1.5;
            }
            if ($correctCount >= 1) {
                return 1.0;
            }
        }

        // For different total questions, calculate proportionally
        $percentage = ($correctCount / $totalQuestions) * 100;

        if ($percentage >= 97.5) {
            return 9.0;
        }
        if ($percentage >= 92.5) {
            return 8.5;
        }
        if ($percentage >= 87.5) {
            return 8.0;
        }
        if ($percentage >= 82.5) {
            return 7.5;
        }
        if ($percentage >= 75.0) {
            return 7.0;
        }
        if ($percentage >= 67.5) {
            return 6.5;
        }
        if ($percentage >= 57.5) {
            return 6.0;
        }
        if ($percentage >= 47.5) {
            return 5.5;
        }
        if ($percentage >= 37.5) {
            return 5.0;
        }
        if ($percentage >= 32.5) {
            return 4.5;
        }
        if ($percentage >= 25.0) {
            return 4.0;
        }
        if ($percentage >= 20.0) {
            return 3.5;
        }
        if ($percentage >= 15.0) {
            return 3.0;
        }
        if ($percentage >= 10.0) {
            return 2.5;
        }
        if ($percentage >= 7.5) {
            return 2.0;
        }
        if ($percentage >= 5.0) {
            return 1.5;
        }
        if ($percentage >= 2.5) {
            return 1.0;
        }

        return 0.0;
    }

    public function saveResults(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'exam_id' => 'required|string',
            'section' => 'required|string|in:reading,listening,writing,speaking',
            'score_data' => 'required|array',
            'username' => 'nullable|string',
            'email' => 'nullable|string|email',
            'passport' => 'nullable|string',
        ]);

        $phone = $request->get('phone');
        $examIdString = $request->get('exam_id');
        $section = $request->get('section');
        $scoreData = $request->get('score_data');
        $username = $request->get('username', 'Anonymous User');
        $email = $request->get('email', '');
        $passport = $request->get('passport', '');

        // Find the exam by exam_id string
        $exam = Exam::where('exam_id', $examIdString)->first();
        if (! $exam) {
            return response()->json([
                'success' => false,
                'message' => 'Exam not found',
            ], 404);
        }

        // Check if result already exists for this user and exam
        $result = Result::where('phone', $phone)
            ->where('exam_id', $exam->id)
            ->first();

        if (! $result) {
            // Create new result record
            $result = new Result;
            $result->exam_id = $exam->id;
            $result->phone = $phone;
            $result->username = $username;
            $result->email = $email;
            $result->passport = $passport;
            $result->reading = [];
            $result->writing = [];
            $result->listening = [];
            $result->speaking = [];
            $result->data = [];
        }

        // Update the specific section data based on section type
        if ($section === 'writing') {
            $sectionData = $scoreData;
        } else {
            // Reading/Listening section data structure
            $sectionData = [
                'total_questions' => $scoreData['total_questions'] ?? 0,
                'correct_answers' => $scoreData['correct_answers'] ?? 0,
                'incorrect_answers' => $scoreData['incorrect_answers'] ?? 0,
                'raw_score' => $scoreData['raw_score'] ?? 0,
                'band_score' => $scoreData['band_score'] ?? 0,
                'completed_at' => now()->toISOString(),
                'detailed_results' => $scoreData['detailed_results'] ?? [],
                'user_answers' => $scoreData['user_answers'] ?? [],
            ];
        }

        // Set the section data
        $result->{$section} = $sectionData;

        // Update general data
        $data = $result->data ?? [];
        $data["{$section}_completed"] = true;
        $data["{$section}_completion_time"] = now()->toISOString();
        $result->data = $data;

        $result->save();

        return response()->json([
            'success' => true,
            'message' => 'Results saved successfully',
            'data' => [
                'result_id' => $result->id,
                'exam_id' => $exam->id,
                'exam_code' => $examIdString,
                'phone' => $phone,
                'section' => $section,
            ],
        ]);
    }

    public function cleanupDrafts(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'exam_id' => 'required|string',
            'section' => 'required|string|in:reading,listening,writing,speaking',
        ]);

        $phone = $request->get('phone');
        $examId = $request->get('exam_id');
        $section = $request->get('section');

        // Delete all draft records for this user, exam, and section
        $deletedCount = Draft::where('phone', $phone)
            ->where('exam_id', $examId)
            ->where('section', $section)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Draft cleanup completed',
            'deleted_drafts' => $deletedCount,
        ]);
    }

    public function getResults(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'exam_id' => 'required|string',
        ]);

        $phone = $request->get('phone');
        $examIdString = $request->get('exam_id');

        // Find the exam by exam_id string
        $exam = Exam::where('exam_id', $examIdString)->first();

        if (! $exam) {
            return response()->json([
                'success' => false,
                'message' => 'Exam not found',
                'results' => null,
            ], 404);
        }

        // Find the result for this user and exam
        $result = Result::where('phone', $phone)
            ->where('exam_id', $exam->id)
            ->first();

        if (! $result) {
            return response()->json([
                'success' => true,
                'message' => 'No results found',
                'results' => null,
            ]);
        }

        // Return the section results
        return response()->json([
            'success' => true,
            'message' => 'Results retrieved successfully',
            'results' => [
                'listening' => $result->listening,
                'reading' => $result->reading,
                'writing' => $result->writing,
                'speaking' => $result->speaking,
            ],
        ]);
    }
}
