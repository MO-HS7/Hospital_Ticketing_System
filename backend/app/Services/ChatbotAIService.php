<?php

namespace App\Services;

use App\Models\Department;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI Adapter Layer for Chatbot
 * 
 * This service provides an abstraction layer for AI-powered symptom analysis.
 * Uses Google Gemini when enabled, otherwise falls back to rule-based analysis.
 * 
 * Performance optimizations:
 * - Departments cached for 10 minutes
 * - Strict HTTP timeouts (connect: 2s, total: 8s)
 * - Immediate fallback on timeout/error
 */
class ChatbotAIService
{
    protected bool $aiEnabled;
    protected ?string $geminiApiKey;
    protected string $geminiModel;
    protected string $geminiEndpoint;
    
    // Timing constants
    const CONNECT_TIMEOUT = 2;    // seconds
    const REQUEST_TIMEOUT = 8;    // seconds
    const CACHE_TTL = 600;        // 10 minutes
    
    // Timing logs
    protected array $timings = [];

    public function __construct()
    {
        $this->aiEnabled = config('services.chatbot.ai_enabled', false);
        $this->geminiApiKey = config('services.gemini.api_key');
        $this->geminiModel = config('services.gemini.model', 'gemini-2.0-flash');
        $this->geminiEndpoint = config('services.gemini.endpoint', 'https://generativelanguage.googleapis.com/v1beta/models');
    }

    /**
     * Analyze user message and return structured response for interactive chat.
     *
     * @param string $message User's symptom description
     * @param string $locale User's locale (en/ar)
     * @param array $context Additional context (session_id, history)
     * @return array Structured response with messages, quick_replies, suggestions
     */
    public function analyzeSymptoms(string $message, string $locale = 'en', array $context = []): array
    {
        $startTime = microtime(true);
        $this->timings = [];
        
        // Get cached departments
        $deptStart = microtime(true);
        $departments = $this->getCachedDepartments($locale);
        $this->timings['departments_fetch_ms'] = round((microtime(true) - $deptStart) * 1000);
        
        $result = null;
        $usedFallback = false;
        $fallbackReason = null;

        // Try AI if enabled
        if ($this->aiEnabled && $this->geminiApiKey) {
            $aiStart = microtime(true);
            try {
                $result = $this->analyzeWithGemini($message, $locale, $departments, $context);
                $this->timings['gemini_call_ms'] = round((microtime(true) - $aiStart) * 1000);
            } catch (\Throwable $e) {
                $this->timings['gemini_call_ms'] = round((microtime(true) - $aiStart) * 1000);
                $usedFallback = true;
                $fallbackReason = $e->getMessage();
                Log::warning('Gemini API failed, using fallback', [
                    'error' => $e->getMessage(),
                    'duration_ms' => $this->timings['gemini_call_ms'],
                ]);
            }
        } else {
            $usedFallback = true;
            $fallbackReason = $this->aiEnabled ? 'no_api_key' : 'ai_disabled';
        }

        // Use rule-based fallback
        if (!$result) {
            $fallbackStart = microtime(true);
            $result = $this->analyzeWithRules($message, $locale, $departments);
            $this->timings['fallback_ms'] = round((microtime(true) - $fallbackStart) * 1000);
        }

        $this->timings['total_ms'] = round((microtime(true) - $startTime) * 1000);
        
        // Log performance metrics
        Log::info('Chatbot analysis completed', [
            'timings' => $this->timings,
            'used_fallback' => $usedFallback,
            'fallback_reason' => $fallbackReason,
            'ai_enabled' => $this->aiEnabled,
        ]);

        return array_merge($result, [
            'ai_powered' => !$usedFallback,
            '_debug' => [
                'timings' => $this->timings,
                'used_fallback' => $usedFallback,
                'fallback_reason' => $fallbackReason,
            ],
        ]);
    }

    /**
     * Get departments with caching.
     */
    protected function getCachedDepartments(string $locale): array
    {
        $cacheKey = "chatbot_departments_{$locale}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($locale) {
            return Department::where('is_active', true)
                ->get(['id', 'slug', 'name_en', 'name_ar', 'icon_key'])
                ->map(fn($d) => [
                    'id' => $d->id,
                    'slug' => $d->slug,
                    'name' => $locale === 'ar' ? $d->name_ar : $d->name_en,
                    'name_en' => $d->name_en,
                    'name_ar' => $d->name_ar,
                    'icon_key' => $d->icon_key,
                ])
                ->toArray();
        });
    }

    /**
     * AI-powered analysis using Google Gemini with strict timeouts.
     */
    protected function analyzeWithGemini(string $message, string $locale, array $departments, array $context = []): array
    {
        $departmentList = collect($departments)->pluck('name', 'slug')->toJson(JSON_UNESCAPED_UNICODE);
        $isArabic = $locale === 'ar';
        $lang = $isArabic ? 'Arabic' : 'English';

        // Build prompt for structured output
        $systemPrompt = <<<PROMPT
You are a hospital symptom triage assistant. Analyze patient symptoms and suggest departments.

Available departments (slug => name):
{$departmentList}

RESPOND IN {$lang} ONLY.

Instructions:
1. If symptoms are vague, ask 1-2 clarifying questions
2. If symptoms are clear, suggest 1-3 relevant departments
3. For emergency symptoms (chest pain + breathing, severe bleeding), prioritize Emergency

RESPOND WITH VALID JSON ONLY:
{
  "messages": ["string array of bot responses"],
  "quick_replies": ["optional clarifying questions as clickable chips"],
  "departments": ["slug1", "slug2"],
  "is_emergency": false,
  "needs_clarification": false
}

CRITICAL: Output ONLY the JSON, no markdown, no explanation.
PROMPT;

        $url = "{$this->geminiEndpoint}/{$this->geminiModel}:generateContent?key={$this->geminiApiKey}";

        // Strict timeouts
        $response = Http::connectTimeout(self::CONNECT_TIMEOUT)
            ->timeout(self::REQUEST_TIMEOUT)
            ->retry(1, 100, function ($exception) {
                // Only retry on connection errors, not on slow responses
                return $exception instanceof \Illuminate\Http\Client\ConnectionException;
            })
            ->post($url, [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [['text' => $systemPrompt]],
                    ],
                    [
                        'role' => 'model',
                        'parts' => [['text' => '{"messages":["I understand. I will respond with valid JSON only."],"quick_replies":[],"departments":[],"is_emergency":false,"needs_clarification":false}']],
                    ],
                    [
                        'role' => 'user',
                        'parts' => [['text' => "Patient: \"{$message}\""]],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'topK' => 20,
                    'topP' => 0.9,
                    'maxOutputTokens' => 512,
                ],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Gemini API error: ' . $response->status());
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        
        if (!$text) {
            throw new \RuntimeException('Empty Gemini response');
        }

        // Clean and parse JSON
        $text = preg_replace('/^```json\s*/i', '', trim($text));
        $text = preg_replace('/\s*```$/i', '', $text);
        
        $parsed = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON from Gemini');
        }

        return $this->formatResponse($parsed, $departments, $locale);
    }

    /**
     * Rule-based symptom analysis (fast fallback).
     */
    protected function analyzeWithRules(string $message, string $locale, array $departments): array
    {
        $messageLower = mb_strtolower($message);
        $matchedSlugs = [];
        $isEmergency = false;
        $needsClarification = false;

        // Check for emergency keywords first
        $emergencyKeywords = ['bleeding', 'accident', 'unconscious', 'heart attack', 'can\'t breathe',
            'نزيف', 'حادث', 'إغماء', 'نوبة قلبية', 'لا أستطيع التنفس'];
        foreach ($emergencyKeywords as $kw) {
            if (str_contains($messageLower, $kw)) {
                $matchedSlugs[] = 'emergency';
                $isEmergency = true;
                break;
            }
        }

        // Regular keyword matching
        $keywords = $this->getKeywordMappings();
        foreach ($keywords as $keyword => $slug) {
            if (str_contains($messageLower, $keyword)) {
                if (!in_array($slug, $matchedSlugs)) {
                    $matchedSlugs[] = $slug;
                }
            }
        }

        // If no matches, need clarification
        if (empty($matchedSlugs)) {
            $needsClarification = true;
            $matchedSlugs = ['internal-medicine']; // Default fallback
        }

        // Build quick replies for clarification
        $quickReplies = [];
        $messages = [];

        if ($needsClarification) {
            if ($locale === 'ar') {
                $messages[] = 'لم أتمكن من تحديد القسم المناسب. هل يمكنك وصف أعراضك بشكل أوضح؟';
                $quickReplies = ['ألم في الرأس', 'ألم في الصدر', 'مشاكل في العين', 'آلام في العظام', 'مشاكل جلدية'];
            } else {
                $messages[] = "I couldn't identify the best department. Can you describe your symptoms more clearly?";
                $quickReplies = ['Headache', 'Chest pain', 'Eye problems', 'Bone/joint pain', 'Skin issues'];
            }
        } elseif ($isEmergency) {
            if ($locale === 'ar') {
                $messages[] = '⚠️ يبدو أن حالتك طارئة. يرجى التوجه فوراً إلى قسم الطوارئ أو الاتصال بالإسعاف.';
            } else {
                $messages[] = '⚠️ This sounds like an emergency. Please go to the Emergency department immediately or call an ambulance.';
            }
        } else {
            if ($locale === 'ar') {
                $messages[] = 'بناءً على أعراضك، أقترح القسم(الأقسام) التالية:';
            } else {
                $messages[] = 'Based on your symptoms, I suggest the following department(s):';
            }
        }

        return $this->formatResponse([
            'messages' => $messages,
            'quick_replies' => $quickReplies,
            'departments' => $matchedSlugs,
            'is_emergency' => $isEmergency,
            'needs_clarification' => $needsClarification,
        ], $departments, $locale);
    }

    /**
     * Format response with full department info.
     */
    protected function formatResponse(array $parsed, array $departments, string $locale): array
    {
        $suggestedSlugs = $parsed['departments'] ?? [];
        $deptLookup = collect($departments)->keyBy('slug');

        $suggestions = [];
        foreach ($suggestedSlugs as $slug) {
            if (isset($deptLookup[$slug])) {
                $dept = $deptLookup[$slug];
                $suggestions[] = [
                    'department_id' => $dept['id'],
                    'department_name' => $dept['name_en'],
                    'department_name_ar' => $dept['name_ar'],
                    'name_localized' => $locale === 'ar' ? $dept['name_ar'] : $dept['name_en'],
                    'slug' => $slug,
                    'icon_key' => $dept['icon_key'] ?? null,
                ];
            }
        }

        return [
            'messages' => $parsed['messages'] ?? [],
            'quick_replies' => $parsed['quick_replies'] ?? [],
            'suggestions' => $suggestions,
            'is_emergency' => $parsed['is_emergency'] ?? false,
            'needs_clarification' => $parsed['needs_clarification'] ?? false,
        ];
    }

    /**
     * Get keyword to department slug mappings.
     */
    protected function getKeywordMappings(): array
    {
        return [
            // English keywords
            'heart' => 'cardiology', 'chest pain' => 'cardiology', 'palpitation' => 'cardiology',
            'blood pressure' => 'cardiology',
            'bone' => 'orthopedics', 'fracture' => 'orthopedics', 'joint' => 'orthopedics',
            'muscle' => 'orthopedics', 'back pain' => 'orthopedics', 'knee' => 'orthopedics',
            'headache' => 'neurology', 'brain' => 'neurology', 'migraine' => 'neurology',
            'dizzy' => 'neurology', 'numbness' => 'neurology', 'seizure' => 'neurology',
            'child' => 'pediatrics', 'baby' => 'pediatrics', 'infant' => 'pediatrics', 'kid' => 'pediatrics',
            'skin' => 'dermatology', 'rash' => 'dermatology', 'acne' => 'dermatology', 'itch' => 'dermatology',
            'eye' => 'ophthalmology', 'vision' => 'ophthalmology', 'blind' => 'ophthalmology',
            'ear' => 'ent', 'throat' => 'ent', 'nose' => 'ent', 'sinus' => 'ent', 'hearing' => 'ent',
            'fever' => 'internal-medicine', 'cough' => 'internal-medicine', 'cold' => 'internal-medicine',
            'flu' => 'internal-medicine', 'tired' => 'internal-medicine',
            'stomach' => 'gastroenterology', 'digestive' => 'gastroenterology', 'nausea' => 'gastroenterology',
            'breathing' => 'pulmonology', 'lung' => 'pulmonology', 'asthma' => 'pulmonology',
            // Arabic keywords
            'قلب' => 'cardiology', 'صدر' => 'cardiology', 'ضغط' => 'cardiology',
            'عظام' => 'orthopedics', 'مفصل' => 'orthopedics', 'ظهر' => 'orthopedics', 'ركبة' => 'orthopedics',
            'رأس' => 'neurology', 'صداع' => 'neurology', 'دوخة' => 'neurology', 'تنميل' => 'neurology',
            'أطفال' => 'pediatrics', 'طفل' => 'pediatrics', 'رضيع' => 'pediatrics',
            'جلد' => 'dermatology', 'طفح' => 'dermatology', 'حكة' => 'dermatology',
            'عين' => 'ophthalmology', 'نظر' => 'ophthalmology', 'رؤية' => 'ophthalmology',
            'أذن' => 'ent', 'حلق' => 'ent', 'أنف' => 'ent', 'سمع' => 'ent',
            'حرارة' => 'internal-medicine', 'سعال' => 'internal-medicine', 'زكام' => 'internal-medicine',
            'معدة' => 'gastroenterology', 'هضم' => 'gastroenterology', 'غثيان' => 'gastroenterology',
            'تنفس' => 'pulmonology', 'رئة' => 'pulmonology', 'ربو' => 'pulmonology',
        ];
    }

    /**
     * Check if AI is enabled and configured.
     */
    public function isAIEnabled(): bool
    {
        return $this->aiEnabled && !empty($this->geminiApiKey);
    }

    /**
     * Get last request timings for debugging.
     */
    public function getTimings(): array
    {
        return $this->timings;
    }
}
