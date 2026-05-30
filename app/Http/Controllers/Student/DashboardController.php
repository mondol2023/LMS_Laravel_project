<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\PracticeResult;
use App\Models\Result;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the student dashboard.
     */
    public function index(): Response
    {
        $user = Auth::user();

        // Get results linked to this user
        $results = Result::where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->orWhere('phone', $user->phone)
            ->with('exam:id,name,exam_id')
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = $this->calculateStats($results);

        // Get subscription data
        $subscriptionData = null;
        if ($user->hasActiveSubscription()) {
            $subscription = $user->activeSubscription;
            $package = $subscription->package;

            $subscriptionData = [
                'package_name' => $package->name,
                'price' => $package->price,
                'duration' => $package->duration,
                'subscribed_at' => $subscription->subscribed_at,
                'expires_at' => $subscription->expires_at,
                'days_remaining' => $subscription->days_remaining,
                'is_active' => $subscription->isActive(),
                'is_expired' => $subscription->isExpired(),

                // Full Mock Test
                'full_mock_test' => [
                    'enabled' => $package->full_mock_test_limit !== null,
                    'limit' => $package->full_mock_test_limit,
                    'used' => $subscription->full_mock_tests_used,
                    'remaining' => $user->getRemainingFullMockTests(),
                ],

                // Partial Mock Tests
                'partial_tests' => [
                    'reading' => [
                        'enabled' => $package->partial_reading_limit !== null,
                        'limit' => $package->partial_reading_limit,
                        'used' => $subscription->partial_reading_used,
                        'remaining' => $user->getRemainingPartialReading(),
                    ],
                    'writing' => [
                        'enabled' => $package->partial_writing_limit !== null,
                        'limit' => $package->partial_writing_limit,
                        'used' => $subscription->partial_writing_used,
                        'remaining' => $user->getRemainingPartialWriting(),
                    ],
                    'listening' => [
                        'enabled' => $package->partial_listening_limit !== null,
                        'limit' => $package->partial_listening_limit,
                        'used' => $subscription->partial_listening_used,
                        'remaining' => $user->getRemainingPartialListening(),
                    ],
                    'speaking' => [
                        'enabled' => $package->partial_speaking_limit !== null,
                        'limit' => $package->partial_speaking_limit,
                        'used' => $subscription->partial_speaking_used,
                        'remaining' => $user->getRemainingPartialSpeaking(),
                    ],
                ],

                // Practice Modules
                'practice_modules' => [
                    'reading' => [
                        'enabled' => $package->practice_reading_enabled,
                        'unlimited' => true,
                    ],
                    'listening' => [
                        'enabled' => $package->practice_listening_enabled,
                        'unlimited' => true,
                    ],
                    'writing' => [
                        'enabled' => $package->practice_writing_enabled,
                        'limit' => $package->practice_writing_limit,
                        'used' => $subscription->practice_writing_used,
                        'remaining' => $user->getRemainingWritingPractice(),
                    ],
                    'speaking' => [
                        'enabled' => $package->practice_speaking_enabled,
                        'limit' => $package->practice_speaking_limit,
                        'used' => $subscription->practice_speaking_used,
                        'remaining' => $user->getRemainingSpeakingPractice(),
                    ],
                ],
            ];
        }

        return Inertia::render('Student/Dashboard', [
            'user' => $user,
            'stats' => $stats,
            'recentResults' => $results->take(5),
            'subscription' => $subscriptionData,
        ]);
    }

    /**
     * Show student profile.
     */
    public function profile(): Response
    {
        return Inertia::render('Student/Profile', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Show student results.
     */
    public function results(): Response
    {
        $user = Auth::user();

        $results = Result::where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->orWhere('phone', $user->phone)
            ->with('exam:id,name,exam_id')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Student/Results', [
            'results' => $results,
        ]);
    }

    /**
     * Show single result detail.
     */
    public function resultShow(Result $result): Response
    {
        $user = Auth::user();

        // Ensure user owns this result
        if ($result->user_id !== $user->id &&
            $result->email !== $user->email &&
            $result->phone !== $user->phone) {
            abort(403);
        }

        return Inertia::render('Student/ResultDetail', [
            'result' => $result->load('exam'),
        ]);
    }

    /**
     * Show student reports/analytics.
     */
    public function reports(): Response
    {
        $user = Auth::user();

        $results = Result::where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->orWhere('phone', $user->phone)
            ->with('exam:id,name,exam_id')
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = $this->calculateStats($results);

        // Calculate progress over time
        $progressData = $results->map(function ($result) {
            $overall = collect([
                $result->listening['band_score'] ?? 0,
                $result->reading['band_score'] ?? 0,
                $result->writing['band_score'] ?? 0,
                $result->speaking['band_score'] ?? 0,
            ])->filter(fn ($v) => $v > 0)->avg() ?? 0;

            return [
                'date' => $result->created_at->format('M d'),
                'overall' => round($overall, 1),
                'listening' => $result->listening['band_score'] ?? 0,
                'reading' => $result->reading['band_score'] ?? 0,
                'writing' => $result->writing['band_score'] ?? 0,
                'speaking' => $result->speaking['band_score'] ?? 0,
            ];
        })->reverse()->values();

        // Get practice results
        $practiceResults = PracticeResult::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('module');

        // Calculate practice stats per module
        $practiceStats = [];
        foreach (['listening', 'reading', 'writing', 'speaking'] as $module) {
            $moduleResults = $practiceResults->get($module, collect());
            $practiceStats[$module] = [
                'total_attempts' => $moduleResults->count(),
                'avg_score' => round($moduleResults->avg('score'), 1),
                'avg_band' => round($moduleResults->avg('band_score'), 1),
                'best_band' => round($moduleResults->max('band_score'), 1),
                'recent_tests' => $moduleResults->take(5)->map(fn ($r) => [
                    'exam_name' => $r->exam_name,
                    'score' => $r->score,
                    'total' => $r->total,
                    'band_score' => $r->band_score,
                    'date' => $r->created_at->format('M d, Y'),
                ]),
            ];
        }

        return Inertia::render('Student/Reports', [
            'stats' => $stats,
            'progressData' => $progressData,
            'totalResults' => $results->count(),
            'practiceStats' => $practiceStats,
            'practiceResults' => $practiceResults->map(fn ($group) => $group->values()),
        ]);
    }

    /**
     * Calculate statistics from results.
     */
    private function calculateStats($results): array
    {
        $totalExams = $results->count();

        $listeningScores = $results->pluck('listening.band_score')->filter()->values();
        $readingScores = $results->pluck('reading.band_score')->filter()->values();
        $writingScores = $results->pluck('writing.band_score')->filter()->values();
        $speakingScores = $results->pluck('speaking.band_score')->filter()->values();

        $avgListening = $listeningScores->avg() ?? 0;
        $avgReading = $readingScores->avg() ?? 0;
        $avgWriting = $writingScores->avg() ?? 0;
        $avgSpeaking = $speakingScores->avg() ?? 0;

        $allScores = collect([$avgListening, $avgReading, $avgWriting, $avgSpeaking])
            ->filter(fn ($v) => $v > 0);
        $overallAvg = $allScores->avg() ?? 0;

        return [
            'total_exams' => $totalExams,
            'avg_listening' => round($avgListening, 1),
            'avg_reading' => round($avgReading, 1),
            'avg_writing' => round($avgWriting, 1),
            'avg_speaking' => round($avgSpeaking, 1),
            'overall_avg' => round($overallAvg, 1),
            'best_listening' => round($listeningScores->max() ?? 0, 1),
            'best_reading' => round($readingScores->max() ?? 0, 1),
            'best_writing' => round($writingScores->max() ?? 0, 1),
            'best_speaking' => round($speakingScores->max() ?? 0, 1),
        ];
    }
}
