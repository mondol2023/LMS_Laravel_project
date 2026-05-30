<?php

namespace App\Console\Commands;

use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SystemStatusCommand extends Command
{
    protected $signature = 'ielts:status';

    protected $description = 'Display system status and statistics for IELTS application';

    public function handle(): int
    {
        $this->info('IELTS Mock Test Application - System Status');
        $this->newLine();

        // Basic statistics
        $userCount = User::count();
        $adminCount = User::where('role', 'admin')->count();
        $studentCount = User::where('role', 'student')->count();
        $testCount = Test::count();
        $attemptCount = TestAttempt::count();
        $completedAttempts = TestAttempt::where('status', 'completed')->count();
        $inProgressAttempts = TestAttempt::where('status', 'in_progress')->count();

        $this->table(['Metric', 'Count'], [
            ['Total Users', number_format($userCount)],
            ['Admin Users', number_format($adminCount)],
            ['Student Users', number_format($studentCount)],
            ['Total Tests', number_format($testCount)],
            ['Total Attempts', number_format($attemptCount)],
            ['Completed Attempts', number_format($completedAttempts)],
            ['In Progress Attempts', number_format($inProgressAttempts)],
        ]);

        $this->newLine();

        // Performance statistics
        if ($completedAttempts > 0) {
            $avgOverallBand = TestAttempt::whereNotNull('overall_band')->avg('overall_band');
            $avgListeningScore = TestAttempt::whereNotNull('listening_score')->avg('listening_score');
            $avgReadingScore = TestAttempt::whereNotNull('reading_score')->avg('reading_score');
            $avgWritingScore = TestAttempt::whereNotNull('writing_score')->avg('writing_score');
            $avgSpeakingScore = TestAttempt::whereNotNull('speaking_score')->avg('speaking_score');

            $this->info('Average Scores');
            $this->table(['Section', 'Average Score'], [
                ['Overall Band', number_format($avgOverallBand, 2)],
                ['Listening', number_format($avgListeningScore, 2)],
                ['Reading', number_format($avgReadingScore, 2)],
                ['Writing', $avgWritingScore ? number_format($avgWritingScore, 2) : 'N/A'],
                ['Speaking', $avgSpeakingScore ? number_format($avgSpeakingScore, 2) : 'N/A'],
            ]);

            $this->newLine();
        }

        // Top performers
        $topPerformers = TestAttempt::with('user')
            ->whereNotNull('overall_band')
            ->orderBy('overall_band', 'desc')
            ->take(5)
            ->get();

        if ($topPerformers->count() > 0) {
            $this->info('Top 5 Performers');
            $topPerformersData = $topPerformers->map(function ($attempt) {
                return [
                    $attempt->user->name,
                    $attempt->user->country,
                    $attempt->overall_band,
                    $attempt->completed_at->format('Y-m-d'),
                ];
            })->toArray();

            $this->table(['Name', 'Country', 'Band Score', 'Date'], $topPerformersData);
            $this->newLine();
        }

        // Recent activity
        $recentAttempts = TestAttempt::with('user', 'test')
            ->latest()
            ->take(5)
            ->get();

        if ($recentAttempts->count() > 0) {
            $this->info('Recent Test Attempts');
            $recentData = $recentAttempts->map(function ($attempt) {
                return [
                    $attempt->user->name,
                    $attempt->test->title,
                    $attempt->status,
                    $attempt->overall_band ?? 'Pending',
                    $attempt->started_at->format('M d, H:i'),
                ];
            })->toArray();

            $this->table(['User', 'Test', 'Status', 'Score', 'Started'], $recentData);
            $this->newLine();
        }

        // Country distribution
        $countryStats = User::select('country', DB::raw('count(*) as count'))
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderBy('count', 'desc')
            ->take(10)
            ->get();

        if ($countryStats->count() > 0) {
            $this->info('Top 10 Countries by User Count');
            $countryData = $countryStats->map(function ($stat) {
                return [$stat->country, $stat->count];
            })->toArray();

            $this->table(['Country', 'Users'], $countryData);
            $this->newLine();
        }

        // System health checks
        $this->info('System Health Checks');
        $healthChecks = [
            ['Database Connection', $this->checkDatabase()],
            ['Cache System', $this->checkCache()],
            ['Storage Writable', $this->checkStorage()],
            ['Queue System', $this->checkQueue()],
        ];

        $this->table(['Component', 'Status'], $healthChecks);

        return self::SUCCESS;
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();

            return '✅ OK';
        } catch (\Exception $e) {
            return '❌ Failed';
        }
    }

    private function checkCache(): string
    {
        try {
            cache()->put('health_check', 'test', 1);
            $result = cache()->get('health_check');

            return $result === 'test' ? '✅ OK' : '❌ Failed';
        } catch (\Exception $e) {
            return '❌ Failed';
        }
    }

    private function checkStorage(): string
    {
        try {
            $testFile = storage_path('logs/health_check.txt');
            file_put_contents($testFile, 'test');
            $result = file_get_contents($testFile);
            unlink($testFile);

            return $result === 'test' ? '✅ OK' : '❌ Failed';
        } catch (\Exception $e) {
            return '❌ Failed';
        }
    }

    private function checkQueue(): string
    {
        try {
            // Simple check if queue config is accessible
            config('queue.default');

            return '✅ OK';
        } catch (\Exception $e) {
            return '❌ Failed';
        }
    }
}
