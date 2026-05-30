<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    /**
     * Show the Exam ID entry page.
     */
    public function examId(): Response
    {
        return Inertia::render('auth/ExamId');
    }

    /**
     * Show the combined exam registration form.
     */
    public function examRegistration(): Response
    {
        return Inertia::render('auth/ExamRegistration');
    }

    /**
     * Handle the combined exam registration.
     */
    public function storeExamRegistration(Request $request): RedirectResponse
    {
        $request->validate([
            'exam_id' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'phone' => 'required|string|max:20',
            'passport' => 'required|string|max:20',
        ]);

        // Validate exam ID exists or is in valid format
        $examId = strtolower(trim($request->exam_id));

        // Check if exam exists in database
        $examExists = Exam::query()->where('exam_id', $request->exam_id)->exists();

        // Define allowed exam ID patterns for static exams
        $allowedExamIds = [
            'acad001', 'gen001', 'prep001', 'mock001', 'practice001',
            'ielts001', 'ielts002', 'ielts003', 'sample-test', 'demo',
        ];

        // Check if exam ID is numeric (auto-generated) or in allowed list
        $isValidExam = $examExists ||
                      is_numeric($request->exam_id) ||
                      in_array($examId, $allowedExamIds) ||
                      preg_match('/^[a-z0-9\-]{3,20}$/', $examId); // Basic pattern validation

        if (! $isValidExam) {
            throw ValidationException::withMessages([
                'exam_id' => ['The exam ID provided is not valid or does not exist.'],
            ]);
        }

        // Create user with exam_id
        $user = User::query()->create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make('defaultpassword123'), // Default password
            'phone' => $request->phone,
            'passport' => $request->passport,
            'exam_id' => $request->exam_id,
        ]);

        event(new Registered($user));
        Auth::login($user);

        // Redirect to the specific exam based on exam_id
        return to_route('exam.slug', ['slug' => 123]);
    }
}
