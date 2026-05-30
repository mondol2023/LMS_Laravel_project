<?php

use App\Models\Highlight;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->phone = '1234567890';
    $this->examId = 'IELTS001';
    $this->section = 'writing';
    $this->part = 'part1';
});

test('can create a highlight', function () {
    $highlightData = [
        'phone' => $this->phone,
        'exam_id' => $this->examId,
        'section' => $this->section,
        'part' => $this->part,
        'text' => 'This is a highlighted text',
        'color' => 'yellow',
        'start_offset' => 0,
        'end_offset' => 25,
    ];

    $response = $this->postJson('/api/highlights', $highlightData);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Highlight saved successfully',
        ]);

    $this->assertDatabaseHas('highlights', [
        'phone' => $this->phone,
        'exam_id' => $this->examId,
        'section' => $this->section,
        'part' => $this->part,
        'text' => 'This is a highlighted text',
        'color' => 'yellow',
    ]);
});

test('can retrieve highlights for a user', function () {
    Highlight::factory()->count(3)->create([
        'phone' => $this->phone,
        'exam_id' => $this->examId,
        'section' => $this->section,
        'part' => $this->part,
    ]);

    Highlight::factory()->create([
        'phone' => 'different_phone',
        'exam_id' => $this->examId,
        'section' => $this->section,
        'part' => $this->part,
    ]);

    $response = $this->getJson("/api/highlights?phone={$this->phone}&exam_id={$this->examId}&section={$this->section}&part={$this->part}");

    $response->assertSuccessful()
        ->assertJson(['success' => true])
        ->assertJsonCount(3, 'data');
});

test('can delete a highlight', function () {
    $highlight = Highlight::factory()->create([
        'phone' => $this->phone,
        'exam_id' => $this->examId,
        'section' => $this->section,
        'part' => $this->part,
    ]);

    $response = $this->deleteJson("/api/highlights/{$highlight->id}", [
        'phone' => $this->phone,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Highlight deleted successfully',
        ]);

    $this->assertDatabaseMissing('highlights', [
        'id' => $highlight->id,
    ]);
});

test('cannot delete another users highlight', function () {
    $highlight = Highlight::factory()->create([
        'phone' => 'different_phone',
        'exam_id' => $this->examId,
        'section' => $this->section,
        'part' => $this->part,
    ]);

    $response = $this->deleteJson("/api/highlights/{$highlight->id}", [
        'phone' => $this->phone,
    ]);

    $response->assertNotFound()
        ->assertJson([
            'success' => false,
            'message' => 'Highlight not found',
        ]);

    $this->assertDatabaseHas('highlights', [
        'id' => $highlight->id,
    ]);
});

test('can clear all highlights for a user', function () {
    Highlight::factory()->count(3)->create([
        'phone' => $this->phone,
        'exam_id' => $this->examId,
        'section' => $this->section,
        'part' => $this->part,
    ]);

    $otherHighlight = Highlight::factory()->create([
        'phone' => 'different_phone',
        'exam_id' => $this->examId,
        'section' => $this->section,
        'part' => $this->part,
    ]);

    $response = $this->deleteJson('/api/highlights/clear', [
        'phone' => $this->phone,
        'exam_id' => $this->examId,
        'section' => $this->section,
        'part' => $this->part,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'All highlights cleared successfully',
        ]);

    $this->assertDatabaseMissing('highlights', [
        'phone' => $this->phone,
        'exam_id' => $this->examId,
    ]);

    $this->assertDatabaseHas('highlights', [
        'id' => $otherHighlight->id,
    ]);
});

test('validates required fields when creating highlight', function () {
    $response = $this->postJson('/api/highlights', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['phone', 'exam_id', 'section', 'part', 'text', 'color', 'start_offset', 'end_offset']);
});

test('validates color must be one of allowed values', function () {
    $highlightData = [
        'phone' => $this->phone,
        'exam_id' => $this->examId,
        'section' => $this->section,
        'part' => $this->part,
        'text' => 'This is a highlighted text',
        'color' => 'invalid_color',
        'start_offset' => 0,
        'end_offset' => 25,
    ];

    $response = $this->postJson('/api/highlights', $highlightData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['color']);
});

test('validates offsets must be integers', function () {
    $highlightData = [
        'phone' => $this->phone,
        'exam_id' => $this->examId,
        'section' => $this->section,
        'part' => $this->part,
        'text' => 'This is a highlighted text',
        'color' => 'yellow',
        'start_offset' => 'invalid',
        'end_offset' => 'invalid',
    ];

    $response = $this->postJson('/api/highlights', $highlightData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['start_offset', 'end_offset']);
});
