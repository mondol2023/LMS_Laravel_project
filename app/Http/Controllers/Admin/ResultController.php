<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Result;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResultController extends Controller
{
    public function index()
    {
        $results = Result::latest()->paginate(15);

        return Inertia::render('Admin/Results/Index', [
            'results' => $results,
        ]);
    }

    public function show(Result $result)
    {
        return Inertia::render('Admin/Results/Show', [
            'result' => $result,
        ]);
    }

    public function updateSpeaking(Request $request, Result $result)
    {
        $request->validate([
            'speaking' => 'required|array',
            'speaking.band_score' => 'nullable|numeric|min:0|max:9',
            'speaking.status' => 'nullable|string',
        ]);

        // Store old values for logging
        $oldSpeaking = $result->speaking;

        // Update the speaking section
        $result->update([
            'speaking' => $request->speaking,
        ]);

        // Clear PDF cache when result is updated
        if ($result->pdf_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($result->pdf_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($result->pdf_path);
            $result->update(['pdf_path' => null]);
        }

        // Log the modification
        \App\Models\ResultModificationLog::create([
            'result_id' => $result->id,
            'user_id' => auth()->id(),
            'modification_type' => 'speaking_update',
            'old_values' => [
                'speaking' => $oldSpeaking,
            ],
            'new_values' => [
                'speaking' => $request->speaking,
            ],
            'description' => 'Admin updated speaking scores for '.$result->username.' (Exam: '.$result->exam->name.')',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', 'Speaking result updated successfully.');
    }

    public function updateWriting(Request $request, Result $result)
    {
        $request->validate([
            'writing' => 'required',
            'task1_band' => 'nullable|numeric|min:0|max:9',
            'task1_feedback' => 'nullable|string',
            'task2_band' => 'nullable|numeric|min:0|max:9',
            'task2_feedback' => 'nullable|string',
            'band_score' => 'nullable|numeric|min:0|max:9',
            'teacher_feedback' => 'nullable|string',
        ]);

        // Store old values for logging
        $oldWriting = $result->writing;

        // Get the current writing data
        $writingData = $result->writing;

        // Convert to array if it's an object (for consistency)
        if (is_object($writingData)) {
            $writingData = json_decode(json_encode($writingData), true);
        }

        // Add all scoring and feedback fields to the writing structure
        if (is_array($writingData)) {
            // Add task1 score and feedback
            $writingData['task1_band'] = $request->task1_band;
            $writingData['task1_feedback'] = $request->task1_feedback;

            // Add task2 score and feedback
            $writingData['task2_band'] = $request->task2_band;
            $writingData['task2_feedback'] = $request->task2_feedback;

            // Add overall band score and general feedback
            $writingData['band_score'] = $request->band_score;
            $writingData['teacher_feedback'] = $request->teacher_feedback;
        }

        // Update the result
        $result->update([
            'writing' => $writingData,
        ]);

        // Clear PDF cache when result is updated
        if ($result->pdf_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($result->pdf_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($result->pdf_path);
            $result->update(['pdf_path' => null]);
        }

        // Log the modification
        \App\Models\ResultModificationLog::create([
            'result_id' => $result->id,
            'user_id' => auth()->id(),
            'modification_type' => 'writing_update',
            'old_values' => [
                'writing' => $oldWriting,
            ],
            'new_values' => [
                'writing' => $writingData,
            ],
            'description' => 'Admin updated writing scores and feedback for '.$result->username.' (Exam: '.$result->exam->name.')',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', 'Writing result updated successfully.');
    }
}
