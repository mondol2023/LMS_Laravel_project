<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExamRequest;
use App\Http\Requests\UpdateExamRequest;
use App\Models\Exam;
use Inertia\Inertia;

class ExamController extends Controller
{
    public function index()
    {
        $exams = Exam::query()->orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('Admin/Exams/Index', [
            'exams' => $exams,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Exams/Create');
    }

    public function store(StoreExamRequest $request)
    {
        $validated = $request->validated();

        $exam = Exam::query()->create($validated);

        return redirect()->route('admin.exams.index')
            ->with('success', "Exam '{$exam->name}' created successfully with ID: {$exam->exam_id}");
    }

    public function show(Exam $exam)
    {
        return Inertia::render('Admin/Exams/Show', [
            'exam' => $exam,
        ]);
    }

    public function edit(Exam $exam)
    {
        return Inertia::render('Admin/Exams/Edit', [
            'exam' => $exam,
        ]);
    }

    public function update(UpdateExamRequest $request, Exam $exam)
    {
        $validated = $request->validated();

        $exam->update($validated);

        return redirect()->route('admin.exams.index')
            ->with('success', "Exam '{$exam->name}' updated successfully.");
    }

    public function destroy(Exam $exam)
    {
        $examName = $exam->name;
        $exam->delete();

        return redirect()->route('admin.exams.index')
            ->with('success', "Exam '{$examName}' deleted successfully.");
    }
}
