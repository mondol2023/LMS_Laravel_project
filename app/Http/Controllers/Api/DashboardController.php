<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaderboardEntry;
use App\Models\Test;
use App\Models\TestAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Get user's recent attempts
        $recentAttempts = TestAttempt::where('user_id', $user->id)
            ->with('test')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    'test_title' => $attempt->test->title,
                    'status' => $attempt->status,
                    'overall_band' => $attempt->overall_band,
                    'started_at' => $attempt->started_at,
                    'completed_at' => $attempt->completed_at,
                ];
            });

        // Get user statistics
        $stats = [
            'total_attempts' => TestAttempt::where('user_id', $user->id)->count(),
            'completed_tests' => TestAttempt::where('user_id', $user->id)->where('status', 'completed')->count(),
            'average_band' => $user->getAverageBandScore(),
            'best_score' => TestAttempt::where('user_id', $user->id)->whereNotNull('overall_band')->max('overall_band') ?? 0,
        ];

        // Get available tests
        $availableTests = Test::where('status', 'published')
            ->withCount(['testAttempts as user_attempts' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->limit(6)
            ->get()
            ->map(function ($test) {
                return [
                    'id' => $test->id,
                    'title' => $test->title,
                    'type' => $test->type,
                    'difficulty' => $test->difficulty,
                    'user_attempts' => $test->user_attempts,
                ];
            });

        // Get recent leaderboard entries for motivation
        $topPerformers = LeaderboardEntry::with('user')
            ->orderByDesc('overall_band')
            ->orderBy('completion_time')
            ->limit(5)
            ->get()
            ->map(function ($entry) {
                return [
                    'user_name' => $entry->user->name,
                    'overall_band' => $entry->overall_band,
                    'completion_time' => $entry->getFormattedCompletionTime(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'recent_attempts' => $recentAttempts,
                'available_tests' => $availableTests,
                'top_performers' => $topPerformers,
            ],
        ]);
    }
}
