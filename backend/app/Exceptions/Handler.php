<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
    
    /**
     * Handle unauthenticated users - return JSON 401 for API requests.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // For API requests, always return JSON 401 (never redirect)
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'error' => 'Authentication required for this endpoint.',
            ], 401);
        }
        
        // For web requests, try to redirect to login if route exists
        if (app('router')->has('login')) {
            return redirect()->guest(route('login'));
        }
        
        // Fallback: return 401 JSON
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
}
