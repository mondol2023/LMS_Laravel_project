<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\LeaderboardEntry;
use App\Models\Result;
use App\Models\SubscriptionPackage;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use App\Models\UserSubscription;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $today = Carbon::today();

        // Get user's recent attempts
        $recentAttempts = TestAttempt::where('user_id', $user->id)
            ->with('test')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    'test_title' => $attempt->test->title ?? 'Unknown Test',
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

        // Admin dashboard statistics
        $adminStats = [
            'total_exams' => Exam::count(),
            'total_students' => User::where('role', 'student')->count(),
            'total_results' => Result::count(),
            'today_results' => Result::whereDate('created_at', $today)->count(),
            'today_students' => User::where('role', 'student')->whereDate('created_at', $today)->count(),
            'today_participants' => Result::whereDate('created_at', $today)->distinct('user_id')->count('user_id'),
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
                    'user_name' => $entry->user->name ?? 'Unknown',
                    'overall_band' => $entry->overall_band,
                    'completion_time' => $entry->getFormattedCompletionTime(),
                ];
            });

        // Subscription statistics
        $subscriptionStats = [
            'total_packages' => SubscriptionPackage::count(),
            'active_packages' => SubscriptionPackage::where('is_active', true)->count(),
            'total_subscriptions' => UserSubscription::count(),
            'active_subscriptions' => UserSubscription::where('is_active', true)
                ->where('expires_at', '>', now())
                ->count(),
            'expiring_soon' => UserSubscription::where('is_active', true)
                ->whereBetween('expires_at', [now(), now()->addDays(7)])
                ->count(),
            'expired' => UserSubscription::where('is_active', true)
                ->where('expires_at', '<', now())
                ->count(),
        ];

        // Get packages with subscription counts
        $packages = SubscriptionPackage::query()
            ->withCount(['subscriptions', 'activeSubscriptions'])
            ->withSum('activeSubscriptions as total_revenue', 'id') // placeholder, we'll calculate properly
            ->where('is_active', true)
            ->orderByDesc('active_subscriptions_count')
            ->limit(5)
            ->get()
            ->map(function ($package) {
                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'price' => $package->price,
                    'duration' => $package->duration,
                    'discount' => $package->discount,
                    'discount_type' => $package->discount_type,
                    'final_price' => $package->final_price,
                    'total_subscribers' => $package->subscriptions_count,
                    'active_subscribers' => $package->active_subscriptions_count,
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'adminStats' => $adminStats,
            'recentAttempts' => $recentAttempts,
            'availableTests' => $availableTests,
            'topPerformers' => $topPerformers,
            'subscriptionStats' => $subscriptionStats,
            'packages' => $packages,
        ]);
    }
}
