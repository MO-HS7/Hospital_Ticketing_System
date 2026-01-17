<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Intent Classifier for Intelligent Chatbot
 * 
 * Classifies user messages into actionable intents:
 * - booking_intent: User wants to create an appointment
 * - medical_question: Health/symptom question (answer via AI)
 * - hospital_faq: Hospital services, hours, policies
 * - emergency: Red-flag symptoms requiring immediate attention
 * - greeting: Hello, hi, مرحبا
 * - noise: Single characters, dots, meaningless input
 * - confirmation: Yes, confirm, نعم
 * - rejection: No, cancel, لا
 * - navigation: Back, previous, رجوع
 * - off_topic: Unrelated to health/hospital
 */
class ChatbotIntentClassifier
{
    // Emergency red-flag patterns (require immediate escalation)
    const EMERGENCY_PATTERNS = [
        'en' => [
            'chest pain' => ['with', 'and', 'breathe', 'breathing', 'shortness', 'arm', 'jaw'],
            'can\'t breathe' => [],
            'cannot breathe' => [],
            'difficulty breathing' => [],
            'severe bleeding' => [],
            'unconscious' => [],
            'loss of consciousness' => [],
            'heart attack' => [],
            'stroke' => [],
            'paralysis' => [],
            'sudden weakness' => [],
            'slurred speech' => [],
            'severe allergic' => [],
            'anaphylaxis' => [],
            'suicidal' => [],
            'overdose' => [],
        ],
        'ar' => [
            'ألم صدر' => ['تنفس', 'صعوبه', 'ذراع'],
            'لا أستطيع التنفس' => [],
            'صعوبة التنفس' => [],
            'نزيف شديد' => [],
            'إغماء' => [],
            'فقدان الوعي' => [],
            'نوبة قلبية' => [],
            'جلطة' => [],
            'سكتة' => [],
            'شلل' => [],
            'ضعف مفاجئ' => [],
            'حساسية شديدة' => [],
            'انتحار' => [],
        ],
    ];

    // Greeting patterns
    const GREETINGS = [
        'en' => ['hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening', 'greetings', 'howdy'],
        'ar' => ['مرحبا', 'اهلا', 'السلام عليكم', 'صباح الخير', 'مساء الخير', 'هلا', 'اهلين'],
    ];

    // Confirmation patterns
    const CONFIRMATIONS = [
        'en' => ['yes', 'confirm', 'ok', 'okay', 'sure', 'yep', 'yeah', 'absolutely', 'proceed', 'go ahead', 'correct', 'right'],
        'ar' => ['نعم', 'تأكيد', 'موافق', 'اوكي', 'صحيح', 'تمام', 'ايوه', 'اكيد', 'نعم أريد'],
    ];
    
    // Cancellation patterns (for stopping booking flow)
    const CANCELLATIONS = [
        'en' => ['cancel', 'stop', 'nevermind', 'never mind', 'go back', 'cancel booking', 'stop booking', 'quit', 'exit', 'no thanks', 'not now'],
        'ar' => ['الغاء', 'إلغاء', 'الغي', 'توقف', 'لا أريد', 'لا اريد', 'رجوع', 'انهاء', 'الغى', 'لا شكرا', 'مش عايز', 'الغي الحجز', 'وقف'],
    ];

    // Rejection/Cancel patterns
    const REJECTIONS = [
        'en' => ['no', 'cancel', 'stop', 'nevermind', 'forget it', 'don\'t', 'nope', 'nah'],
        'ar' => ['لا', 'إلغاء', 'الغاء', 'توقف', 'انسى', 'مش عايز', 'لا اريد'],
    ];

    // Navigation patterns
    const NAVIGATION = [
        'back' => ['en' => ['back', 'previous', 'go back', 'return'], 'ar' => ['رجوع', 'السابق', 'ارجع', 'العوده']],
        'cancel' => ['en' => ['cancel', 'quit', 'exit', 'start over'], 'ar' => ['الغاء', 'إلغاء', 'خروج', 'ابدا من جديد']],
    ];

    // Booking intent signals
    const BOOKING_SIGNALS = [
        'en' => ['book', 'appointment', 'schedule', 'reserve', 'see a doctor', 'visit', 'ticket', 'want to book', 'need an appointment', 'make appointment'],
        'ar' => ['حجز', 'موعد', 'احجز', 'اريد حجز', 'زياره طبيب', 'تذكره', 'اريد موعد'],
    ];

    // Identity signals (who are you, what can you do)
    // CRITICAL: Arabic patterns MUST use normalized forms (ا not أ, ه not ة, ي not ى)
    // because normalizeMessage converts أ→ا, ة→ه, ى→ي before matching
    const IDENTITY_SIGNALS = [
        'en' => ['who are you', 'what are you', 'what can you do', 'your name', 'introduce yourself', 'what is your name', 'tell me about yourself', 'are you a bot', 'are you ai', 'are you human', 'who is this', 'who am i talking to'],
        'ar' => ['من انت', 'مين انت', 'من انتي', 'انت مين', 'ما هو اسمك', 'ايش اسمك', 'شو اسمك', 'عرفني بنفسك', 'عرفني عن نفسك', 'ماذا تفعل', 'ما الذي يمكنك فعله', 'هل انت روبوت', 'هل انت ذكاء اصطناعي', 'انت بوت', 'انت ذكاء', 'من هذا'],
    ];

    // Medical news signals
    // CRITICAL: Arabic patterns MUST use normalized forms
    const MEDICAL_NEWS_SIGNALS = [
        'en' => ['medical news', 'health news', 'latest health', 'recent medical', 'new treatment', 'new study', 'new research', 'medical updates', 'health updates', 'what\'s new in medicine', 'latest in healthcare', 'healthcare news'],
        'ar' => ['اخبار طبيه', 'اخبار صحيه', 'اخر الاخبار', 'اخر الاخبار الطبيه', 'ما هي اخر الاخبار', 'ماهي اخر الاخبار', 'احدث الابحاث', 'علاج جديد', 'دراسه جديده', 'تحديثات صحيه', 'ما الجديد في الطب', 'جديد الطب', 'اخبار الصحه'],
    ];

    // Hospital FAQ signals
    const HOSPITAL_FAQ_SIGNALS = [
        'en' => ['hours', 'open', 'close', 'location', 'address', 'parking', 'insurance', 'payment', 'cost', 'price', 'fee', 'accept', 'policy', 'cancel my appointment', 'contact', 'phone', 'email', 'where is', 'how to get', 'which department'],
        'ar' => ['ساعات', 'مواعيد', 'العمل', 'موقع', 'عنوان', 'موقف', 'تامين', 'دفع', 'سعر', 'تكلفه', 'رسوم', 'سياسه', 'الغاء موعد', 'اتصال', 'هاتف', 'اين', 'كيف اصل', 'اي قسم'],
    ];

    // General medical advice signals (NOT symptoms - just asking for tips/advice)
    // These should return helpful info WITHOUT forcing symptom collection or booking
    // CRITICAL: Arabic patterns MUST use normalized forms
    const GENERAL_ADVICE_SIGNALS = [
        'en' => [
            'tips', 'tip', 'advice', 'advise', 'how to', 'how can i', 'how do i', 'what should i',
            'help me', 'ways to', 'best way', 'improve', 'better', 'healthy', 'healthier',
            'sleep better', 'sleep tips', 'sleeping tips', 'insomnia tips',
            'diet tips', 'eating tips', 'nutrition tips', 'weight loss', 'lose weight',
            'exercise tips', 'workout tips', 'fitness tips',
            'stress tips', 'reduce stress', 'manage stress', 'relaxation',
            'prevent', 'prevention', 'avoid', 'reduce',
            'good for', 'bad for', 'should i eat', 'should i drink',
        ],
        'ar' => [
            'نصائح', 'نصيحه', 'كيف', 'ماذا يجب', 'ما هي افضل', 'ما افضل',
            'ساعدني', 'طرق', 'افضل طريقه', 'تحسين', 'افضل', 'صحي',
            'نصائح النوم', 'تحسين النوم', 'انام بشكل افضل', 'اريد نصائح',
            'نصائح الغذاء', 'نصائح الاكل', 'تخسيس', 'انقاص الوزن',
            'نصائح الرياضه', 'تمارين',
            'تخفيف التوتر', 'اداره التوتر', 'استرخاء',
            'الوقايه', 'تجنب', 'تقليل', 'ارشادات',
        ],
    ];

    // Symptom/Pain indicators (actual symptoms requiring triage)
    // Different from general advice - these describe current health issues
    const SYMPTOM_INDICATORS = [
        'en' => ['pain', 'ache', 'hurt', 'hurts', 'hurting', 'fever', 'cough', 'coughing', 'cold', 'nausea', 'vomit', 'vomiting', 'dizzy', 'dizziness', 'headache', 'fatigue', 'swelling', 'swollen', 'rash', 'bleeding', 'burn', 'burning', 'itch', 'itching', 'infection', 'infected', 'symptom', 'symptoms', 'feel sick', 'feeling sick', 'not feeling well', 'unwell', 'problem with my', 'issue with my', 'suffering from', 'having trouble with', 'since yesterday', 'for days', 'for weeks', 'getting worse', 'won\'t go away'],
        'ar' => ['ألم', 'وجع', 'يؤلمني', 'حرارة', 'حمى', 'سعال', 'أسعل', 'كحة', 'غثيان', 'استفراغ', 'دوخة', 'صداع', 'تورم', 'منتفخ', 'طفح', 'حكة', 'نزيف', 'حرق', 'عدوى', 'ملتهب', 'أعراض', 'مريض', 'أشعر بالمرض', 'اعاني من', 'عندي مشكلة في', 'منذ أمس', 'منذ أيام', 'يزداد سوءاً'],
    ];

    /**
     * Classify a user message into an intent.
     */
    public function classify(string $message, string $locale = 'en'): array
    {
        $normalized = $this->normalizeMessage($message, $locale);
        $isArabic = $locale === 'ar';
        
        // === TRACE LOG: Classify input ===
        Log::debug('[Intent Classifier] Classifying message', [
            'original' => mb_substr($message, 0, 100),
            'normalized' => mb_substr($normalized, 0, 100),
            'locale' => $locale,
            'identity_patterns' => self::IDENTITY_SIGNALS[$locale] ?? [],
            'advice_patterns' => array_slice(self::GENERAL_ADVICE_SIGNALS[$locale] ?? [], 0, 5),
        ]);
        
        // 1. Check for noise (must be first)
        if ($this->isNoise($normalized)) {
            return $this->buildResult('noise', 0.95, [
                'reason' => 'Input too short or meaningless',
                'is_actionable' => false,
            ]);
        }
        
        // 2. Check for emergency (highest priority)
        $emergency = $this->detectEmergency($normalized, $locale);
        if ($emergency['is_emergency']) {
            return $this->buildResult('emergency', 0.99, [
                'reason' => $emergency['reason'],
                'matched_patterns' => $emergency['matched'],
                'is_actionable' => true,
                'requires_escalation' => true,
            ]);
        }
        
        // 3. Check for navigation commands
        if ($this->matchesPatterns($normalized, self::NAVIGATION['back'][$locale] ?? self::NAVIGATION['back']['en'])) {
            return $this->buildResult('navigation_back', 0.95, ['is_actionable' => true]);
        }
        // Use CANCELLATIONS patterns for cancel intent (more comprehensive)
        $cancelPatterns = array_merge(
            self::CANCELLATIONS['en'] ?? [],
            self::CANCELLATIONS['ar'] ?? [],
            self::NAVIGATION['cancel'][$locale] ?? [],
            self::NAVIGATION['cancel']['en'] ?? []
        );
        if ($this->matchesPatterns($normalized, $cancelPatterns)) {
            return $this->buildResult('cancel', 0.95, ['is_actionable' => true, 'reset_booking' => true]);
        }
        
        // 4. Check for confirmation/rejection (needed for booking flow)
        if ($this->matchesPatterns($normalized, self::CONFIRMATIONS[$locale] ?? self::CONFIRMATIONS['en'])) {
            return $this->buildResult('confirmation', 0.90, ['is_actionable' => true, 'value' => true]);
        }
        if ($this->matchesPatterns($normalized, self::REJECTIONS[$locale] ?? self::REJECTIONS['en'])) {
            return $this->buildResult('rejection', 0.90, ['is_actionable' => true, 'value' => false]);
        }
        
        // 5. Check for greeting (pure greeting only, short messages)
        if ($this->isPureGreeting($normalized, $locale)) {
            Log::debug('[Intent Classifier] Matched: GREETING');
            return $this->buildResult('greeting', 0.90, ['is_actionable' => false]);
        }
        
        // 6. Check for identity questions (who are you, what can you do)
        // Check BOTH locales for bilingual support
        $identityPatterns = array_merge(
            self::IDENTITY_SIGNALS['ar'] ?? [],
            self::IDENTITY_SIGNALS['en'] ?? []
        );
        if ($this->matchesPatterns($normalized, $identityPatterns)) {
            Log::info('[Intent Classifier] Matched: IDENTITY', ['normalized' => $normalized]);
            return $this->buildResult('identity', 0.90, [
                'is_actionable' => true,
                'needs_retrieval' => false, // Identity doesn't need web search
            ]);
        }
        
        // 7. Check for medical news requests (check BOTH locales)
        $newsPatterns = array_merge(
            self::MEDICAL_NEWS_SIGNALS['ar'] ?? [],
            self::MEDICAL_NEWS_SIGNALS['en'] ?? []
        );
        if ($this->matchesPatterns($normalized, $newsPatterns)) {
            Log::info('[Intent Classifier] Matched: MEDICAL_NEWS', ['normalized' => $normalized]);
            return $this->buildResult('medical_news', 0.90, [
                'is_actionable' => true,
                'needs_retrieval' => true, // Medical news REQUIRES web search
            ]);
        }
        
        // 8. Check for explicit booking intent (check BOTH locales)
        $bookingPatterns = array_merge(
            self::BOOKING_SIGNALS['ar'] ?? [],
            self::BOOKING_SIGNALS['en'] ?? []
        );
        if ($this->matchesPatterns($normalized, $bookingPatterns)) {
            Log::info('[Intent Classifier] Matched: BOOKING_INTENT');
            return $this->buildResult('booking_intent', 0.85, ['is_actionable' => true]);
        }
        
        // 9. Check for hospital FAQ (check BOTH locales)
        $faqPatterns = array_merge(
            self::HOSPITAL_FAQ_SIGNALS['ar'] ?? [],
            self::HOSPITAL_FAQ_SIGNALS['en'] ?? []
        );
        if ($this->matchesPatterns($normalized, $faqPatterns)) {
            Log::info('[Intent Classifier] Matched: HOSPITAL_FAQ');
            return $this->buildResult('hospital_faq', 0.80, ['is_actionable' => true]);
        }
        
        // 10. Check for GENERAL ADVICE requests (tips, how-to) - BEFORE symptoms
        // This fixes the bug where "sleep tips" triggered symptom collection
        $advicePatterns = array_merge(
            self::GENERAL_ADVICE_SIGNALS['ar'] ?? [],
            self::GENERAL_ADVICE_SIGNALS['en'] ?? []
        );
        if ($this->matchesPatterns($normalized, $advicePatterns)) {
            Log::info('[Intent Classifier] Matched: MEDICAL_QUESTION (advice)', ['normalized' => $normalized]);
            return $this->buildResult('medical_question', 0.85, [
                'is_actionable' => true,
                'may_need_booking' => false, // General advice: do NOT force booking
                'is_advice_request' => true,
            ]);
        }
        
        // 11. Check for actual SYMPTOM REPORTS (user describing current health issues)
        $symptomPatterns = array_merge(
            self::SYMPTOM_INDICATORS['ar'] ?? [],
            self::SYMPTOM_INDICATORS['en'] ?? []
        );
        if ($this->matchesPatterns($normalized, $symptomPatterns)) {
            Log::info('[Intent Classifier] Matched: SYMPTOM_REPORT', ['normalized' => $normalized]);
            return $this->buildResult('symptom_report', 0.75, [
                'is_actionable' => true,
                'may_need_booking' => true, // Symptoms: offer booking
            ]);
        }
        
        // 9. Check if message is a likely question (ends with ?)
        if (str_ends_with(trim($message), '?') || str_ends_with(trim($message), '؟')) {
            return $this->buildResult('general_question', 0.60, ['is_actionable' => true]);
        }
        
        // 10. Default: treat longer messages as potential medical content, short as unclear
        if (mb_strlen($normalized) > 20) {
            return $this->buildResult('medical_question', 0.50, [
                'is_actionable' => true,
                'needs_clarification' => true,
            ]);
        }
        
        // 11. UPGRADE unclear to medical_question if contains medical topic keywords
        // This prevents common medical topics from being classified as unclear
        $medicalKeywords = [
            'ar' => ['نوم', 'أرق', 'نومي', 'قلق', 'توتر', 'تغذية', 'رجيم', 'حمية', 'رياضة', 'لياقة', 
                     'صحة', 'نصائح', 'ارشادات', 'معلومات', 'طبية', 'طبي', 'علاج', 'دواء'],
            'en' => ['sleep', 'insomnia', 'stress', 'anxiety', 'nutrition', 'diet', 'exercise', 'fitness',
                     'health', 'tips', 'advice', 'information', 'medical', 'treatment', 'medicine']
        ];
        
        $keywords = array_merge($medicalKeywords['ar'], $medicalKeywords['en']);
        foreach ($keywords as $keyword) {
            if (str_contains($normalized, $keyword)) {
                Log::info('[Intent Classifier] Upgraded unclear to medical_question', ['keyword' => $keyword]);
                return $this->buildResult('medical_question', 0.65, [
                    'is_actionable' => true,
                    'upgraded_from' => 'unclear',
                    'matched_keyword' => $keyword,
                ]);
            }
        }
        
        return $this->buildResult('unclear', 0.30, [
            'is_actionable' => false,
            'needs_clarification' => true,
        ]);
    }

    /**
     * Detect emergency based on red-flag patterns.
     */
    protected function detectEmergency(string $message, string $locale): array
    {
        $patterns = self::EMERGENCY_PATTERNS[$locale] ?? self::EMERGENCY_PATTERNS['en'];
        $matched = [];
        
        foreach ($patterns as $keyword => $modifiers) {
            if (str_contains($message, $keyword)) {
                // If no modifiers required, it's a direct match
                if (empty($modifiers)) {
                    $matched[] = $keyword;
                    continue;
                }
                
                // Check if any modifier is also present (e.g., "chest pain" + "breathing")
                foreach ($modifiers as $mod) {
                    if (str_contains($message, $mod)) {
                        $matched[] = "$keyword + $mod";
                        break;
                    }
                }
            }
        }
        
        // Also check the other language for bilingual input
        $otherLocale = $locale === 'ar' ? 'en' : 'ar';
        $otherPatterns = self::EMERGENCY_PATTERNS[$otherLocale];
        foreach ($otherPatterns as $keyword => $modifiers) {
            if (str_contains($message, $keyword)) {
                if (empty($modifiers)) {
                    $matched[] = $keyword;
                }
            }
        }
        
        return [
            'is_emergency' => !empty($matched),
            'reason' => !empty($matched) ? 'Detected emergency symptoms: ' . implode(', ', array_unique($matched)) : '',
            'matched' => array_unique($matched),
        ];
    }

    /**
     * Check if input is noise (single chars, dots, random letters).
     */
    protected function isNoise(string $message): bool
    {
        $msg = trim($message);
        
        // Single character (except numbers that might be selections)
        if (mb_strlen($msg) === 1 && !is_numeric($msg)) {
            return true;
        }
        
        // Only punctuation or whitespace
        if (preg_match('/^[\s\.\,\!\?\;\:\-\_\*\#\@\&\%\$\(\)\[\]\{\}]+$/u', $msg)) {
            return true;
        }
        
        // Very short non-words (2-3 random chars that aren't common words)
        if (mb_strlen($msg) <= 3) {
            $commonShortWords = ['yes', 'no', 'ok', 'hi', 'bye', 'نعم', 'لا', 'اهلا', 'مرحبا'];
            if (!in_array(mb_strtolower($msg), $commonShortWords)) {
                // Check if it's just random consonants
                if (preg_match('/^[^aeiouأإآاوي\s]+$/iu', $msg)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Check if message is a pure greeting (only greeting, no other content).
     */
    protected function isPureGreeting(string $message, string $locale): bool
    {
        $greetings = array_merge(
            self::GREETINGS['en'],
            self::GREETINGS['ar']
        );
        
        // Only consider it a pure greeting if the entire message is just a greeting
        foreach ($greetings as $greeting) {
            if ($message === $greeting || preg_match('/^' . preg_quote($greeting, '/') . '[\.\!\s]*$/iu', $message)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if message contains any of the patterns.
     */
    protected function matchesPatterns(string $message, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (str_contains($message, $pattern)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Normalize message for matching.
     */
    protected function normalizeMessage(string $message, string $locale = 'en'): string
    {
        $msg = mb_strtolower(trim($message));
        
        // Remove Arabic diacritics
        $msg = preg_replace('/[\x{064B}-\x{0652}]/u', '', $msg);
        
        // Normalize Arabic letters
        $msg = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $msg);
        $msg = str_replace('ى', 'ي', $msg);
        $msg = str_replace('ة', 'ه', $msg);
        $msg = str_replace('ـ', '', $msg);
        
        return $msg;
    }

    /**
     * Build a structured result.
     */
    protected function buildResult(string $intent, float $confidence, array $metadata = []): array
    {
        return array_merge([
            'intent' => $intent,
            'confidence' => $confidence,
            'is_actionable' => true,
        ], $metadata);
    }

    /**
     * Check if input looks like a valid ID selection (number or known format).
     */
    public function looksLikeIdSelection(string $message): bool
    {
        $msg = trim($message);
        
        // Pure number
        if (ctype_digit($msg)) {
            return true;
        }
        
        // Number at start/end
        if (preg_match('/^\d+\b|\b\d+$/', $msg)) {
            return true;
        }
        
        return false;
    }

    /**
     * Extract numeric ID from message if present.
     */
    public function extractNumericId(string $message): ?int
    {
        if (preg_match('/\b(\d+)\b/', $message, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }
}
