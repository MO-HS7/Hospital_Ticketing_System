<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     * For API requests, return null to trigger JSON 401 response.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Always return null for API routes to get JSON 401 instead of redirect
        if ($request->is('api/*') || $request->expectsJson()) {
            return null;
        }
        
        // For web routes, check if login route exists before redirecting
        if (app('router')->has('login')) {
            return route('login');
        }
        
        return null;
    }
}
