<?php

namespace App\Http\Controllers;

use App\Models\TestAttempt;
use App\Models\UserAnswer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttemptController extends Controller
{
    public function show(TestAttempt $attempt)
    {
        // Ensure user owns this attempt
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }

        $test = $attempt->test;
        $userAnswers = $attempt->userAnswers()->get()->keyBy(function ($answer) {
            return $answer->section.'_'.$answer->question_number;
        });

        return Inertia::render('Tests/Take', [
            'attempt' => [
                'id' => $attempt->id,
                'status' => $attempt->status,
                'started_at' => $attempt->started_at,
                'time_spent' => $attempt->time_spent ?? [],
            ],
            'test' => [
                'id' => $test->id,
                'title' => $test->title,
                'type' => $test->type,
                'listening_content' => $test->listening_content,
                'reading_content' => $test->reading_content,
                'writing_tasks' => $test->writing_tasks,
                'speaking_tasks' => $test->speaking_tasks,
            ],
            'userAnswers' => $userAnswers,
        ]);
    }

    public function saveAnswer(TestAttempt $attempt, Request $request)
    {
        // Ensure user owns this attempt
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }

        // Ensure attempt is still in progress
        if ($attempt->status !== 'in_progress') {
            return response()->json(['error' => 'This attempt is no longer active'], 422);
        }

        $request->validate([
            'section' => 'required|in:listening,reading,writing,speaking',
            'question_number' => 'required|integer|min:1',
            'answer' => 'required|string',
        ]);

        UserAnswer::updateOrCreate(
            [
                'attempt_id' => $attempt->id,
                'section' => $request->section,
                'question_number' => $request->question_number,
            ],
            [
                'user_answer' => $request->answer,
            ]
        );

        return response()->json(['success' => true]);
    }

    public function updateTimeSpent(TestAttempt $attempt, Request $request)
    {
        // Ensure user owns this attempt
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'section' => 'required|in:listening,reading,writing,speaking',
            'time' => 'required|integer|min:0',
        ]);

        $timeSpent = $attempt->time_spent ?? [];
        $timeSpent[$request->section] = $request->time;

        $attempt->update(['time_spent' => $timeSpent]);

        return response()->json(['success' => true]);
    }

    public function submitSection(TestAttempt $attempt, Request $request)
    {
        // Ensure user owns this attempt
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'section' => 'required|in:listening,reading,writing,speaking',
        ]);

        // Auto-score listening and reading sections
        if (in_array($request->section, ['listening', 'reading'])) {
            $this->autoScore($attempt, $request->section);
        }

        return response()->json(['success' => true]);
    }

    public function submit(TestAttempt $attempt)
    {
        // Ensure user owns this attempt
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }

        // Auto-score listening and reading
        $this->autoScore($attempt, 'listening');
        $this->autoScore($attempt, 'reading');

        // Calculate overall band (will be null for writing/speaking until manually graded)
        $overallBand = $attempt->calculateOverallBand();

        $attempt->update([
            'status' => 'completed',
            'completed_at' => now(),
            'overall_band' => $overallBand > 0 ? $overallBand : null,
        ]);

        // Create leaderboard entry if we have an overall band
        if ($overallBand > 0) {
            $attempt->leaderboardEntry()->create([
                'user_id' => $attempt->user_id,
                'test_id' => $attempt->test_id,
                'overall_band' => $overallBand,
                'completion_time' => $attempt->getTotalTimeSpent(),
            ]);
        }

        return redirect()->route('tests.results', $attempt);
    }

    public function results(TestAttempt $attempt)
    {
        // Ensure user owns this attempt
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }

        $userAnswers = $attempt->userAnswers()->get()->groupBy('section');

        return Inertia::render('Tests/Results', [
            'attempt' => [
                'id' => $attempt->id,
                'test_title' => $attempt->test->title,
                'completed_at' => $attempt->completed_at,
                'listening_score' => $attempt->listening_score,
                'reading_score' => $attempt->reading_score,
                'writing_score' => $attempt->writing_score,
                'speaking_score' => $attempt->speaking_score,
                'overall_band' => $attempt->overall_band,
                'time_spent' => $attempt->time_spent,
                'total_time' => $attempt->getTotalTimeSpent(),
            ],
            'userAnswers' => $userAnswers,
            'rank' => $attempt->leaderboardEntry?->getRank($attempt->test_id),
        ]);
    }

    private function autoScore(TestAttempt $attempt, string $section)
    {
        if (! in_array($section, ['listening', 'reading'])) {
            return;
        }

        $test = $attempt->test;
        $userAnswers = $attempt->userAnswers()->where('section', $section)->get();
        $correctAnswers = 0;
        $totalQuestions = 0;

        // Get correct answers from test content
        $contentKey = $section.'_content';
        $testContent = $test->{$contentKey};

        if ($section === 'listening') {
            foreach ($testContent['sections'] ?? [] as $sectionData) {
                foreach ($sectionData['questions'] ?? [] as $question) {
                    $totalQuestions++;
                    $userAnswer = $userAnswers->where('question_number', $question['number'])->first();

                    if ($userAnswer && trim(strtolower($userAnswer->user_answer)) === trim(strtolower($question['answer']))) {
                        $correctAnswers++;
                        $userAnswer->update(['is_correct' => true, 'points_earned' => 1]);
                    } elseif ($userAnswer) {
                        $userAnswer->update(['is_correct' => false, 'points_earned' => 0]);
                    }
                }
            }
        } elseif ($section === 'reading') {
            foreach ($testContent['passages'] ?? [] as $passage) {
                foreach ($passage['questions'] ?? [] as $question) {
                    $totalQuestions++;
                    $userAnswer = $userAnswers->where('question_number', $question['number'])->first();

                    if ($userAnswer && trim(strtolower($userAnswer->user_answer)) === trim(strtolower($question['answer']))) {
                        $correctAnswers++;
                        $userAnswer->update(['is_correct' => true, 'points_earned' => 1]);
                    } elseif ($userAnswer) {
                        $userAnswer->update(['is_correct' => false, 'points_earned' => 0]);
                    }
                }
            }
        }

        // Convert to IELTS band score
        $bandScore = $this->convertToIeltsBand($correctAnswers, $totalQuestions);

        $attempt->update([
            $section.'_score' => $bandScore,
        ]);
    }

    private function convertToIeltsBand(int $correctAnswers, int $totalQuestions): float
    {
        if ($totalQuestions === 0) {
            return 0;
        }

        $percentage = ($correctAnswers / $totalQuestions) * 100;

        // IELTS band conversion (approximate)
        if ($percentage >= 89) {
            return 9.0;
        }
        if ($percentage >= 82) {
            return 8.5;
        }
        if ($percentage >= 75) {
            return 8.0;
        }
        if ($percentage >= 68) {
            return 7.5;
        }
        if ($percentage >= 60) {
            return 7.0;
        }
        if ($percentage >= 53) {
            return 6.5;
        }
        if ($percentage >= 45) {
            return 6.0;
        }
        if ($percentage >= 38) {
            return 5.5;
        }
        if ($percentage >= 30) {
            return 5.0;
        }
        if ($percentage >= 23) {
            return 4.5;
        }

        return 4.0;
    }
}
