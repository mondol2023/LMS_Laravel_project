<?php

use App\Jobs\SendResultEmail;
use App\Models\Exam;
use App\Models\Result;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

uses()->group('mail');

test('authenticated user can send result email', function () {
    Queue::fake();

    $user = User::factory()->create();
    $exam = Exam::factory()->create();

    $result = Result::create([
        'exam_id' => $exam->id,
        'local_exam_id' => 'IELTS001',
        'username' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '1234567890',
        'passport' => 'ABC123',
        'listening' => [
            'user_answers' => [
                'q1' => 'hairdresser',
                'q2' => 'tablets',
            ],
        ],
        'reading' => [
            'user_answers' => [
                'q1' => 'hot tar',
                'q2' => '5 cm',
            ],
        ],
        'writing' => [
            '0' => [
                'q' => 'Test question',
                'ans' => 'Test answer',
            ],
            'band_score' => 7.5,
            'teacher_feedback' => 'Good work!',
        ],
        'speaking' => [
            'part_1' => [
                'response' => 'Test response',
                'feedback' => 'Well done',
            ],
        ],
    ]);

    $this->actingAs($user)
        ->post("/results/{$result->id}/send-email")
        ->assertRedirect();

    Queue::assertPushed(SendResultEmail::class, function ($job) use ($result) {
        return $job->result->id === $result->id;
    });
});

test('guest cannot send result email', function () {
    $exam = Exam::factory()->create();

    $result = Result::create([
        'exam_id' => $exam->id,
        'local_exam_id' => 'IELTS001',
        'username' => 'John Doe',
        'email' => 'john@example.com',
        'listening' => ['user_answers' => []],
        'reading' => ['user_answers' => []],
    ]);

    $this->post("/results/{$result->id}/send-email")
        ->assertRedirect('/login');
});

test('send result email job dispatches mail', function () {
    $exam = Exam::factory()->create();

    $result = Result::create([
        'exam_id' => $exam->id,
        'local_exam_id' => 'IELTS001',
        'username' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '1234567890',
        'passport' => 'ABC123',
        'listening' => [
            'user_answers' => [
                'q1' => 'hairdresser',
                'q2' => 'tablets',
            ],
        ],
        'reading' => [
            'user_answers' => [
                'q1' => 'hot tar',
                'q2' => '5 cm',
            ],
        ],
    ]);

    \Illuminate\Support\Facades\Mail::fake();

    $job = new SendResultEmail($result);
    $job->handle();

    \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\ResultMail::class, function ($mail) use ($result) {
        return $mail->result->id === $result->id
            && $mail->hasTo($result->email);
    });
});
