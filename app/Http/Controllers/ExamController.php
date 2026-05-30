<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExamRequest;
use App\Http\Requests\UpdateExamRequest;
use App\Models\Exam;
use App\Models\Result;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
    public function index(Request $request): Response
    {
        $type = $request->get('type', 'exam');

        $exams = Exam::query()
            ->where('type', $type)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Exams/Index', [
            'exams' => $exams,
            'currentType' => $type,
        ]);
    }

    public function availableExams(): Response
    {
        $exams = Exam::query()->orderBy('created_at')->get();

        return Inertia::render('AvailableExams', [
            'exams' => $exams,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Exams/Create');
    }

    public function store(StoreExamRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['type'] = 'exam';

        $exam = Exam::query()->create($validated);

        return redirect()->route('exams.index')
            ->with('success', "Exam '{$exam->name}' created successfully with ID: {$exam->exam_id}");
    }

    public function show(Exam $exam): Response
    {
        info("Viewing exam: {$exam->name} (ID: {$exam->exam_id})");

        return Inertia::render('Exams/Show', [
            'exam' => $exam,
        ]);
    }

    public function results(Exam $exam): Response
    {
        $results = $exam->results()
            ->select([
                'id',
                'exam_id',
                'user_id',
                'local_exam_id',
                'username',
                'phone',
                'email',
                'passport',
                'exam_type',
                'modules',
                'reading',
                'writing',
                'listening',
                'speaking',
                'data',
                'created_at',
                'updated_at',
            ])
            ->with('user:id,roll_number')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Exams/Results', [
            'exam' => $exam,
            'results' => $results,
        ]);
    }

    public function listBySlug(string $uuid): Response
    {
        // Find exam by UUID
        $exam = Exam::query()->where('uuid', $uuid)->first();

        if (!$exam) {
            return Inertia::render('Exam/NotFound', [
                'examId' => $uuid,
                'message' => 'No corresponding exam found for the provided exam ID.',
            ]);
        }

        // Use exam_id internally for component resolution
        $examId = $exam->exam_id;

        // Fetch result from session
        $resultId = session('result_id');
        $result = null;
        $availableModules = ['listening', 'reading', 'writing']; // Default: all modules
        $subscriptionInfo = null;

        if ($resultId) {
            $result = Result::query()->with('user')->find($resultId);
            if ($result && $result->modules) {
                $availableModules = $result->modules;
            }

            // Get subscription information for the user
            if ($result && $result->user) {
                $user = $result->user;
                $subscription = $user->activeSubscription;

                if ($subscription) {
                    $subscriptionInfo = [
                        'is_active' => $subscription->isActive(),
                        'is_expired' => $subscription->isExpired(),
                        'expires_at' => $subscription->expires_at?->format('Y-m-d'),
                        'days_remaining' => $subscription->days_remaining,
                        'exam_type' => $result->exam_type,
                        'remaining_full_mock_tests' => $user->getRemainingFullMockTests(),
                        'remaining_partial_reading' => $user->getRemainingPartialReading(),
                        'remaining_partial_writing' => $user->getRemainingPartialWriting(),
                        'remaining_partial_listening' => $user->getRemainingPartialListening(),
                        'package_name' => $subscription->package->name ?? 'Unknown',
                        'used_full_mock_tests' => $subscription->full_mock_tests_used,
                        'used_partial_reading' => $subscription->partial_reading_used,
                        'used_partial_writing' => $subscription->partial_writing_used,
                        'used_partial_listening' => $subscription->partial_listening_used,
                    ];
                }
            }
        }

        // Static exam design for checking purposes
        $examData = [
            'id' => $uuid,
            'name' => 'IELTS Academic Mock Test',
            'type' => 'academic',
            'duration' => 152,
            'sections' => [
                [
                    'name' => 'Listening',
                    'duration' => 32,
                    'questions' => 40,
                    'description' => 'Listen to recordings and answer questions',
                ],
                [
                    'name' => 'Reading',
                    'duration' => 60,
                    'questions' => 40,
                    'description' => 'Read passages and answer questions',
                ],
                [
                    'name' => 'Writing',
                    'duration' => 60,
                    'questions' => 2,
                    'description' => 'Complete two writing tasks',
                ],
            ],
            'instructions' => [
                'Read all instructions carefully before starting',
                'You cannot go back to previous sections once completed',
                'Manage your time wisely',
                'Ensure you have a stable internet connection',
                'Have writing materials ready for the Writing section',
            ],
        ];

        // Filter sections based on available modules
        $examData['sections'] = array_values(array_filter($examData['sections'], function ($section) use ($availableModules) {
            return in_array(strtolower($section['name']), $availableModules);
        }));

        // Recalculate total duration based on filtered sections
        $examData['duration'] = array_sum(array_column($examData['sections'], 'duration'));

        // Check if there's a specific exam folder for this examId
        $examPagePath = 'Exam/' . ucfirst($examId) . '/Index';

        // Check if the exam folder/component exists
        $examComponentPath = resource_path("js/pages/{$examPagePath}.vue");

        if (file_exists($examComponentPath)) {
            // Render the specific exam folder's index page
            return Inertia::render($examPagePath, [
                'exam' => $examData,
                'slug' => $uuid,
                'examId' => $examId,
                'result' => $result,
                'availableModules' => $availableModules,
                'subscription' => $subscriptionInfo,
            ]);
        } else {
            // Show the error page if no matching exam found
            return Inertia::render('Exam/NotFound', [
                'examId' => $uuid,
                'message' => 'No corresponding exam found for the provided exam ID.',
            ]);
        }
    }

    public function startExam(string $uuid): Response
    {
        // Find exam by UUID
        $exam = Exam::query()->where('uuid', $uuid)->first();

        if (!$exam) {
            return Inertia::render('Exam/NotFound', [
                'examId' => $uuid,
                'message' => 'No corresponding exam found for the provided exam ID.',
            ]);
        }

        return Inertia::render('Exam/StartExam', [
            'slug' => $uuid,
            'examId' => $exam->exam_id,
        ]);
    }

    public function listening(string $uuid): Response
    {
        // Find exam by UUID
        $exam = Exam::query()->where('uuid', $uuid)->first();

        if (!$exam) {
            return Inertia::render('Exam/NotFound', [
                'examId' => $uuid,
                'message' => 'No corresponding exam found for the provided exam ID.',
            ]);
        }

        $examId = $exam->exam_id;

        // Fetch result to get username
        $resultId = session('result_id');
        $result = $resultId ? Result::query()->find($resultId) : null;
        $username = $result?->username ?? 'Guest';

        // Try to render the specific exam folder's section page
        $examSectionPath = 'Exam/' . ucfirst($examId) . '/Sections/Listening';

        try {
            return Inertia::render($examSectionPath, [
                'slug' => $uuid,
                'examId' => $examId,
                'section' => 'listening',
                'resultId' => $resultId,
                'username' => $username,
            ]);
        } catch (\Exception $e) {
            // Fallback to generic section page if specific exam folder doesn't exist
            return Inertia::render('Exam/Sections/Listening', [
                'slug' => $uuid,
                'examId' => $examId,
                'section' => 'listening',
                'resultId' => $resultId,
                'username' => $username,
            ]);
        }
    }

    public function reading(string $uuid): Response
    {
        // Find exam by UUID
        $exam = Exam::query()->where('uuid', $uuid)->first();

        if (!$exam) {
            return Inertia::render('Exam/NotFound', [
                'examId' => $uuid,
                'message' => 'No corresponding exam found for the provided exam ID.',
            ]);
        }

        $examId = $exam->exam_id;

        // Fetch result to get username
        $resultId = session('result_id');
        $result = $resultId ? Result::query()->find($resultId) : null;
        $username = $result?->username ?? 'Guest';

        // Try to render the specific exam folder's section page
        $examSectionPath = 'Exam/' . ucfirst($examId) . '/Sections/Reading';

        try {
            return Inertia::render($examSectionPath, [
                'slug' => $uuid,
                'examId' => $examId,
                'section' => 'reading',
                'resultId' => $resultId,
                'username' => $username,
            ]);
        } catch (\Exception $e) {
            // Fallback to generic section page if specific exam folder doesn't exist
            return Inertia::render('Exam/Sections/Reading', [
                'slug' => $uuid,
                'examId' => $examId,
                'section' => 'reading',
                'resultId' => $resultId,
                'username' => $username,
            ]);
        }
    }

    public function writing(string $uuid): Response
    {
        // Find exam by UUID
        $exam = Exam::query()->where('uuid', $uuid)->first();

        if (!$exam) {
            return Inertia::render('Exam/NotFound', [
                'examId' => $uuid,
                'message' => 'No corresponding exam found for the provided exam ID.',
            ]);
        }

        $examId = $exam->exam_id;

        // Fetch result to get username
        $resultId = session('result_id');
        $result = $resultId ? Result::query()->find($resultId) : null;
        $username = $result?->username ?? 'Guest';

        // Try to render the specific exam folder's section page
        $examSectionPath = 'Exam/' . ucfirst($examId) . '/Sections/Writing';

        try {
            return Inertia::render($examSectionPath, [
                'slug' => $uuid,
                'examId' => $examId,
                'section' => 'writing',
                'resultId' => $resultId,
                'username' => $username,
            ]);
        } catch (\Exception $e) {
            // Fallback to generic section page if specific exam folder doesn't exist
            return Inertia::render('Exam/Sections/Writing', [
                'slug' => $uuid,
                'examId' => $examId,
                'section' => 'writing',
                'resultId' => $resultId,
                'username' => $username,
            ]);
        }
    }

    public function speaking(string $uuid): Response
    {
        // Find exam by UUID
        $exam = Exam::query()->where('uuid', $uuid)->first();

        if (!$exam) {
            return Inertia::render('Exam/NotFound', [
                'examId' => $uuid,
                'message' => 'No corresponding exam found for the provided exam ID.',
            ]);
        }

        $examId = $exam->exam_id;

        // Try to render the specific exam folder's section page
        $examSectionPath = 'Exam/' . ucfirst($examId) . '/Sections/Speaking';

        try {
            return Inertia::render($examSectionPath, [
                'slug' => $uuid,
                'examId' => $examId,
                'section' => 'speaking',
            ]);
        } catch (\Exception $e) {
            // Fallback to generic section page if specific exam folder doesn't exist
            return Inertia::render('Exam/Sections/Speaking', [
                'slug' => $uuid,
                'examId' => $examId,
                'section' => 'speaking',
            ]);
        }
    }

    public function edit(Exam $exam): Response
    {
        return Inertia::render('Exams/Edit', [
            'exam' => $exam,
        ]);
    }

    public function update(UpdateExamRequest $request, Exam $exam): RedirectResponse
    {
        $validated = $request->validated();

        $exam->update($validated);

        return redirect()->route('exams.index')
            ->with('success', "Exam '{$exam->name}' updated successfully.");
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $examName = $exam->name;
        $exam->delete();

        return redirect()->route('exams.index')
            ->with('success', "Exam '{$examName}' deleted successfully.");
    }

    public function register(Request $request, string $examId): RedirectResponse
    {
        // Check if we're in coaching mode (set by middleware)
        $isCoachingMode = $request->attributes->get('is_coaching_mode', false);

        // Validate registration form
        $validated = $request->validate([
            'local_exam_id' => ['required', 'string', 'max:100'],
            'roll_number' => ['required', 'string', 'max:100'],
        ]);

        // Find the exam by user_exam_id (what user provides)
        $exam = Exam::query()->where('user_exam_id', $validated['local_exam_id'])->first();

        if (!$exam) {
            return back()->withErrors(['local_exam_id' => 'Exam not found with the provided Exam ID.']);
        }

        // Find student by roll number
        $student = User::query()->where('roll_number', $validated['roll_number'])->first();

        if (!$student) {
            return back()->withErrors(['roll_number' => 'Student not found with the provided roll number.']);
        }

        // Only check subscription for non-coaching mode (external access)
        if (!$isCoachingMode) {
            // Check if student has active subscription
            if (!$student->hasActiveSubscription()) {
                return back()->withErrors(['roll_number' => 'You do not have an active subscription. Please subscribe to access mock tests.']);
            }

            // Check if student has exceeded subscription limits for full mock test
            $remaining = $student->getRemainingFullMockTests();
            if ($remaining !== null && $remaining <= 0) {
                return back()->withErrors(['roll_number' => 'You have used all your full mock test attempts. Please upgrade your subscription to continue.']);
            }

            // Check if subscription is expired
            if ($student->isSubscriptionExpired()) {
                return back()->withErrors(['roll_number' => 'Your subscription has expired. Please renew to access mock tests.']);
            }
        }

        // Check if student already registered for this exam as full mock test
        $existingFullResult = Result::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $student->id)
            ->where('exam_type', 'full')
            ->first();

        if ($existingFullResult) {
            return back()->withErrors(['roll_number' => 'You have already registered for this full mock test exam. Each student can only take the full mock test once.']);
        }

        // Create a new result record for full mock test
        $result = Result::query()->create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'local_exam_id' => $exam->exam_id,
            'username' => $student->name,
            'phone' => $student->phone ?? '',
            'email' => $student->email ?? '',
            'passport' => $student->passport ?? '',
            'exam_type' => 'full',
            'modules' => ['listening', 'reading', 'writing'],
            'reading' => [],
            'writing' => [],
            'listening' => [],
            'speaking' => null,
            'data' => [],
        ]);

        // NOTE: Usage counter will be incremented when exam is submitted/completed
        // Store result ID in session for tracking
        session(['result_id' => $result->id]);

        // Redirect to exam page using UUID
        return redirect()
            ->route('exam.slug', $exam->uuid)
            ->with('success', 'Registration successful! You can now start the exam.');
    }

    public function registerPartial(Request $request, string $examId): RedirectResponse
    {
        // Check if we're in coaching mode (set by middleware)
        $isCoachingMode = $request->attributes->get('is_coaching_mode', false);

        // Validate registration form
        $validated = $request->validate([
            'local_exam_id' => ['required', 'string', 'max:100'],
            'roll_number' => ['required', 'string', 'max:100'],
            'modules' => ['required', 'array', 'min:1'],
            'modules.*' => ['required', 'string', 'in:listening,reading,writing'],
        ]);

        // Find the exam by user_exam_id (what user provides)
        $exam = Exam::query()->where('user_exam_id', $validated['local_exam_id'])->first();

        if (!$exam) {
            return back()->withErrors(['local_exam_id' => 'Exam not found with the provided Exam ID.']);
        }

        // Find student by roll number
        $student = User::query()->where('roll_number', $validated['roll_number'])->first();

        if (!$student) {
            return back()->withErrors(['roll_number' => 'Student not found with the provided roll number.']);
        }

        // Only check subscription for non-coaching mode (external access)
        if (!$isCoachingMode) {
            // Check if student has active subscription
            if (!$student->hasActiveSubscription()) {
                return back()->withErrors(['roll_number' => 'You do not have an active subscription. Please subscribe to access mock tests.']);
            }

            // Check if subscription is expired
            if ($student->isSubscriptionExpired()) {
                return back()->withErrors(['roll_number' => 'Your subscription has expired. Please renew to access mock tests.']);
            }

            // Check subscription limits for each requested module
            $limitErrors = [];
            foreach ($validated['modules'] as $module) {
                $remaining = match ($module) {
                    'reading' => $student->getRemainingPartialReading(),
                    'writing' => $student->getRemainingPartialWriting(),
                    'listening' => $student->getRemainingPartialListening(),
                    default => null,
                };

                if ($remaining !== null && $remaining <= 0) {
                    $limitErrors[] = ucfirst($module);
                }
            }

            if (!empty($limitErrors)) {
                return back()->withErrors(['modules' => 'You have reached the limit for: ' . implode(', ', $limitErrors) . '. Please upgrade your subscription.']);
            }
        }

        // Get all existing partial results for this student and exam
        $existingResults = Result::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $student->id)
            ->where('exam_type', 'partial')
            ->get();

        // Collect all modules the student has already registered for
        $registeredModules = [];
        foreach ($existingResults as $existingResult) {
            if ($existingResult->modules) {
                $registeredModules = array_merge($registeredModules, $existingResult->modules);
            }
        }
        $registeredModules = array_unique($registeredModules);

        // Check if student has already registered for all 3 modules
        if (count($registeredModules) >= 3) {
            return back()->withErrors(['modules' => 'You have already registered for all available modules (Listening, Reading, Writing).']);
        }

        // Check if any requested modules are already registered
        $duplicateModules = array_intersect($validated['modules'], $registeredModules);
        if (!empty($duplicateModules)) {
            $moduleNames = array_map('ucfirst', $duplicateModules);

            return back()->withErrors(['modules' => 'You have already registered for: ' . implode(', ', $moduleNames) . '. Please select different modules.']);
        }

        // Initialize sections based on selected modules
        $sectionData = [];
        foreach ($validated['modules'] as $module) {
            if (in_array($module, ['listening', 'reading', 'writing'])) {
                $sectionData[$module] = [];
            }
        }

        // Create a new result record for partial mock test
        $result = Result::query()->create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'local_exam_id' => $exam->exam_id,
            'username' => $student->name,
            'phone' => $student->phone ?? '',
            'email' => $student->email ?? '',
            'passport' => $student->passport ?? '',
            'exam_type' => 'partial',
            'modules' => $validated['modules'],
            'reading' => $sectionData['reading'] ?? null,
            'writing' => $sectionData['writing'] ?? null,
            'listening' => $sectionData['listening'] ?? null,
            'speaking' => null,
            'data' => [],
        ]);

        // NOTE: Usage counters will be incremented when exam is submitted/completed
        // Store result ID in session for tracking
        session(['result_id' => $result->id]);

        // Redirect to exam page using UUID
        return redirect()
            ->route('exam.slug', $exam->uuid)
            ->with('success', 'Registration successful! You can now start the partial exam.');
    }
}
