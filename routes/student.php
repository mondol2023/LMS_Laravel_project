<?php

use App\Http\Controllers\Student\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Student Routes
|--------------------------------------------------------------------------
|
| Routes for student portal - requires authentication and student role
|
*/

Route::middleware(['auth', 'verified', 'student'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('student.dashboard');
    Route::get('/profile', [DashboardController::class, 'profile'])->name('student.profile');
    Route::get('/my-results', [DashboardController::class, 'results'])->name('student.results');
    Route::get('/my-results/{result}', [DashboardController::class, 'resultShow'])->name('student.result.show');
    Route::get('/reports', [DashboardController::class, 'reports'])->name('student.reports');
});
