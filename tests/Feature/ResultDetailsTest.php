<?php

use App\Models\Exam;
use App\Models\Result;
use App\Models\User;

test('guests cannot view result details', function () {
    $exam = Exam::factory()->create();
    $result = Result::create([
        'exam_id' => $exam->id,
        'username' => 'John Doe',
        'email' => 'john@example.com',
        'reading' => ['band_score' => 7.0],
        'writing' => ['band_score' => 6.5],
        'listening' => ['band_score' => 7.5],
        'speaking' => ['band_score' => 6.0],
    ]);

    $response = $this->get(route('results.details', $result));

    $response->assertRedirect(route('login'));
});

test('authenticated users can view result details page', function () {
    $user = User::factory()->create();
    $exam = Exam::factory()->create();
    $result = Result::create([
        'exam_id' => $exam->id,
        'username' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '123456789',
        'passport' => 'ABC123',
        'reading' => [
            'band_score' => 7.0,
            'correct_answers' => 28,
            'total_questions' => 40,
            'user_answers' => ['q1' => 'answer1', 'q2' => 'answer2'],
        ],
        'writing' => [
            'band_score' => 6.5,
            [
                'q' => 'Write about your hometown',
                'ans' => 'My hometown is a beautiful place...',
            ],
            [
                'q' => 'Discuss the advantages of technology',
                'ans' => 'Technology has revolutionized our lives...',
            ],
        ],
        'listening' => [
            'band_score' => 7.5,
            'correct_answers' => 32,
            'total_questions' => 40,
            'user_answers' => ['q1' => 'answer1', 'q2' => 'answer2'],
        ],
        'speaking' => [
            'band_score' => 6.0,
            'status' => 'completed',
            'teacher_feedback' => 'Good pronunciation',
        ],
    ]);

    $this->actingAs($user);

    $response = $this->get(route('results.details', $result));

    $response->assertSuccessful();
    $response->assertInertia(function ($page) use ($result) {
        $page->component('Results/Details')
            ->has('result')
            ->where('result.id', $result->id)
            ->where('result.username', 'John Doe')
            ->where('result.email', 'john@example.com')
            ->has('rawJson');
    });
});

test('result details page contains valid json', function () {
    $user = User::factory()->create();
    $exam = Exam::factory()->create();
    $result = Result::create([
        'exam_id' => $exam->id,
        'username' => 'Jane Smith',
        'email' => 'jane@example.com',
        'reading' => ['band_score' => 8.0],
        'writing' => ['band_score' => 7.5],
        'listening' => ['band_score' => 8.5],
        'speaking' => ['band_score' => 7.0],
    ]);

    $this->actingAs($user);

    $response = $this->get(route('results.details', $result));

    $response->assertInertia(function ($page) {
        $page->component('Results/Details')
            ->has('rawJson');

        // Check that rawJson is valid JSON
        $rawJson = $page->toArray()['props']['rawJson'];
        expect(json_decode($rawJson))->not->toBeNull();
    });
});

test('result details json contains all expected fields', function () {
    $user = User::factory()->create();
    $exam = Exam::factory()->create();
    $result = Result::create([
        'exam_id' => $exam->id,
        'username' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '987654321',
        'passport' => 'XYZ789',
        'reading' => ['band_score' => 6.5],
        'writing' => ['band_score' => 6.0],
        'listening' => ['band_score' => 7.0],
        'speaking' => ['band_score' => 6.5],
    ]);

    $this->actingAs($user);

    $response = $this->get(route('results.details', $result));

    $response->assertInertia(function ($page) {
        $page->component('Results/Details')
            ->has('rawJson');

        // Check that rawJson contains all expected fields
        $rawJson = $page->toArray()['props']['rawJson'];
        $decoded = json_decode($rawJson, true);

        expect($decoded)->toHaveKeys([
            'id',
            'exam_id',
            'username',
            'email',
            'phone',
            'passport',
            'reading',
            'writing',
            'listening',
            'speaking',
            'created_at',
            'updated_at',
        ]);
    });
});
