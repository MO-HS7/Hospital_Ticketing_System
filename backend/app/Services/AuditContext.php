<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Request-scoped audit context.
 * 
 * Captures request metadata for audit events. Populated by middleware for HTTP requests,
 * with fallback values for CLI/jobs.
 */
class AuditContext
{
    public ?string $requestId = null;
    public ?string $ip = null;
    public ?string $userAgent = null;
    public ?string $route = null;
    public ?string $method = null;
    public ?int $actorId = null;
    public ?string $actorRole = null;

    /**
     * Initialize context with a new request ID.
     * Called early in request lifecycle or at start of CLI command.
     */
    public function initialize(): void
    {
        $this->requestId = (string) Str::uuid();
    }

    /**
     * Set HTTP request context from middleware.
     */
    public function setHttpContext(
        string $ip,
        ?string $userAgent,
        ?string $route,
        string $method
    ): void {
        $this->ip = $ip;
        $this->userAgent = $userAgent;
        $this->route = $route;
        $this->method = $method;
    }

    /**
     * Set actor context.
     */
    public function setActor(?int $actorId, ?string $actorRole): void
    {
        $this->actorId = $actorId;
        $this->actorRole = $actorRole;
    }

    /**
     * Get context as array for audit event.
     */
    public function toArray(): array
    {
        return [
            'request_id' => $this->requestId,
            'ip_address' => $this->ip,
            'user_agent' => $this->userAgent,
            'route' => $this->route,
            'method' => $this->method,
            'actor_id' => $this->actorId,
            'actor_role' => $this->actorRole,
        ];
    }

    /**
     * Check if context has been initialized.
     */
    public function isInitialized(): bool
    {
        return $this->requestId !== null;
    }

    /**
     * Reset context (useful for testing or queue workers).
     */
    public function reset(): void
    {
        $this->requestId = null;
        $this->ip = null;
        $this->userAgent = null;
        $this->route = null;
        $this->method = null;
        $this->actorId = null;
        $this->actorRole = null;
    }
}
