<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(): Response
    {
        $search = request('search');

        $students = User::query()
            ->where('role', 'student')
            ->with(['activeSubscription.package'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('roll_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Students/Index', [
            'students' => $students,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Students/Create');
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $createAnother = $validated['create_another'] ?? false;
        unset($validated['create_another']);

        // Set role to student
        $validated['role'] = 'student';

        $student = User::query()->create($validated);

        if ($createAnother) {
            return redirect()->route('students.create')
                ->with('success', "Student '{$student->name}' created successfully.");
        }

        return redirect()->route('students.index')
            ->with('success', "Student '{$student->name}' created successfully.");
    }

    public function show(User $student): Response
    {
        // Ensure we only show students
        abort_if($student->role !== 'student', 404);

        return Inertia::render('Students/Show', [
            'student' => $student,
        ]);
    }

    public function edit(User $student): Response
    {
        // Ensure we only edit students
        abort_if($student->role !== 'student', 404);

        return Inertia::render('Students/Edit', [
            'student' => $student,
        ]);
    }

    public function update(UpdateStudentRequest $request, User $student): RedirectResponse
    {
        // Ensure we only update students
        abort_if($student->role !== 'student', 404);

        $validated = $request->validated();

        // Remove password if not provided (don't update it)
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $student->update($validated);

        return redirect()->route('students.index')
            ->with('success', "Student '{$student->name}' updated successfully.");
    }

    public function destroy(User $student): RedirectResponse
    {
        // Ensure we only delete students
        abort_if($student->role !== 'student', 404);

        $studentName = $student->name;
        $student->delete();

        return redirect()->route('students.index')
            ->with('success', "Student '{$studentName}' deleted successfully.");
    }
}
