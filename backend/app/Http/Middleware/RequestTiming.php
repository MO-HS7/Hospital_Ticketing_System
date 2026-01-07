<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Log request timing with detailed DB performance metrics.
 * Tracks total request time, query count, query time, and connection time.
 */
class RequestTiming
{
    const SLOW_THRESHOLD_MS = 500; // Log requests slower than 500ms

    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $connectionTime = null;
        
        // Enable query logging
        DB::enableQueryLog();

        // Measure first DB connection time by executing a simple ping
        try {
            $connStart = microtime(true);
            DB::connection()->getPdo();
            $connectionTime = (microtime(true) - $connStart) * 1000;
        } catch (\Exception $e) {
            Log::error('DB connection failed', ['error' => $e->getMessage()]);
        }

        $response = $next($request);

        $duration = (microtime(true) - $startTime) * 1000; // Convert to ms
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        $queryTime = collect($queries)->sum('time');

        // Log slow requests with detailed breakdown
        if ($duration > self::SLOW_THRESHOLD_MS) {
            Log::warning('Slow request detected', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'duration_ms' => round($duration, 2),
                'connection_ms' => $connectionTime !== null ? round($connectionTime, 2) : 'N/A',
                'query_count' => $queryCount,
                'query_time_ms' => round($queryTime, 2),
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'slow_queries' => collect($queries)
                    ->filter(fn($q) => $q['time'] > 50)
                    ->map(fn($q) => [
                        'sql' => \Illuminate\Support\Str::limit($q['query'], 200),
                        'time_ms' => $q['time'],
                    ])
                    ->values()
                    ->toArray(),
            ]);
        }

        // Always add timing headers for debugging
        $response->headers->set('X-Request-Duration-Ms', round($duration, 2));
        $response->headers->set('X-DB-Query-Count', $queryCount);
        $response->headers->set('X-DB-Query-Time-Ms', round($queryTime, 2));
        if ($connectionTime !== null) {
            $response->headers->set('X-DB-Connection-Ms', round($connectionTime, 2));
        }

        // Log all requests in dev environment
        if (config('app.debug')) {
            Log::debug('Request timing', [
                'method' => $request->method(),
                'path' => $request->path(),
                'duration_ms' => round($duration, 2),
                'connection_ms' => $connectionTime !== null ? round($connectionTime, 2) : 'N/A',
                'query_count' => $queryCount,
                'query_time_ms' => round($queryTime, 2),
            ]);
        }

        DB::disableQueryLog();

        return $response;
    }
}
