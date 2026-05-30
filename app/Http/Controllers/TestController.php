<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\TestAttempt;
use Inertia\Inertia;

class TestController extends Controller
{
    public function index()
    {
        $tests = Test::where('status', 'published')
            ->withCount(['testAttempts as attempts_count' => function ($query) {
                $query->where('user_id', auth()->id());
            }])
            ->get()
            ->map(function ($test) {
                return [
                    'id' => $test->id,
                    'title' => $test->title,
                    'type' => $test->type,
                    'difficulty' => $test->difficulty,
                    'attempts_count' => $test->attempts_count,
                    'average_score' => $test->getAverageScore(),
                    'completed_attempts' => $test->getCompletedAttemptsCount(),
                ];
            });

        return Inertia::render('Tests/Index', [
            'tests' => $tests,
        ]);
    }

    public function show(Test $test)
    {
        $userAttempts = TestAttempt::where('test_id', $test->id)
            ->where('user_id', auth()->id())
            ->with('leaderboardEntry')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Tests/Show', [
            'test' => [
                'id' => $test->id,
                'title' => $test->title,
                'type' => $test->type,
                'difficulty' => $test->difficulty,
                'listening_sections' => count($test->listening_content['sections'] ?? []),
                'reading_passages' => count($test->reading_content['passages'] ?? []),
                'writing_tasks' => 2, // Always Task 1 and Task 2
                'speaking_parts' => 3, // Always Part 1, 2, and 3
            ],
            'userAttempts' => $userAttempts->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    'attempt_number' => $attempt->attempt_number,
                    'status' => $attempt->status,
                    'started_at' => $attempt->started_at,
                    'completed_at' => $attempt->completed_at,
                    'overall_band' => $attempt->overall_band,
                    'listening_score' => $attempt->listening_score,
                    'reading_score' => $attempt->reading_score,
                    'writing_score' => $attempt->writing_score,
                    'speaking_score' => $attempt->speaking_score,
                    'rank' => $attempt->leaderboardEntry?->getRank($attempt->test_id),
                ];
            }),
        ]);
    }

    public function start(Test $test)
    {
        // Check if user has an in-progress attempt
        $existingAttempt = TestAttempt::where('test_id', $test->id)
            ->where('user_id', auth()->id())
            ->where('status', 'in_progress')
            ->first();

        if ($existingAttempt) {
            return redirect()->route('attempt.show', $existingAttempt);
        }

        // Get the next attempt number for this user/test combination
        $attemptNumber = TestAttempt::where('test_id', $test->id)
            ->where('user_id', auth()->id())
            ->max('attempt_number') + 1;

        // Create new attempt
        $attempt = TestAttempt::create([
            'user_id' => auth()->id(),
            'test_id' => $test->id,
            'attempt_number' => $attemptNumber,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return redirect()->route('attempt.show', $attempt);
    }

    public function publicIndex()
    {
        $tests = Test::where('status', 'published')
            ->get()
            ->map(function ($test) {
                return [
                    'id' => $test->id,
                    'title' => $test->title,
                    'type' => $test->type,
                    'difficulty' => $test->difficulty,
                    'average_score' => $test->getAverageScore(),
                    'completed_attempts' => $test->getCompletedAttemptsCount(),
                    'listening_sections' => count($test->listening_content['sections'] ?? []),
                    'reading_passages' => count($test->reading_content['passages'] ?? []),
                    'writing_tasks' => 2,
                    'speaking_parts' => 3,
                ];
            });

        return Inertia::render('Tests/PublicIndex', [
            'tests' => $tests,
        ]);
    }
}
