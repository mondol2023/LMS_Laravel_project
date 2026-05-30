<?php

namespace App\Mail;

use App\Models\Result;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResultPdfMail extends Mailable implements ShouldQueue
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
        $subject = $this->isPartialResult()
            ? 'Your IELTS Partial Test Result PDF'
            : 'Your IELTS Exam Result PDF';

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.result-pdf',
            with: [
                'result' => $this->result,
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
        // If PDF exists in storage, attach it
        if ($this->result->pdf_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->result->pdf_path)) {
            $fullPath = storage_path('app/public/'.$this->result->pdf_path);

            return [
                Attachment::fromPath($fullPath)
                    ->as('ielts-exam-result.pdf')
                    ->withMime('application/pdf'),
            ];
        }

        // If no saved PDF, return empty (PDF should be generated before sending)
        return [];
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
}
