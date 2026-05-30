<?php

namespace App\Http\Middleware;

use App\Services\CoachingAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function __construct(
        protected CoachingAccessService $coachingAccessService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $resourceType  Type of resource (full_mock_test, partial_reading, practice_writing, etc.)
     */
    public function handle(Request $request, Closure $next, string $resourceType): Response
    {
        // Bypass subscription checks for coaching center access
        if ($this->coachingAccessService->isCoachingAccess($request)) {
            return $next($request);
        }

        $user = $request->user();

        // If user is not authenticated, redirect to login
        if (! $user) {
            return redirect()->route('login')->with('error', 'Please login to continue.');
        }

        // Check if user has active subscription
        if (! $user->hasActiveSubscription()) {
            return back()->withErrors([
                'subscription' => 'You need an active subscription to access this resource. Please contact admin.',
            ]);
        }

        // Check if user can access this specific resource
        if (! $user->canAccessResource($resourceType)) {
            $message = $this->getErrorMessage($resourceType);

            return back()->withErrors([
                'subscription' => $message,
            ]);
        }

        return $next($request);
    }

    /**
     * Get appropriate error message based on resource type
     */
    protected function getErrorMessage(string $resourceType): string
    {
        return match ($resourceType) {
            'full_mock_test' => 'You have reached your Full Mock Test limit. Please upgrade your package or contact admin.',
            'partial_reading' => 'You have reached your Reading Part limit. Please upgrade your package or contact admin.',
            'partial_writing' => 'You have reached your Writing Part limit. Please upgrade your package or contact admin.',
            'partial_listening' => 'You have reached your Listening Part limit. Please upgrade your package or contact admin.',
            'partial_speaking' => 'You have reached your Speaking Part limit. Please upgrade your package or contact admin.',
            'practice_reading' => 'Reading Practice is not enabled in your package. Please upgrade to access this module.',
            'practice_listening' => 'Listening Practice is not enabled in your package. Please upgrade to access this module.',
            'practice_writing' => 'You have reached your Writing Practice limit or it is not enabled. Please upgrade your package.',
            'practice_speaking' => 'You have reached your Speaking Practice limit or it is not enabled. Please upgrade your package.',
            default => 'You do not have access to this resource. Please contact admin.',
        };
    }
}
