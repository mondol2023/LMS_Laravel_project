<?php

namespace App\Mail;

use App\Models\Result;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResultMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Result $result
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your IELTS Exam Results',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.result',
            with: [
                'overallScore' => $this->getOverallScore(),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $isPartial = $this->isPartialResult();
        $template = $isPartial ? 'pdfs.result-partial' : 'pdfs.result';

        $pdf = Pdf::loadView($template, [
            'result' => $this->result,
            'listeningAnswers' => $this->getListeningAnswers(),
            'readingAnswers' => $this->getReadingAnswers(),
            'listeningStats' => $this->getListeningStats(),
            'readingStats' => $this->getReadingStats(),
            'overallScore' => $this->getOverallScore(),
        ]);

        return [
            Attachment::fromData(fn () => $pdf->output(), 'exam-result.pdf')
                ->withMime('application/pdf'),
        ];
    }

    protected function isPartialResult(): bool
    {
        if ($this->result->exam_type === 'partial') {
            return true;
        }

        if ($this->result->exam_type === 'full') {
            return false;
        }

        if ($this->result->modules && is_array($this->result->modules) && count($this->result->modules) > 0) {
            $allModules = ['listening', 'reading', 'writing', 'speaking'];
            $hasAllModules = count($this->result->modules) === 4 && empty(array_diff($allModules, $this->result->modules));

            return ! $hasAllModules;
        }

        return false;
    }

    /**
     * Get listening answers with correct/incorrect marking.
     */
    protected function getListeningAnswers(): array
    {
        if (! $this->result->listening || ! $this->result->local_exam_id) {
            return [];
        }

        $answers = [];
        $userAnswers = $this->result->listening['user_answers'] ?? [];
        $correctAnswers = $this->getCorrectAnswers()['listening'] ?? [];

        foreach ($correctAnswers as $key => $correctAnswer) {
            $userAnswer = $userAnswers["q{$key}"] ?? null;
            $isCorrect = $this->normalizeAnswer($userAnswer) === $this->normalizeAnswer($correctAnswer);

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
    protected function getReadingAnswers(): array
    {
        if (! $this->result->reading || ! $this->result->local_exam_id) {
            return [];
        }

        $answers = [];
        $userAnswers = $this->result->reading['user_answers'] ?? [];
        $correctAnswers = $this->getCorrectAnswers()['reading'] ?? [];

        foreach ($correctAnswers as $key => $correctAnswer) {
            $userAnswer = $userAnswers["q{$key}"] ?? null;
            $isCorrect = $this->normalizeAnswer($userAnswer) === $this->normalizeAnswer($correctAnswer);

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
    protected function getCorrectAnswers(): array
    {
        $examResults = config('examResults');

        return $examResults[$this->result->local_exam_id] ?? [];
    }

    /**
     * Get listening statistics.
     */
    protected function getListeningStats(): array
    {
        $answers = $this->getListeningAnswers();
        $total = count($answers);
        $correct = collect($answers)->where('isCorrect', true)->count();
        $wrong = $total - $correct;

        return compact('correct', 'wrong', 'total');
    }

    /**
     * Get reading statistics.
     */
    protected function getReadingStats(): array
    {
        $answers = $this->getReadingAnswers();
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
        if (! $answer) {
            return '';
        }

        return strtolower(trim($answer));
    }

    /**
     * Calculate overall IELTS band score.
     */
    protected function getOverallScore(): ?float
    {
        $scores = [];

        // Collect all four section scores
        if (isset($this->result->listening['band_score']) && $this->result->listening['band_score'] !== null) {
            $scores[] = (float) $this->result->listening['band_score'];
        }

        if (isset($this->result->reading['band_score']) && $this->result->reading['band_score'] !== null) {
            $scores[] = (float) $this->result->reading['band_score'];
        }

        if (isset($this->result->writing['band_score']) && $this->result->writing['band_score'] !== null) {
            $scores[] = (float) $this->result->writing['band_score'];
        }

        if (isset($this->result->speaking['band_score']) && $this->result->speaking['band_score'] !== null) {
            $scores[] = (float) $this->result->speaking['band_score'];
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
