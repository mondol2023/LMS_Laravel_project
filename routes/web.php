<?php

use App\Http\Controllers\Api\WritingEvaluationController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\BkashPaymentController;
use App\Http\Controllers\CoachingIpController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DraftController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\PaymentHistoryController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubscriptionPackageController;
use App\Http\Controllers\SubscriptionUsersController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\UserSubscriptionController;
use App\Models\Exam;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $totalUsers = User::query()->count();
    $totalTests = Test::query()->count();
    $totalAttempts = TestAttempt::query()->count();
    $avgScore = TestAttempt::query()->whereNotNull('overall_band')->avg('overall_band') ?? 0;

    // Get available exams for home page cards
    $exams = Exam::query()
        ->orderBy('created_at', 'desc')
        ->limit(6)
        ->get(['id', 'name', 'exam_id', 'uuid', 'description', 'created_at']);

    return Inertia::render('Welcome', [
        'totalUsers' => $totalUsers,
        'totalTests' => $totalTests,
        'totalAttempts' => $totalAttempts,
        'avgScore' => round($avgScore, 1),
        'exams' => $exams,
    ]);
})->name('home');

// Public routes (always accessible)
Route::get('available-exams', [ExamController::class, 'availableExams'])->name('available-exams');
Route::get('tests', [TestController::class, 'publicIndex'])->name('tests.public');
Route::get('leaderboard', [LeaderboardController::class, 'publicIndex'])->name('leaderboard.public');

// Exam routes - Coaching mode: no login needed, External: login required
Route::middleware(['coaching'])->group(function () {
    Route::post('exam/{examId}/register', [ExamController::class, 'register'])->name('exam.register');
    Route::post('partial-exam/{examId}/register', [ExamController::class, 'registerPartial'])->name('exam.register.partial');
    Route::get('exam/{uuid}', [ExamController::class, 'listBySlug'])->name('exam.slug');
    Route::get('exam/{uuid}/start-exam', [ExamController::class, 'startExam'])->name('exam.start-exam');
    Route::get('exam/{uuid}/listening', [ExamController::class, 'listening'])->name('exam.listening');
    Route::get('exam/{uuid}/reading', [ExamController::class, 'reading'])->name('exam.reading');
    Route::get('exam/{uuid}/writing', [ExamController::class, 'writing'])->name('exam.writing');
    Route::get('exam/{uuid}/speaking', [ExamController::class, 'speaking'])->name('exam.speaking');
});

// Legal pages
Route::get('/terms-and-conditions', function () {
    return Inertia::render('Legal/TermsAndConditions');
})->name('terms-and-conditions');

Route::get('/privacy-policy', function () {
    return Inertia::render('Legal/PrivacyPolicy');
})->name('privacy-policy');

Route::get('/learning-center', function () {
    return Inertia::render('LearningCenter/Index');
})->name('learning-center');

// Protected practice routes - require authentication
Route::middleware(['auth'])->group(function () {
    Route::get('/practice', function () {
        return Inertia::render('Practice/Index', [
            'title' => 'Practice',
        ]);
    })->name('practice');

    Route::get('/writing', function () {
        $user = request()->user();
        $subscription = null;
        $accessError = null;

        if (! $user) {
            $accessError = 'Please login to access writing practice.';
        } elseif (! $user->hasActiveSubscription()) {
            $accessError = 'You do not have an active subscription. Please contact your administrator to get access.';
        } elseif ($user->isSubscriptionExpired()) {
            $accessError = 'Your subscription has expired. Please contact your administrator to renew.';
        } elseif (! $user->isPracticeModuleEnabled('writing')) {
            $accessError = 'Writing practice is not available in your subscription plan.';
        } elseif (! $user->canAccessResource('practice_writing')) {
            $accessError = 'You have reached your limit for Writing practice. Please contact your administrator.';
        }

        if ($user && $user->hasActiveSubscription()) {
            $activeSubscription = $user->activeSubscription;
            $package = $activeSubscription->package;

            $subscription = [
                'practice_writing_enabled' => $package->practice_writing_enabled,
                'practice_writing_limit' => $package->practice_writing_limit,
                'practice_writing_used' => $activeSubscription->practice_writing_used,
                'practice_writing_remaining' => $user->getRemainingWritingPractice(),
                'is_expired' => $user->isSubscriptionExpired(),
            ];
        }

        return Inertia::render('Writing/Index', [
            'subscription' => $subscription,
            'accessError' => $accessError,
        ]);
    })->name('writing');

    Route::get('/speaking', function () {
        $user = request()->user();
        $subscription = null;
        $accessError = null;

        if (! $user) {
            $accessError = 'Please login to access speaking practice.';
        } elseif (! $user->hasActiveSubscription()) {
            $accessError = 'You do not have an active subscription. Please contact your administrator to get access.';
        } elseif ($user->isSubscriptionExpired()) {
            $accessError = 'Your subscription has expired. Please contact your administrator to renew.';
        } elseif (! $user->isPracticeModuleEnabled('speaking')) {
            $accessError = 'Speaking practice is not available in your subscription plan.';
        } elseif (! $user->canAccessResource('practice_speaking')) {
            $accessError = 'You have reached your limit for Speaking practice. Please contact your administrator.';
        }

        if ($user && $user->hasActiveSubscription()) {
            $activeSubscription = $user->activeSubscription;
            $package = $activeSubscription->package;

            $subscription = [
                'practice_speaking_enabled' => $package->practice_speaking_enabled,
                'practice_speaking_limit' => $package->practice_speaking_limit,
                'practice_speaking_used' => $activeSubscription->practice_speaking_used,
                'practice_speaking_remaining' => $user->getRemainingSpeakingPractice(),
                'is_expired' => $user->isSubscriptionExpired(),
            ];
        }

        return Inertia::render('Speaking/Index', [
            'subscription' => $subscription,
            'accessError' => $accessError,
        ]);
    })->name('speaking');

    Route::get('/listening', function () {
        $user = request()->user();
        $subscription = null;
        $accessError = null;

        if (! $user) {
            $accessError = 'Please login to access listening practice.';
        } elseif (! $user->hasActiveSubscription()) {
            $accessError = 'You do not have an active subscription. Please contact your administrator to get access.';
        } elseif ($user->isSubscriptionExpired()) {
            $accessError = 'Your subscription has expired. Please contact your administrator to renew.';
        } elseif (! $user->isPracticeModuleEnabled('listening')) {
            $accessError = 'Listening practice is not available in your subscription plan.';
        }

        if ($user && $user->hasActiveSubscription()) {
            $activeSubscription = $user->activeSubscription;
            $package = $activeSubscription->package;

            $subscription = [
                'practice_listening_enabled' => $package->practice_listening_enabled,
                'is_expired' => $user->isSubscriptionExpired(),
            ];
        }

        return Inertia::render('Listening/Index', [
            'subscription' => $subscription,
            'accessError' => $accessError,
        ]);
    })->name('listening');

    // Listening practice individual parts (testId 1-4 for C20, 5-8 for C19)
    Route::get('/listening/test{testId}/{part}', function ($testId, $part) {
        $testIdFormatted = 'Test'.$testId;
        $partFormatted = ucfirst($part);
        $componentPath = "Listening/{$testIdFormatted}{$partFormatted}";

        return Inertia::render($componentPath, [
            'testId' => $testId,
            'part' => $part,
        ]);
    })->where(['testId' => '[1-8]', 'part' => 'part[1-4]'])->name('listening.practice.part');

    // Practice Results Page - Listening
    Route::get('/practice/results/listening/{testNumber}', function ($testNumber, \Illuminate\Http\Request $request) {
        // Get data from session
        $resultsData = session('practice_results', []);

        if (empty($resultsData)) {
            return redirect()->route('listening')->with('error', 'No results found. Please take a test first.');
        }

        // Get partNumber from query string (e.g., ?part=1)
        $partNumber = $request->query('part') ? (int) $request->query('part') : ($resultsData['partNumber'] ?? null);

        return Inertia::render('Practice/Results/Listening', [
            'testNumber' => (int) $testNumber,
            'userAnswers' => $resultsData['userAnswers'] ?? [],
            'correctAnswers' => $resultsData['correctAnswers'] ?? [],
            'score' => $resultsData['score'] ?? ['correct' => 0, 'incorrect' => 40, 'bandScore' => 0],
            'partNumber' => $partNumber,
        ]);
    })->where('testNumber', '[1-8]')->name('practice.results.listening');

    // Store practice results (POST endpoint)
    Route::post('/practice/results/listening/{testNumber}', function (\Illuminate\Http\Request $request, $testNumber) {
        $score = $request->input('score', []);

        // Store results in session (including partNumber for part-wise results)
        session(['practice_results' => [
            'userAnswers' => $request->input('userAnswers', []),
            'correctAnswers' => $request->input('correctAnswers', []),
            'score' => $score,
            'partNumber' => $request->input('partNumber'),
        ]]);

        // Save to database for reports
        if (auth()->check()) {
            $partNumber = $request->input('partNumber');

            // Map test number to Cambridge level and test in level
            $cambridgeLevel = match (true) {
                $testNumber >= 1 && $testNumber <= 4 => 20,
                $testNumber >= 5 && $testNumber <= 8 => 19,
                default => null,
            };

            $testInLevel = match (true) {
                $testNumber >= 1 && $testNumber <= 4 => $testNumber,
                $testNumber >= 5 && $testNumber <= 8 => $testNumber - 4,
                default => $testNumber,
            };

            $examName = $partNumber
                ? "Listening Academic {$cambridgeLevel} Test{$testInLevel} Part{$partNumber}"
                : "Listening Academic {$cambridgeLevel} Test{$testInLevel}";

            \App\Models\PracticeResult::create([
                'user_id' => auth()->id(),
                'module' => 'listening',
                'exam_name' => $examName,
                'score' => $score['correct'] ?? 0,
                'total' => 40,
                'band_score' => $score['bandScore'] ?? 0,
                'details' => [
                    'part_number' => $partNumber,
                    'user_answers' => $request->input('userAnswers', []),
                ],
            ]);
        }

        return redirect()->route('practice.results.listening', ['testNumber' => $testNumber]);
    })->where('testNumber', '[1-8]')->name('practice.results.listening.store');

    Route::get('/reading', function () {
        $user = request()->user();
        $subscription = null;
        $accessError = null;

        if (! $user) {
            $accessError = 'Please login to access reading practice.';
        } elseif (! $user->hasActiveSubscription()) {
            $accessError = 'You do not have an active subscription. Please contact your administrator to get access.';
        } elseif ($user->isSubscriptionExpired()) {
            $accessError = 'Your subscription has expired. Please contact your administrator to renew.';
        } elseif (! $user->isPracticeModuleEnabled('reading')) {
            $accessError = 'Reading practice is not available in your subscription plan.';
        }

        if ($user && $user->hasActiveSubscription()) {
            $activeSubscription = $user->activeSubscription;
            $package = $activeSubscription->package;

            $subscription = [
                'practice_reading_enabled' => $package->practice_reading_enabled,
                'is_expired' => $user->isSubscriptionExpired(),
            ];
        }

        return Inertia::render('Reading/Index', [
            'subscription' => $subscription,
            'accessError' => $accessError,
        ]);
    })->name('reading');

    // Reading practice individual parts
    Route::get('/reading/test{testId}/{part}', function ($testId, $part) {
        $testIdFormatted = 'Test'.$testId;
        $partFormatted = ucfirst($part);
        $componentPath = "Reading/{$testIdFormatted}{$partFormatted}";

        return Inertia::render($componentPath, [
            'testId' => $testId,
            'part' => $part,
        ]);
    })->where(['testId' => '[1-8]', 'part' => 'part[1-3]'])->name('reading.practice.part');

    // Practice Results Page - Reading
    Route::get('/practice/results/reading/{testNumber}', function ($testNumber, \Illuminate\Http\Request $request) {
        // Get data from session
        $resultsData = session('practice_reading_results', []);

        if (empty($resultsData)) {
            return redirect()->route('reading')->with('error', 'No results found. Please take a test first.');
        }

        // Get partNumber from query string (e.g., ?part=1)
        $partNumber = $request->query('part') ? (int) $request->query('part') : ($resultsData['partNumber'] ?? null);

        return Inertia::render('Practice/Results/Reading', [
            'testNumber' => (int) $testNumber,
            'userAnswers' => $resultsData['userAnswers'] ?? [],
            'correctAnswers' => $resultsData['correctAnswers'] ?? [],
            'score' => $resultsData['score'] ?? ['correct' => 0, 'incorrect' => 40, 'bandScore' => 0],
            'partNumber' => $partNumber,
        ]);
    })->where('testNumber', '[1-8]')->name('practice.results.reading');

    // Store practice results (POST endpoint) - Reading
    Route::post('/practice/results/reading/{testNumber}', function (\Illuminate\Http\Request $request, $testNumber) {
        $score = $request->input('score', []);

        // Store results in session (including partNumber for part-wise results)
        session(['practice_reading_results' => [
            'userAnswers' => $request->input('userAnswers', []),
            'correctAnswers' => $request->input('correctAnswers', []),
            'score' => $score,
            'partNumber' => $request->input('partNumber'),
        ]]);

        // Save to database for reports
        if (auth()->check()) {
            $partNumber = $request->input('partNumber');

            // Map test number to Cambridge level and test in level
            $cambridgeLevel = match (true) {
                $testNumber >= 1 && $testNumber <= 4 => 20,
                $testNumber >= 5 && $testNumber <= 8 => 19,
                default => null,
            };

            $testInLevel = match (true) {
                $testNumber >= 1 && $testNumber <= 4 => $testNumber,
                $testNumber >= 5 && $testNumber <= 8 => $testNumber - 4,
                default => $testNumber,
            };

            $examName = $partNumber
                ? "Reading Academic {$cambridgeLevel} Test{$testInLevel} Part{$partNumber}"
                : "Reading Academic {$cambridgeLevel} Test{$testInLevel}";

            \App\Models\PracticeResult::create([
                'user_id' => auth()->id(),
                'module' => 'reading',
                'exam_name' => $examName,
                'score' => $score['correct'] ?? 0,
                'total' => 40,
                'band_score' => $score['bandScore'] ?? 0,
                'details' => [
                    'part_number' => $partNumber,
                    'user_answers' => $request->input('userAnswers', []),
                ],
            ]);
        }

        return redirect()->route('practice.results.reading', ['testNumber' => $testNumber]);
    })->where('testNumber', '[1-8]')->name('practice.results.reading.store');
});

Route::get('/mock-tests', function () {
    return Inertia::render('MockTests/Index');
})->name('mock-tests');

// AI Writing Evaluation
Route::post('/api/writing/evaluate', [WritingEvaluationController::class, 'evaluate'])->name('writing.evaluate');
Route::post('/api/writing/practice-evaluate', [WritingEvaluationController::class, 'practiceEvaluate'])
    ->name('writing.practice-evaluate')
    ->middleware(['auth', 'subscription:practice_writing']);

// AI Speaking Practice
Route::post('/api/speaking/start-session', [App\Http\Controllers\Api\SpeakingPracticeController::class, 'startSession'])
    ->name('speaking.start-session')
    ->middleware(['auth', 'subscription:practice_speaking']);
Route::post('/api/speaking/next-question', [App\Http\Controllers\Api\SpeakingPracticeController::class, 'getNextQuestion'])
    ->name('speaking.next-question')
    ->middleware(['auth']);
Route::post('/api/speaking/evaluate', [App\Http\Controllers\Api\SpeakingPracticeController::class, 'evaluate'])
    ->name('speaking.evaluate')
    ->middleware(['auth']);

// Draft API routes (public for IELTS exam usage)
Route::prefix('api/drafts')->group(function () {
    Route::post('save', [DraftController::class, 'save'])->name('drafts.save');
    Route::post('save-batch', [DraftController::class, 'saveBatch'])->name('drafts.save-batch');
    Route::get('get', [DraftController::class, 'getDrafts'])->name('drafts.get');
    Route::get('get-section', [DraftController::class, 'getSectionDrafts'])->name('drafts.get-section');
    Route::delete('clear', [DraftController::class, 'clearDrafts'])->name('drafts.clear');
    Route::delete('clear-section', [DraftController::class, 'clearSectionDrafts'])->name('drafts.clear-section');
});

// Admin routes - dashboard & billing always accessible
Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Billing (from Super Admin) - must be accessible to pay invoices
    Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('billing/{invoice}', [BillingController::class, 'show'])->name('billing.show');

    // bKash Payment
    Route::post('billing/{invoice}/pay/bkash', [BkashPaymentController::class, 'initiate'])->name('bkash.pay');
    Route::get('billing/{invoice}/payment/success', [BkashPaymentController::class, 'success'])->name('bkash.success');
    Route::get('billing/{invoice}/payment/cancelled', [BkashPaymentController::class, 'cancelled'])->name('bkash.cancelled');
    Route::get('billing/{invoice}/payment/failed', [BkashPaymentController::class, 'failed'])->name('bkash.failed');
});

// Admin routes - restricted when seller has overdue invoices or is suspended
Route::middleware(['auth', 'verified', 'admin', 'seller.status'])->group(function () {
    // Exam
    Route::get('exam', [ExamController::class, 'show'])->name('exam.show');

    // Leaderboard
    Route::get('leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');

    // Exams
    Route::get('exams', [ExamController::class, 'index'])->name('exams.index');
    Route::get('exams/create', [ExamController::class, 'create'])->name('exams.create');
    Route::post('exams', [ExamController::class, 'store'])->name('exams.store');
    Route::get('exams/{exam}', [ExamController::class, 'show'])->name('exams.show');
    Route::get('exams/{exam}/results', [ExamController::class, 'results'])->name('exams.results');
    Route::get('exams/{exam}/edit', [ExamController::class, 'edit'])->name('exams.edit');
    Route::put('exams/{exam}', [ExamController::class, 'update'])->name('exams.update');
    Route::delete('exams/{exam}', [ExamController::class, 'destroy'])->name('exams.destroy');

    // Students
    Route::get('students', [StudentController::class, 'index'])->name('students.index');
    Route::get('students/create', [StudentController::class, 'create'])->name('students.create');
    Route::post('students', [StudentController::class, 'store'])->name('students.store');
    Route::get('students/{student}', [StudentController::class, 'show'])->name('students.show');
    Route::get('students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
    Route::put('students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');

    // Coupons
    Route::get('coupons', [CouponController::class, 'index'])->name('coupons.index');
    Route::get('coupons/create', [CouponController::class, 'create'])->name('coupons.create');
    Route::post('coupons', [CouponController::class, 'store'])->name('coupons.store');
    Route::get('coupons/{coupon}/edit', [CouponController::class, 'edit'])->name('coupons.edit');
    Route::put('coupons/{coupon}', [CouponController::class, 'update'])->name('coupons.update');
    Route::delete('coupons/{coupon}', [CouponController::class, 'destroy'])->name('coupons.destroy');

    // Subscription Packages
    Route::get('subscriptions/packages', [SubscriptionPackageController::class, 'index'])->name('packages.index');
    Route::get('subscriptions/packages/create', [SubscriptionPackageController::class, 'create'])->name('packages.create');
    Route::post('subscriptions/packages', [SubscriptionPackageController::class, 'store'])->name('packages.store');
    Route::get('subscriptions/packages/{package}/edit', [SubscriptionPackageController::class, 'edit'])->name('packages.edit');
    Route::put('subscriptions/packages/{package}', [SubscriptionPackageController::class, 'update'])->name('packages.update');
    Route::delete('subscriptions/packages/{package}', [SubscriptionPackageController::class, 'destroy'])->name('packages.destroy');
    Route::post('subscriptions/packages/{package}/toggle', [SubscriptionPackageController::class, 'toggleStatus'])->name('packages.toggle');

    // Subscription User Assignment
    Route::get('subscriptions/users', [SubscriptionUsersController::class, 'index'])->name('subscriptions.users.index');
    Route::post('subscriptions/users', [SubscriptionUsersController::class, 'store'])->name('subscriptions.users.store');
    Route::delete('subscriptions/users/{subscription}', [SubscriptionUsersController::class, 'destroy'])->name('subscriptions.users.destroy');
    Route::post('subscriptions/users/bulk-assign', [SubscriptionUsersController::class, 'bulkAssign'])->name('subscriptions.users.bulk-assign');
    Route::post('subscriptions/users/bulk-remove', [SubscriptionUsersController::class, 'bulkRemove'])->name('subscriptions.users.bulk-remove');

    // User Subscription Management
    Route::get('subscriptions/users/{user}', [UserSubscriptionController::class, 'show'])->name('subscriptions.show');
    Route::post('subscriptions/assign', [UserSubscriptionController::class, 'assign'])->name('subscriptions.assign');
    Route::post('subscriptions/users/{user}/upgrade', [UserSubscriptionController::class, 'upgrade'])->name('subscriptions.upgrade');
    Route::post('subscriptions/users/{user}/renew', [UserSubscriptionController::class, 'renew'])->name('subscriptions.renew');
    Route::post('subscriptions/users/{user}/cancel', [UserSubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

    // Payment History
    Route::get('payment-history', [PaymentHistoryController::class, 'index'])->name('payment-history.index');
    Route::get('payment-history/{payment}', [PaymentHistoryController::class, 'show'])->name('payment-history.show');
    Route::post('payment-history/{payment}/add-payment', [PaymentHistoryController::class, 'recordAdditionalPayment'])->name('payment-history.add-payment');

    // Coaching IPs Management
    Route::get('settings/coaching-ips', [CoachingIpController::class, 'index'])->name('coaching-ips.index');
    Route::get('settings/coaching-ips/create', [CoachingIpController::class, 'create'])->name('coaching-ips.create');
    Route::post('settings/coaching-ips', [CoachingIpController::class, 'store'])->name('coaching-ips.store');
    Route::get('settings/coaching-ips/{coachingIp}/edit', [CoachingIpController::class, 'edit'])->name('coaching-ips.edit');
    Route::put('settings/coaching-ips/{coachingIp}', [CoachingIpController::class, 'update'])->name('coaching-ips.update');
    Route::delete('settings/coaching-ips/{coachingIp}', [CoachingIpController::class, 'destroy'])->name('coaching-ips.destroy');
    Route::post('settings/coaching-ips/{coachingIp}/toggle', [CoachingIpController::class, 'toggleStatus'])->name('coaching-ips.toggle');
    Route::post('settings/coaching-ips/add-current', [CoachingIpController::class, 'addCurrentIp'])->name('coaching-ips.add-current');

    //    results
    Route::get('/results', [ResultController::class, 'index'])->name('results.index');
    Route::get('/results/{result}', [ResultController::class, 'show'])->name('results.show');
    Route::get('/results/{result}/details', [ResultController::class, 'showJson'])->name('results.details');
    Route::get('/results/{result}/pdf', [ResultController::class, 'viewPdf'])->name('results.pdf');
    Route::post('/results/{result}/send-email', [ResultController::class, 'sendEmail'])->name('results.sendEmail');
    Route::post('/results/{result}/send-pdf', [ResultController::class, 'sendPdf'])->name('results.sendPdf');
    Route::post('/results/{result}/regenerate-pdf', [ResultController::class, 'regeneratePdf'])->name('results.regeneratePdf');
    Route::post('/results/{result}/upload-pdf', [ResultController::class, 'uploadPdf'])->name('results.uploadPdf');
    Route::put('/results/{result}/writing', [ResultController::class, 'updateWriting'])->name('results.update-writing');
    Route::put('/results/{result}/speaking', [ResultController::class, 'updateSpeaking'])->name('results.update-speaking');
    Route::delete('/results/{result}', [ResultController::class, 'destroy'])->name('results.destroy');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

// Admin routes
Route::prefix('admin')->group(base_path('routes/admin.php'));

// Student portal routes
Route::prefix('student')->group(base_path('routes/student.php'));
