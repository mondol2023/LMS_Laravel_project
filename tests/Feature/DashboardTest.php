<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
});

test('authenticated users can access dashboard via web', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(function ($page) {
        $page->component('Dashboard')
            ->has('stats', function ($stats) {
                $stats->has('total_attempts')
                    ->has('completed_tests')
                    ->has('average_band')
                    ->has('best_score');
            })
            ->has('recentAttempts')
            ->has('availableTests')
            ->has('topPerformers');
    });
});

test('authenticated users can access dashboard via api', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/dashboard');

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'success',
        'data' => [
            'stats' => [
                'total_attempts',
                'completed_tests',
                'average_band',
                'best_score',
            ],
            'recent_attempts',
            'available_tests',
            'top_performers',
        ],
    ]);
    $response->assertJson([
        'success' => true,
    ]);
});

test('unauthenticated users cannot access api dashboard', function () {
    $response = $this->getJson('/api/dashboard');

    $response->assertUnauthorized();
});
