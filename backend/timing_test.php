<?php
/**
 * Performance timing test - run with: php timing_test.php
 * Tests each component separately to isolate bottlenecks.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== Performance Timing Test ===\n\n";

// 1. Measure bootstrap time
$bootStart = microtime(true);
$app->boot();
$bootTime = (microtime(true) - $bootStart) * 1000;
echo "1. App Bootstrap: " . round($bootTime, 2) . "ms\n";

// 2. Measure DB connection time
$dbStart = microtime(true);
try {
    \Illuminate\Support\Facades\DB::connection()->getPdo();
    $dbConnectTime = (microtime(true) - $dbStart) * 1000;
    echo "2. DB Connection: " . round($dbConnectTime, 2) . "ms\n";
} catch (Exception $e) {
    echo "2. DB Connection: FAILED - " . $e->getMessage() . "\n";
}

// 3. Measure simple query
$queryStart = microtime(true);
try {
    \Illuminate\Support\Facades\DB::select('SELECT 1');
    $queryTime = (microtime(true) - $queryStart) * 1000;
    echo "3. Simple Query (SELECT 1): " . round($queryTime, 2) . "ms\n";
} catch (Exception $e) {
    echo "3. Simple Query: FAILED\n";
}

// 4. Measure Redis connection
$redisStart = microtime(true);
try {
    \Illuminate\Support\Facades\Cache::put('test_key', 'test_value', 10);
    \Illuminate\Support\Facades\Cache::get('test_key');
    $redisTime = (microtime(true) - $redisStart) * 1000;
    echo "4. Redis Cache: " . round($redisTime, 2) . "ms\n";
} catch (Exception $e) {
    echo "4. Redis Cache: FAILED - " . $e->getMessage() . "\n";
}

// 5. Measure full health endpoint
$healthStart = microtime(true);
try {
    $controller = new \App\Http\Controllers\Api\HealthController();
    $response = $controller();
    $healthTime = (microtime(true) - $healthStart) * 1000;
    echo "5. Health Controller: " . round($healthTime, 2) . "ms\n";
} catch (Exception $e) {
    echo "5. Health Controller: FAILED - " . $e->getMessage() . "\n";
}

$totalTime = $bootTime + $dbConnectTime + $queryTime + $redisTime + $healthTime;
echo "\n=== Total: " . round($totalTime, 2) . "ms ===\n";
echo "\nIf total is <500ms but curl is >5s, the issue is Docker/nginx networking.\n";
