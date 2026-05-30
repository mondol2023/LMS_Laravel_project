<?php

namespace App\Http\Controllers;

use App\Mail\ResultPdfMail;
use App\Models\Exam;
use App\Models\Result;
use App\Models\ResultModificationLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ResultController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): \Inertia\Response
    {
        $query = Result::query()
            ->select([
                'id',
                'exam_id',
                'student_id',
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
                'created_at',
                'updated_at',
            ])
            ->with('exam');

        // Search by username or passport (roll)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('passport', 'like', "%{$search}%");
            });
        }

        // Filter by exam
        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $results = $query->latest('created_at')->paginate(15)->withQueryString();

        $exams = Exam::query()->orderBy('name')->get(['id', 'name', 'exam_id']);

        return Inertia::render('Results/Index', [
            'results' => $results,
            'exams' => $exams,
            'filters' => $request->only(['search', 'exam_id', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Result $result): \Inertia\Response
    {
        return Inertia::render('Results/Show', [
            'result' => $result,
        ]);
    }

    /**
     * Display the raw JSON of a result.
     */
    public function showJson(Result $result): \Inertia\Response
    {
        return Inertia::render('Results/Details', [
            'result' => $result,
            'rawJson' => json_encode($result->toArray(), JSON_PRETTY_PRINT),
        ]);
    }

    /**
     * Update speaking score and feedback for a result.
     */
    public function updateSpeaking(Request $request, Result $result): RedirectResponse
    {
        $request->validate([
            'speaking' => 'required|array',
            'speaking.band_score' => 'nullable|numeric|min:0|max:9',
            'speaking.teacher_feedback' => 'nullable|string',
        ]);

        $oldSpeaking = $result->speaking;

        $result->update([
            'speaking' => $request->speaking,
        ]);

        if ($result->pdf_path && Storage::disk('public')->exists($result->pdf_path)) {
            Storage::disk('public')->delete($result->pdf_path);
            $result->update(['pdf_path' => null]);
        }

        ResultModificationLog::create([
            'result_id' => $result->id,
            'user_id' => auth()->id(),
            'modification_type' => 'speaking_update',
            'old_values' => ['speaking' => $oldSpeaking],
            'new_values' => ['speaking' => $request->speaking],
            'description' => 'Updated speaking scores and feedback for ' . $result->username,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', 'Speaking result updated successfully.');
    }

    /**
     * Update writing scores and feedback for a result.
     */
    public function updateWriting(Request $request, Result $result): RedirectResponse
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

        $oldWriting = $result->writing;
        $writingData = $result->writing;

        if (is_object($writingData)) {
            $writingData = json_decode(json_encode($writingData), true);
        }

        if (is_array($writingData)) {
            $writingData['task1_ta'] = $request->task1_ta;
            $writingData['task1_cc'] = $request->task1_cc;
            $writingData['task1_lr'] = $request->task1_lr;
            $writingData['task1_gra'] = $request->task1_gra;
            $writingData['task1_band'] = $request->task1_band;
            $writingData['task1_feedback'] = $request->task1_feedback;
            $writingData['task2_ta'] = $request->task2_ta;
            $writingData['task2_cc'] = $request->task2_cc;
            $writingData['task2_lr'] = $request->task2_lr;
            $writingData['task2_gra'] = $request->task2_gra;
            $writingData['task2_band'] = $request->task2_band;
            $writingData['task2_feedback'] = $request->task2_feedback;
            $writingData['band_score'] = $request->band_score;
            $writingData['teacher_feedback'] = $request->teacher_feedback;
        }

        $result->update([
            'writing' => $writingData,
        ]);

        if ($result->pdf_path && Storage::disk('public')->exists($result->pdf_path)) {
            Storage::disk('public')->delete($result->pdf_path);
            $result->update(['pdf_path' => null]);
        }

        ResultModificationLog::create([
            'result_id' => $result->id,
            'user_id' => auth()->id(),
            'modification_type' => 'writing_update',
            'old_values' => ['writing' => $oldWriting],
            'new_values' => ['writing' => $writingData],
            'description' => 'Updated writing scores and feedback for ' . $result->username,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', 'Writing result updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Result $result): RedirectResponse
    {
        $result->delete();

        return back()->with('success', 'Result deleted successfully!');
    }

    /**
     * Send the result email to the user.
     */
    public function sendEmail(Result $result): RedirectResponse
    {
        \App\Jobs\SendResultEmail::dispatch($result);

        return back()->with('success', 'Result email has been queued for sending!');
    }

    /**
     * View the PDF for the result.
     */
    public function viewPdf(Result $result): Response|ResponseFactory
    {
        // Check if result was updated after PDF was generated (requires regeneration)
        $needsRegeneration = false;
        if ($result->pdf_path && Storage::disk('public')->exists($result->pdf_path)) {
            $pdfLastModified = Storage::disk('public')->lastModified($result->pdf_path);
            $resultLastModified = $result->updated_at->timestamp;

            if ($resultLastModified > $pdfLastModified) {
                $needsRegeneration = true;
            }
        }

        // Check if PDF already exists in storage and doesn't need regeneration
        if (!$needsRegeneration && $result->pdf_path && Storage::disk('public')->exists($result->pdf_path)) {
            $pdfContent = Storage::disk('public')->get($result->pdf_path);

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="exam-result.pdf"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        }

        // Determine which template to use based on exam type
        $isPartial = $this->isPartialResult($result);
        $template = $isPartial ? 'pdfs.result-partial' : 'pdfs.result';

        // Generate the PDF with proper options
        $pdf = Pdf::loadView($template, [
            'result' => $result,
            'listeningAnswers' => $this->getListeningAnswers($result),
            'readingAnswers' => $this->getReadingAnswers($result),
            'listeningStats' => $this->getListeningStats($result),
            'readingStats' => $this->getReadingStats($result),
            'overallScore' => $this->getOverallScore($result),
        ]);

        // Set PDF options for better rendering
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('defaultFont', 'DejaVu Sans');

        // Get PDF output
        $pdfOutput = $pdf->output();

        // Save the PDF to storage
        $pdfPath = "results/result-{$result->id}.pdf";
        Storage::disk('public')->put($pdfPath, $pdfOutput);

        // Update the result with the PDF path
        if (!$result->pdf_path) {
            $result->update(['pdf_path' => $pdfPath]);
        }

        // Return PDF with proper headers for iframe display
        return response($pdfOutput)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="exam-result.pdf"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Send PDF to user via email.
     */
    public function sendPdf(Result $result): RedirectResponse
    {
        // Determine which template to use based on exam type
        $isPartial = $this->isPartialResult($result);
        $template = $isPartial ? 'pdfs.result-partial' : 'pdfs.result';

        // Always regenerate PDF before sending to ensure it's up-to-date
        $pdf = Pdf::loadView($template, [
            'result' => $result,
            'listeningAnswers' => $this->getListeningAnswers($result),
            'readingAnswers' => $this->getReadingAnswers($result),
            'listeningStats' => $this->getListeningStats($result),
            'readingStats' => $this->getReadingStats($result),
            'overallScore' => $this->getOverallScore($result),
        ]);

        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('defaultFont', 'DejaVu Sans');

        $pdfOutput = $pdf->output();

        // Save PDF temporarily for email attachment
        $pdfPath = "results/result-{$result->id}.pdf";
        Storage::disk('public')->put($pdfPath, $pdfOutput);
        $result->update(['pdf_path' => $pdfPath]);

        // Send email
        Mail::to($result->email)
            ->send(new ResultPdfMail($result));

        // Delete PDF after sending and clear path
        if (Storage::disk('public')->exists($pdfPath)) {
            Storage::disk('public')->delete($pdfPath);
            $result->update(['pdf_path' => null]);
        }

        return back()->with('success', 'PDF has been sent to the user and removed from storage!');
    }

    /**
     * Regenerate the PDF for the result.
     */
    public function regeneratePdf(Result $result): RedirectResponse
    {
        // Determine which template to use based on exam type
        $isPartial = $this->isPartialResult($result);
        $template = $isPartial ? 'pdfs.result-partial' : 'pdfs.result';

        // Generate fresh PDF with proper options
        $pdf = Pdf::loadView($template, [
            'result' => $result,
            'listeningAnswers' => $this->getListeningAnswers($result),
            'readingAnswers' => $this->getReadingAnswers($result),
            'listeningStats' => $this->getListeningStats($result),
            'readingStats' => $this->getReadingStats($result),
            'overallScore' => $this->getOverallScore($result),
        ]);

        // Set PDF options for better rendering
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('defaultFont', 'DejaVu Sans');

        // Get PDF output
        $pdfOutput = $pdf->output();

        // Update the PDF in storage (overwrite existing)
        $pdfPath = "results/result-{$result->id}.pdf";
        Storage::disk('public')->put($pdfPath, $pdfOutput);

        // Update the result with the PDF path
        $result->update(['pdf_path' => $pdfPath]);

        return back()->with('success', 'PDF has been regenerated successfully!');
    }

    /**
     * Upload an annotated PDF to replace the existing one.
     */
    public function uploadPdf(Request $request, Result $result): RedirectResponse
    {
        // Validate the uploaded file
        $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:10240', // Max 10MB
        ]);
        $oldPdfPath = $result->pdf_path;
        $uploadedFile = $request->file('pdf');
        $pdfPath = "results/result-{$result->id}.pdf";
        Storage::disk('public')->put($pdfPath, file_get_contents($uploadedFile->getRealPath()));
        $result->update(['pdf_path' => $pdfPath]);

        // Log the modification
        ResultModificationLog::query()->create([
            'result_id' => $result->id,
            'user_id' => auth()->id(),
            'modification_type' => 'pdf_upload',
            'old_values' => [
                'pdf_path' => $oldPdfPath,
                'file_name' => $oldPdfPath ? basename($oldPdfPath) : null,
            ],
            'new_values' => [
                'pdf_path' => $pdfPath,
                'file_name' => basename($pdfPath),
                'original_file_name' => $uploadedFile->getClientOriginalName(),
                'file_size' => $uploadedFile->getSize(),
            ],
            'description' => 'Admin uploaded annotated PDF for ' . $result->username . ' (Exam: ' . $result->exam->name . ')',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'Annotated PDF has been uploaded successfully!');
    }

    /**
     * Determine if result is a partial test (module-based).
     */
    protected function isPartialResult(Result $result): bool
    {
        if ($result->exam_type === 'partial') {
            return true;
        }

        if ($result->exam_type === 'full') {
            return false;
        }

        if ($result->modules && is_array($result->modules) && count($result->modules) > 0) {
            $allModules = ['listening', 'reading', 'writing', 'speaking'];
            $hasAllModules = count($result->modules) === 4 && empty(array_diff($allModules, $result->modules));

            return !$hasAllModules;
        }

        return false;
    }

    /**
     * Get listening answers with correct/incorrect marking.
     */
    protected function getListeningAnswers(Result $result): array
    {
        if (!$result->listening || !$result->local_exam_id) {
            return [];
        }

        $answers = [];
        $userAnswers = $result->listening['user_answers'] ?? [];
        $correctAnswers = $this->getCorrectAnswers($result)['listening'] ?? [];

        foreach ($correctAnswers as $key => $correctAnswer) {
            $userAnswer = $userAnswers["q{$key}"] ?? null;
            $isCorrect = $this->answersMatch($userAnswer, $correctAnswer);

            $answers[] = [
                'question' => $key,
                'userAnswer' => $userAnswer ?: '-',
                'correctAnswer' => $correctAnswer,
                'isCorrect' => $isCorrect,
            ];
        }

        return $answers;
    }

    /**
     * Get reading answers with correct/incorrect marking.
     */
    protected function getReadingAnswers(Result $result): array
    {
        if (!$result->reading || !$result->local_exam_id) {
            return [];
        }

        $answers = [];
        $userAnswers = $result->reading['user_answers'] ?? [];
        $correctAnswers = $this->getCorrectAnswers($result)['reading'] ?? [];

        foreach ($correctAnswers as $key => $correctAnswer) {
            $userAnswer = $userAnswers["q{$key}"] ?? null;
            $isCorrect = $this->answersMatch($userAnswer, $correctAnswer);

            $answers[] = [
                'question' => $key,
                'userAnswer' => $userAnswer ?: '-',
                'correctAnswer' => $correctAnswer,
                'isCorrect' => $isCorrect,
            ];
        }

        return $answers;
    }

    /**
     * Get correct answers from exam results.
     */
    protected function getCorrectAnswers(Result $result): array
    {
        $examResults = config('examResults');

        return $examResults[$result->local_exam_id] ?? [];
    }

    /**
     * Get listening statistics.
     */
    protected function getListeningStats(Result $result): array
    {
        $answers = $this->getListeningAnswers($result);
        $total = count($answers);
        $correct = collect($answers)->where('isCorrect', true)->count();
        $wrong = $total - $correct;

        return compact('correct', 'wrong', 'total');
    }

    /**
     * Get reading statistics.
     */
    protected function getReadingStats(Result $result): array
    {
        $answers = $this->getReadingAnswers($result);
        $total = count($answers);
        $correct = collect($answers)->where('isCorrect', true)->count();
        $wrong = $total - $correct;

        return compact('correct', 'wrong', 'total');
    }

    /**
     * Normalize answer for comparison.
     */
    protected function normalizeAnswer(?string $answer): string
    {
        if (!$answer) {
            return '';
        }

        $normalized = strtolower(trim($answer));

        // Remove ordinal suffixes (st, nd, rd, th) from numbers
        $normalized = preg_replace('/\b(\d+)(st|nd|rd|th)\b/', '$1', $normalized);

        // Remove punctuation and extra spaces
        $normalized = preg_replace('/[.,!?;:\'"()-]/', ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim($normalized);
    }

    /**
     * Check if two answers match (handles alternative answers separated by /).
     */
    protected function answersMatch(?string $userAnswer, ?string $correctAnswer): bool
    {
        if (!$userAnswer || !$correctAnswer) {
            return false;
        }

        $normalizedUser = $this->normalizeAnswer($userAnswer);

        // Handle multiple correct answers separated by "/"
        $possibleAnswers = array_map(function ($a) {
            return $this->normalizeAnswer(trim($a));
        }, explode('/', $correctAnswer));

        // Check if user answer matches any of the possible correct answers
        foreach ($possibleAnswers as $possibleAnswer) {
            // Direct match
            if ($possibleAnswer === $normalizedUser) {
                return true;
            }

            // Check for partial matches in compound answers
            if (strpos($possibleAnswer, ' ') !== false && strpos($normalizedUser, ' ') !== false) {
                $possibleWords = explode(' ', $possibleAnswer);
                $userWords = explode(' ', $normalizedUser);

                // Check if all words from one are in the other
                $allPossibleInUser = empty(array_diff($possibleWords, $userWords));
                $allUserInPossible = empty(array_diff($userWords, $possibleWords));

                if ($allPossibleInUser || $allUserInPossible) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Calculate overall IELTS band score.
     */
    protected function getOverallScore(Result $result): ?float
    {
        $scores = [];

        // Collect all four section scores
        if (isset($result->listening['band_score']) && $result->listening['band_score'] !== null) {
            $scores[] = (float)$result->listening['band_score'];
        }

        if (isset($result->reading['band_score']) && $result->reading['band_score'] !== null) {
            $scores[] = (float)$result->reading['band_score'];
        }

        if (isset($result->writing['band_score']) && $result->writing['band_score'] !== null) {
            $scores[] = (float)$result->writing['band_score'];
        }

        if (isset($result->speaking['band_score']) && $result->speaking['band_score'] !== null) {
            $scores[] = (float)$result->speaking['band_score'];
        }

        // Need all 4 sections to calculate overall score
        if (count($scores) !== 4) {
            return null;
        }

        // Calculate average
        $average = array_sum($scores) / 4;

        // Apply IELTS rounding rules
        return $this->roundToIELTS($average);
    }

    /**
     * Apply IELTS rounding rules.
     * .00 - .24 → round down to .0
     * .25 - .74 → round to .5
     * .75 - .99 → round up to next whole number
     */
    protected function roundToIELTS(float $score): float
    {
        $wholePart = floor($score);
        $decimalPart = $score - $wholePart;

        if ($decimalPart < 0.25) {
            // 6.24 → 6.0
            return $wholePart;
        } elseif ($decimalPart >= 0.25 && $decimalPart < 0.75) {
            // 6.25 → 6.5, 6.74 → 6.5
            return $wholePart + 0.5;
        } else {
            // 6.75 → 7.0
            return $wholePart + 1.0;
        }
    }
}
