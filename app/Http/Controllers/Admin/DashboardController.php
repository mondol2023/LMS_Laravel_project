<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\TestAttempt;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $totalExams = Exam::count();
        $totalAttempts = TestAttempt::count();
        $activeExams = Exam::where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->count();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'totalUsers' => $totalUsers,
                'totalExams' => $totalExams,
                'totalAttempts' => $totalAttempts,
                'activeExams' => $activeExams,
            ],
        ]);
    }
}
