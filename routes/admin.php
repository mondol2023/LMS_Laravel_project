<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\ResultController;
use Illuminate\Support\Facades\Route;

// Admin login routes (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [AuthController::class, 'login'])->name('admin.login.store');
});

// Protected admin routes
Route::middleware('admin')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Exam routes
    Route::get('/exams', [ExamController::class, 'index'])->name('admin.exams.index');
    Route::get('/exams/create', [ExamController::class, 'create'])->name('admin.exams.create');
    Route::post('/exams', [ExamController::class, 'store'])->name('admin.exams.store');
    Route::get('/exams/{exam}', [ExamController::class, 'show'])->name('admin.exams.show');
    Route::get('/exams/{exam}/edit', [ExamController::class, 'edit'])->name('admin.exams.edit');
    Route::put('/exams/{exam}', [ExamController::class, 'update'])->name('admin.exams.update');
    Route::delete('/exams/{exam}', [ExamController::class, 'destroy'])->name('admin.exams.destroy');

    // Results routes
    Route::get('/results', [ResultController::class, 'index'])->name('admin.results.index');
    Route::get('/results/{result}', [ResultController::class, 'show'])->name('admin.results.show');
    Route::put('/results/{result}/speaking', [ResultController::class, 'updateSpeaking'])->name('admin.results.update-speaking');
    Route::put('/results/{result}/writing', [ResultController::class, 'updateWriting'])->name('admin.results.update-writing');
});
