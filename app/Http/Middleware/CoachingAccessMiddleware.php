<?php

namespace App\Http\Middleware;

use App\Services\CoachingAccessService;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CoachingAccessMiddleware
{
    public function __construct(
        protected CoachingAccessService $coachingAccessService
    ) {}

    /**
     * Handle an incoming request.
     * This middleware allows access to protected routes when accessing from coaching center.
     * When in coaching mode, auth and subscription checks are bypassed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Store coaching mode in request for later use
        $isCoachingMode = $this->coachingAccessService->isCoachingAccess($request);
        $request->attributes->set('is_coaching_mode', $isCoachingMode);

        // If in coaching mode, authentication is optional
        // If not in coaching mode and user is not authenticated, redirect to login
        if (! $isCoachingMode && ! $request->user()) {
            // For Inertia requests, use Inertia location redirect
            if ($request->header('X-Inertia')) {
                return Inertia::location(route('login'));
            }

            return redirect()->route('login')->with('error', 'Please login to access exams.');
        }

        return $next($request);
    }
}
