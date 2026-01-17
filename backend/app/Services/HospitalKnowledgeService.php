<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * Hospital Knowledge Base Service
 * 
 * Provides RAG (Retrieval-Augmented Generation) capabilities for the chatbot.
 * Retrieves relevant snippets from the hospital knowledge base based on user queries.
 */
class HospitalKnowledgeService
{
    protected array $kb;
    protected int $maxSnippets = 5;
    protected int $maxTokensPerSnippet = 150;

    public function __construct()
    {
        $this->kb = config('hospital_kb', []);
    }

    /**
     * Retrieve relevant knowledge snippets for a given query.
     *
     * @param string $query User's message
     * @param string $locale 'en' or 'ar'
     * @param array $context Additional context (current step, department, etc.)
     * @return array Array of relevant snippets with titles
     */
    public function retrieve(string $query, string $locale = 'en', array $context = []): array
    {
        $queryLower = mb_strtolower($this->normalizeArabic($query));
        $snippets = [];
        $scores = [];

        // 1. Check FAQs (highest priority for direct questions)
        foreach ($this->kb['faqs'] ?? [] as $faq) {
            $score = $this->calculateRelevance($queryLower, $faq['keywords'] ?? [], $locale);
            if ($score > 0) {
                $key = "faq_" . md5($faq['question_en']);
                $scores[$key] = $score + 10; // Boost FAQ matches
                $snippets[$key] = [
                    'type' => 'faq',
                    'title' => $locale === 'ar' ? $faq['question_ar'] : $faq['question_en'],
                    'content' => $locale === 'ar' ? $faq['answer_ar'] : $faq['answer_en'],
                ];
            }
        }

        // 2. Check department info (if discussing symptoms or departments)
        if ($this->containsSymptomKeywords($queryLower)) {
            foreach ($this->kb['departments_info'] ?? [] as $dept) {
                $deptContent = $locale === 'ar' ? $dept['ar'] : $dept['en'];
                $score = $this->calculateContentRelevance($queryLower, $deptContent);
                if ($score > 0) {
                    $key = "dept_" . $dept['slug'];
                    $scores[$key] = $score + 5; // Boost dept matches
                    $snippets[$key] = [
                        'type' => 'department',
                        'title' => $dept['slug'],
                        'content' => $deptContent,
                    ];
                }
            }
        }

        // 3. Check booking rules (if discussing appointments)
        if ($this->containsBookingKeywords($queryLower)) {
            foreach ($this->kb['booking_rules'] ?? [] as $rule) {
                $key = "booking_" . $rule['topic'];
                $scores[$key] = 8;
                $snippets[$key] = [
                    'type' => 'booking',
                    'title' => $rule['topic'],
                    'content' => $locale === 'ar' ? $rule['ar'] : $rule['en'],
                ];
            }
        }

        // 4. Check payment policies (if discussing payment)
        if ($this->containsPaymentKeywords($queryLower)) {
            foreach ($this->kb['payment_policies'] ?? [] as $policy) {
                $key = "payment_" . $policy['topic'];
                $scores[$key] = 8;
                $snippets[$key] = [
                    'type' => 'payment',
                    'title' => $policy['topic'],
                    'content' => $locale === 'ar' ? $policy['ar'] : $policy['en'],
                ];
            }
        }

        // 5. Check working hours (if asking about time/availability)
        if ($this->containsTimeKeywords($queryLower)) {
            foreach ($this->kb['working_hours'] ?? [] as $name => $hours) {
                $key = "hours_" . $name;
                $scores[$key] = 6;
                $snippets[$key] = [
                    'type' => 'hours',
                    'title' => $name,
                    'content' => $locale === 'ar' ? $hours['ar'] : $hours['en'],
                ];
            }
        }

        // 6. Always include triage guidance for symptom discussions
        if ($this->containsSymptomKeywords($queryLower)) {
            $triage = $this->kb['triage_guidance'] ?? [];
            if (!empty($triage)) {
                $urgentGuidance = $triage[0] ?? null; // Emergency guidance
                if ($urgentGuidance) {
                    $snippets['triage_emergency'] = [
                        'type' => 'triage',
                        'title' => 'Emergency Warning',
                        'content' => $locale === 'ar' ? $urgentGuidance['symptoms_ar'] . ' → ' . $urgentGuidance['action_ar'] 
                                                       : $urgentGuidance['symptoms_en'] . ' → ' . $urgentGuidance['action_en'],
                    ];
                    $scores['triage_emergency'] = 3; // Lower priority, just for context
                }
            }
        }

        // Sort by score and take top N
        arsort($scores);
        $topKeys = array_slice(array_keys($scores), 0, $this->maxSnippets);
        
        $result = [];
        foreach ($topKeys as $key) {
            if (isset($snippets[$key])) {
                // Truncate content to max tokens
                $snippets[$key]['content'] = $this->truncateToTokens($snippets[$key]['content'], $this->maxTokensPerSnippet);
                $result[] = $snippets[$key];
            }
        }

        Log::debug('Knowledge retrieval completed', [
            'query_length' => strlen($query),
            'snippets_found' => count($result),
            'types' => array_column($result, 'type'),
        ]);

        return $result;
    }

    /**
     * Format snippets for injection into AI context.
     */
    public function formatForContext(array $snippets, string $locale = 'en'): string
    {
        if (empty($snippets)) {
            return '';
        }

        $header = $locale === 'ar' 
            ? "معلومات المستشفى ذات الصلة:\n" 
            : "Relevant Hospital Information:\n";

        $lines = [$header];
        foreach ($snippets as $snippet) {
            $lines[] = "- [{$snippet['title']}]: {$snippet['content']}";
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * Get medical disclaimers.
     */
    public function getDisclaimers(string $locale = 'en', string $type = 'general'): string
    {
        foreach ($this->kb['medical_disclaimers'] ?? [] as $disclaimer) {
            if ($disclaimer['type'] === $type) {
                return $locale === 'ar' ? $disclaimer['ar'] : $disclaimer['en'];
            }
        }
        return '';
    }

    // === Helper Methods ===

    protected function normalizeArabic(string $text): string
    {
        $text = preg_replace('/[\x{064B}-\x{0652}]/u', '', $text);
        $text = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $text);
        $text = str_replace('ى', 'ي', $text);
        $text = str_replace('ة', 'ه', $text);
        return $text;
    }

    protected function calculateRelevance(string $query, array $keywords, string $locale): int
    {
        $score = 0;
        foreach ($keywords as $keyword) {
            if (str_contains($query, mb_strtolower($keyword))) {
                $score += 2;
            }
        }
        return $score;
    }

    protected function calculateContentRelevance(string $query, string $content): int
    {
        $contentLower = mb_strtolower($this->normalizeArabic($content));
        $words = preg_split('/\s+/', $query);
        $score = 0;
        foreach ($words as $word) {
            if (strlen($word) > 2 && str_contains($contentLower, $word)) {
                $score++;
            }
        }
        return $score;
    }

    protected function containsSymptomKeywords(string $query): bool
    {
        $keywords = ['pain', 'ache', 'fever', 'sick', 'hurt', 'symptom', 'feel', 
                     'ألم', 'حمى', 'مريض', 'أعراض', 'صداع', 'معدة', 'قلب', 'عين'];
        foreach ($keywords as $kw) {
            if (str_contains($query, $kw)) return true;
        }
        return false;
    }

    protected function containsBookingKeywords(string $query): bool
    {
        $keywords = ['book', 'appointment', 'schedule', 'cancel', 'reschedule', 'arrive',
                     'حجز', 'موعد', 'جدول', 'إلغاء', 'الحضور'];
        foreach ($keywords as $kw) {
            if (str_contains($query, $kw)) return true;
        }
        return false;
    }

    protected function containsPaymentKeywords(string $query): bool
    {
        $keywords = ['pay', 'payment', 'cost', 'fee', 'price', 'insurance', 'card',
                     'دفع', 'سعر', 'تكلفة', 'رسوم', 'تأمين', 'بطاقة'];
        foreach ($keywords as $kw) {
            if (str_contains($query, $kw)) return true;
        }
        return false;
    }

    protected function containsTimeKeywords(string $query): bool
    {
        $keywords = ['open', 'hours', 'time', 'when', 'available', 'close',
                     'مفتوح', 'ساعات', 'وقت', 'متى', 'متاح', 'مغلق'];
        foreach ($keywords as $kw) {
            if (str_contains($query, $kw)) return true;
        }
        return false;
    }

    protected function truncateToTokens(string $text, int $maxTokens): string
    {
        // Rough estimate: 1 token ≈ 4 characters
        $maxChars = $maxTokens * 4;
        if (strlen($text) <= $maxChars) {
            return $text;
        }
        return Str::limit($text, $maxChars, '...');
    }
}
