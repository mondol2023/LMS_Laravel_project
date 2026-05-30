<?php

use App\Models\Exam;
use App\Models\Result;
use App\Models\User;

test('guests cannot delete results', function () {
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

    $response = $this->delete(route('results.destroy', $result));

    $response->assertRedirect(route('login'));
    $this->assertDatabaseHas('results', ['id' => $result->id]);
});

test('authenticated users can delete results', function () {
    $user = User::factory()->create();
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

    $this->actingAs($user);

    $response = $this->delete(route('results.destroy', $result));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Result deleted successfully!');
    $this->assertDatabaseMissing('results', ['id' => $result->id]);
});

test('deleting result removes it from database completely', function () {
    $user = User::factory()->create();
    $exam = Exam::factory()->create();
    $result = Result::create([
        'exam_id' => $exam->id,
        'username' => 'Jane Smith',
        'email' => 'jane@example.com',
        'phone' => '123456789',
        'passport' => 'ABC123',
        'reading' => ['band_score' => 8.0],
        'writing' => ['band_score' => 7.5],
        'listening' => ['band_score' => 8.5],
        'speaking' => ['band_score' => 7.0],
    ]);

    $resultId = $result->id;

    $this->actingAs($user);

    $response = $this->delete(route('results.destroy', $result));

    $response->assertRedirect();
    expect(Result::find($resultId))->toBeNull();
    $this->assertDatabaseMissing('results', [
        'id' => $resultId,
        'username' => 'Jane Smith',
    ]);
});

test('delete route handles non-existent results gracefully', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->delete(route('results.destroy', 99999));

    $response->assertNotFound();
});
