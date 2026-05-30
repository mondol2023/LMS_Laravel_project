<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     * Only allow users with role 'admin' to access.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (!Auth::user()->isAdmin()) {
            // Redirect students to their dashboard
            if (Auth::user()->isStudent()) {
                return redirect()->route('student.dashboard')
                    ->with('error', 'You do not have access to the admin area.');
            }

            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
