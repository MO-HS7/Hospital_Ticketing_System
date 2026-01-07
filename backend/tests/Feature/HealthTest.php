<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_ok_when_dependencies_are_healthy(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.db.ok', true)
            ->assertJsonPath('checks.tables.ok', true)
            ->assertJsonPath('checks.cache.ok', true)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'checks' => [
                    'db' => ['ok'],
                    'tables' => ['ok', 'missing'],
                    'cache' => ['ok', 'store'],
                ],
            ]);

        $this->assertSame([], $response->json('checks.tables.missing'));
    }

    public function test_health_endpoint_cache_can_be_cleared(): void
    {
        // First request - should cache the result
        $response1 = $this->getJson('/api/health');
        $response1->assertOk();

        // Clear the cache
        \Illuminate\Support\Facades\Cache::forget('health:tables_check');

        // Second request - should recalculate and still be OK
        $response2 = $this->getJson('/api/health');
        $response2->assertOk()
            ->assertJsonPath('checks.tables.ok', true);
    }
}
