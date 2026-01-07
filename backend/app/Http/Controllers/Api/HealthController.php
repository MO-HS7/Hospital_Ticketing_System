<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Lightweight health check endpoint.
 * Optimized for speed - caches schema checks to avoid repeated information_schema queries.
 */
class HealthController extends Controller
{
    /**
     * Cache TTL for table existence check (60 seconds)
     */
    private const TABLE_CHECK_TTL = 60;

    public function __invoke(): JsonResponse
    {
        $cacheStore = config('cache.default', 'redis');

        $checks = [
            'db' => ['ok' => true],
            'tables' => ['ok' => true, 'missing' => []],
            'cache' => ['ok' => true, 'store' => $cacheStore],
        ];

        // DB check: simple ping with short timeout
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
        } catch (Throwable $e) {
            $checks['db']['ok'] = false;
        }

        // Tables check: cached for 60 seconds to avoid repeated schema queries
        try {
            $tableCheckResult = Cache::remember('health:tables_check', self::TABLE_CHECK_TTL, function () {
                $requiredTables = [
                    'users',
                    'departments', 
                    'tickets',
                    'ticket_notes',
                    'ticket_events',
                    'personal_access_tokens',
                    'roles',
                    'permissions',
                ];

                $missing = [];
                
                // Single query to get all existing tables
                $existingTables = DB::select("SHOW TABLES");
                $tableColumn = 'Tables_in_' . config('database.connections.mysql.database');
                $existingTableNames = array_map(fn($t) => $t->$tableColumn ?? '', $existingTables);

                foreach ($requiredTables as $table) {
                    if (!in_array($table, $existingTableNames)) {
                        $missing[] = $table;
                    }
                }

                return ['ok' => count($missing) === 0, 'missing' => $missing];
            });

            $checks['tables'] = $tableCheckResult;
        } catch (Throwable $e) {
            $checks['tables']['ok'] = false;
            $checks['tables']['missing'] = ['error_checking'];
        }

        // Cache check: simple put/get/delete
        try {
            $key = 'health_probe';
            Cache::put($key, '1', 10);
            $value = Cache::get($key);
            if ((string) $value !== '1') {
                $checks['cache']['ok'] = false;
            }
            Cache::forget($key);
        } catch (Throwable $e) {
            $checks['cache']['ok'] = false;
        }

        $ok = $checks['db']['ok'] && $checks['tables']['ok'] && $checks['cache']['ok'];

        return response()->json([
            'status' => $ok ? 'ok' : 'error',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $ok ? 200 : 503);
    }
}
