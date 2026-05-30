<?php

namespace App\Jobs;

use App\Mail\ResultMail;
use App\Models\Result;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendResultEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Result $result
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        info('=== SendResultEmail Job Started ===');
        if (empty($this->result->email)) {
            return;
        }

        try {
            info('Attempting to send email to: '.$this->result->email);
            Mail::to($this->result->email)->send(
                new ResultMail($this->result)
            );

            info('✓ Email sent successfully to: '.$this->result->email);
        } catch (\Exception $e) {
            info('✗ ERROR sending email: '.$e->getMessage());
            info('Stack trace: '.$e->getTraceAsString());
            throw $e;
        }
    }
}
