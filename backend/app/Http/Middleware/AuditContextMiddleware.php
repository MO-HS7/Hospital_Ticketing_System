<?php

namespace App\Http\Middleware;

use App\Services\AuditContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Audit Context Middleware
 * 
 * Initializes the AuditContext for every request with:
 * - Unique request_id (UUID)
 * - IP address
 * - User agent
 * - Route name
 * - HTTP method
 * - Actor info (when authenticated)
 */
class AuditContextMiddleware
{
    protected AuditContext $context;

    public function __construct(AuditContext $context)
    {
        $this->context = $context;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Initialize with fresh request_id
        $this->context->initialize();

        // Set HTTP context
        $this->context->setHttpContext(
            ip: $request->ip() ?? 'unknown',
            userAgent: $request->userAgent(),
            route: $request->route()?->getName(),
            method: $request->method()
        );

        // Set actor context if authenticated
        $user = $request->user();
        if ($user) {
            $this->context->setActor(
                actorId: $user->id,
                actorRole: $user->roles->first()?->name
            );
        }

        return $next($request);
    }
}
