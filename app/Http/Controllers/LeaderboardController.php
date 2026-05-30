<?php

namespace App\Http\Controllers;

use App\Models\LeaderboardEntry;
use App\Models\Test;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $testId = $request->get('test_id');
        $type = $request->get('type', 'overall'); // overall, academic, general_training

        $query = LeaderboardEntry::with(['user', 'test'])
            ->orderByDesc('overall_band')
            ->orderBy('completion_time');

        // Filter by specific test
        if ($testId) {
            $query->where('test_id', $testId);
        }

        // Filter by test type
        if ($type !== 'overall') {
            $query->whereHas('test', function ($q) use ($type) {
                $q->where('type', $type);
            });
        }

        $leaderboard = $query->paginate(50)->through(function ($entry, $index) {
            return [
                'rank' => $index + 1,
                'user_name' => $entry->user->name,
                'user_country' => $entry->user->country,
                'test_title' => $entry->test->title,
                'test_type' => $entry->test->type,
                'overall_band' => $entry->overall_band,
                'completion_time' => $entry->getFormattedCompletionTime(),
                'created_at' => $entry->created_at,
                'is_current_user' => $entry->user_id === auth()->id(),
            ];
        });

        // Get available tests for filtering
        $tests = Test::where('status', 'published')
            ->select('id', 'title', 'type')
            ->get();

        // Get current user's best entry for comparison
        $userBestEntry = LeaderboardEntry::where('user_id', auth()->id())
            ->when($testId, fn ($q) => $q->where('test_id', $testId))
            ->when($type !== 'overall', fn ($q) => $q->whereHas('test', fn ($sq) => $sq->where('type', $type)))
            ->orderByDesc('overall_band')
            ->orderBy('completion_time')
            ->first();

        $userRank = null;
        if ($userBestEntry) {
            $userRank = $userBestEntry->getRank($testId);
        }

        return Inertia::render('Leaderboard/Index', [
            'leaderboard' => $leaderboard,
            'tests' => $tests,
            'filters' => [
                'test_id' => $testId,
                'type' => $type,
            ],
            'userRank' => $userRank,
            'userBestScore' => $userBestEntry?->overall_band,
        ]);
    }

    public function publicIndex(Request $request)
    {
        $testId = $request->get('test_id');
        $type = $request->get('type', 'overall'); // overall, academic, general_training

        $query = LeaderboardEntry::with(['user', 'test'])
            ->orderByDesc('overall_band')
            ->orderBy('completion_time');

        // Filter by specific test
        if ($testId) {
            $query->where('test_id', $testId);
        }

        // Filter by test type
        if ($type !== 'overall') {
            $query->whereHas('test', function ($q) use ($type) {
                $q->where('type', $type);
            });
        }

        $leaderboard = $query->paginate(50)->through(function ($entry, $index) {
            return [
                'rank' => $index + 1,
                'user_name' => $entry->user->name,
                'user_country' => $entry->user->country,
                'test_title' => $entry->test->title,
                'test_type' => $entry->test->type,
                'overall_band' => $entry->overall_band,
                'completion_time' => $entry->getFormattedCompletionTime(),
                'created_at' => $entry->created_at,
                'is_current_user' => false, // No user context for public view
            ];
        });

        // Get available tests for filtering
        $tests = Test::where('status', 'published')
            ->select('id', 'title', 'type')
            ->get();

        return Inertia::render('Leaderboard/PublicIndex', [
            'leaderboard' => $leaderboard,
            'tests' => $tests,
            'filters' => [
                'test_id' => $testId,
                'type' => $type,
            ],
        ]);
    }
}
