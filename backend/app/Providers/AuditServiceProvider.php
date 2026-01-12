<?php

namespace App\Providers;

use App\Services\AuditContext;
use App\Services\AuditLogger;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind AuditContext as singleton per request
        $this->app->singleton(AuditContext::class, function () {
            return new AuditContext();
        });

        // Bind AuditLogger with AuditContext dependency
        $this->app->singleton(AuditLogger::class, function ($app) {
            return new AuditLogger($app->make(AuditContext::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Initialize context for CLI commands
        if ($this->app->runningInConsole()) {
            $this->initializeCliContext();
        }
    }

    /**
     * Initialize audit context for CLI/jobs.
     */
    protected function initializeCliContext(): void
    {
        $context = $this->app->make(AuditContext::class);
        
        if (!$context->isInitialized()) {
            $context->initialize();
            $context->setHttpContext(
                ip: '127.0.0.1',
                userAgent: 'CLI/' . php_sapi_name(),
                route: 'cli',
                method: 'CLI'
            );
        }
    }
}
