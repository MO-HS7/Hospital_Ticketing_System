<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Chatbot Metrics Service
 * 
 * Tracks observability metrics for the chatbot:
 * - AI usage vs fallback rate
 * - Average latency
 * - Error rate
 * - Ticket completion rate
 * - Emergency trigger count
 */
class ChatbotMetricsService
{
    const CACHE_KEY_PREFIX = 'chatbot_metrics:';
    const METRICS_TTL = 86400; // 24 hours

    /**
     * Record an AI request (success or fallback).
     */
    public function recordAIRequest(bool $usedAI, int $latencyMs, bool $success = true): void
    {
        $today = now()->format('Y-m-d');
        $key = self::CACHE_KEY_PREFIX . $today;
        
        $metrics = Cache::get($key, $this->getEmptyMetrics());
        
        $metrics['total_requests']++;
        
        if ($usedAI) {
            $metrics['ai_requests']++;
            if ($success) {
                $metrics['ai_success']++;
            } else {
                $metrics['ai_errors']++;
            }
        } else {
            $metrics['fallback_requests']++;
        }
        
        // Update latency stats
        $metrics['total_latency_ms'] += $latencyMs;
        $metrics['max_latency_ms'] = max($metrics['max_latency_ms'], $latencyMs);
        
        // Update last successful AI call if applicable
        if ($usedAI && $success) {
            $metrics['last_ai_success'] = now()->toIso8601String();
        }
        
        Cache::put($key, $metrics, self::METRICS_TTL);
    }

    /**
     * Record an emergency trigger.
     */
    public function recordEmergency(): void
    {
        $today = now()->format('Y-m-d');
        $key = self::CACHE_KEY_PREFIX . $today;
        
        $metrics = Cache::get($key, $this->getEmptyMetrics());
        $metrics['emergency_triggers']++;
        
        Cache::put($key, $metrics, self::METRICS_TTL);
    }

    /**
     * Record a ticket completion (5-step flow finished).
     */
    public function recordTicketCompletion(bool $completed): void
    {
        $today = now()->format('Y-m-d');
        $key = self::CACHE_KEY_PREFIX . $today;
        
        $metrics = Cache::get($key, $this->getEmptyMetrics());
        $metrics['flow_started']++;
        
        if ($completed) {
            $metrics['flow_completed']++;
        }
        
        Cache::put($key, $metrics, self::METRICS_TTL);
    }

    /**
     * Get metrics for a specific date (default: today).
     */
    public function getMetrics(?string $date = null): array
    {
        $date = $date ?? now()->format('Y-m-d');
        $key = self::CACHE_KEY_PREFIX . $date;
        
        $metrics = Cache::get($key, $this->getEmptyMetrics());
        
        // Calculate derived metrics
        $metrics['ai_usage_rate'] = $metrics['total_requests'] > 0 
            ? round(($metrics['ai_requests'] / $metrics['total_requests']) * 100, 1) 
            : 0;
            
        $metrics['fallback_rate'] = $metrics['total_requests'] > 0 
            ? round(($metrics['fallback_requests'] / $metrics['total_requests']) * 100, 1) 
            : 0;
            
        $metrics['error_rate'] = $metrics['ai_requests'] > 0 
            ? round(($metrics['ai_errors'] / $metrics['ai_requests']) * 100, 1) 
            : 0;
            
        $metrics['avg_latency_ms'] = $metrics['total_requests'] > 0 
            ? round($metrics['total_latency_ms'] / $metrics['total_requests']) 
            : 0;
            
        $metrics['completion_rate'] = $metrics['flow_started'] > 0 
            ? round(($metrics['flow_completed'] / $metrics['flow_started']) * 100, 1) 
            : 0;
        
        $metrics['date'] = $date;
        
        return $metrics;
    }

    /**
     * Get AI status for admin dashboard.
     */
    public function getAIStatus(): array
    {
        $aiEnabled = (bool) config('services.chatbot.ai_enabled', false);
        $apiKeySet = !empty(config('services.gemini.api_key'));
        $model = config('services.gemini.model', 'gemini-2.0-flash');
        
        $todayMetrics = $this->getMetrics();
        
        return [
            'ai_enabled' => $aiEnabled,
            'api_key_configured' => $apiKeySet,
            'model' => $model,
            'status' => $aiEnabled && $apiKeySet ? 'active' : 'inactive',
            'status_label' => $aiEnabled && $apiKeySet 
                ? 'AI: Gemini (ON)' 
                : ($aiEnabled ? 'AI: Missing API Key' : 'Fallback Mode (OFF)'),
            'last_successful_call' => $todayMetrics['last_ai_success'],
            'today_stats' => [
                'total_requests' => $todayMetrics['total_requests'],
                'ai_usage_rate' => $todayMetrics['ai_usage_rate'] . '%',
                'avg_latency_ms' => $todayMetrics['avg_latency_ms'],
                'error_rate' => $todayMetrics['error_rate'] . '%',
            ],
        ];
    }

    /**
     * Get empty metrics structure.
     */
    protected function getEmptyMetrics(): array
    {
        return [
            'total_requests' => 0,
            'ai_requests' => 0,
            'ai_success' => 0,
            'ai_errors' => 0,
            'fallback_requests' => 0,
            'emergency_triggers' => 0,
            'flow_started' => 0,
            'flow_completed' => 0,
            'total_latency_ms' => 0,
            'max_latency_ms' => 0,
            'last_ai_success' => null,
        ];
    }
}
