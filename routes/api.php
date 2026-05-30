<?php

use App\Http\Controllers\Api\CentralWebhookController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DraftController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\ResultController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Central Super Admin Webhook (HMAC-authenticated, no CSRF)
Route::post('central/access-update', [CentralWebhookController::class, 'handle']);

Route::middleware(['api'])->group(function () {
    // Dashboard API route (requires authentication)
    Route::middleware('auth:sanctum')->get('dashboard', [DashboardController::class, 'index']);

    // Exam API routes
    Route::prefix('exams')->group(function () {
        Route::get('/', [ExamController::class, 'index']);
        Route::get('/check/{examId}', [ExamController::class, 'checkExists']);
        Route::get('/{examId}', [ExamController::class, 'show']);
    });
    // Exam results API route
    Route::get('exam-results', [ExamController::class, 'getResults']);

    // Result API routes
    Route::post('results/store-section', [ResultController::class, 'storeSection']);
    Route::get('results/get', [ResultController::class, 'getResult']);

    // Draft API routes
    Route::post('drafts/save-final', [DraftController::class, 'saveFinalDraft']);
    Route::get('drafts/answers', [DraftController::class, 'getDraftAnswers']);
    Route::post('drafts/calculate-score', [DraftController::class, 'calculateScore']);
    Route::post('drafts/save-results', [DraftController::class, 'saveResults']);
    Route::get('drafts/results', [DraftController::class, 'getResults']);
    Route::delete('drafts/cleanup', [DraftController::class, 'cleanupDrafts']);
    Route::post('drafts/cleanup', [DraftController::class, 'cleanupDrafts']);

    // User route for API
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });
});
