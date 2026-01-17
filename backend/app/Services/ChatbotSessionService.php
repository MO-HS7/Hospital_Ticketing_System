<?php

namespace App\Services;

use App\Models\ChatbotSession;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Stateful Chatbot Session Service with Gemini AI
 */
class ChatbotSessionService
{
    const SESSION_TTL_HOURS = 24;
    const CONNECT_TIMEOUT = 2;
    const REQUEST_TIMEOUT = 10;
    const CACHE_TTL = 600;

    protected bool $aiEnabled;
    protected ?string $geminiApiKey;
    protected string $geminiModel;
    protected string $geminiEndpoint;
    protected HospitalKnowledgeService $knowledgeService;
    protected ChatbotMetricsService $metricsService;
    protected ChatbotIntentClassifier $intentClassifier;
    protected ChatbotAIService $aiService;

    // Conversation steps - STRICT 5-STEP BOOKING FLOW
    const STEP_INITIAL = 'initial';
    const STEP_SYMPTOMS = 'symptoms';
    const STEP_DEPARTMENT = 'department';      // Step 1: Department Selection
    const STEP_DOCTOR = 'doctor';              // Step 2: Doctor Selection
    const STEP_SLOT = 'slot';                  // Step 3: Slot Selection (NEW)
    const STEP_PATIENT_INFO = 'patient_info';  // Step 4: Patient Information
    const STEP_CONFIRM = 'confirm';            // Step 5: Summary & Confirmation
    const STEP_COMPLETE = 'complete';
    
    // Conversation mode: Q&A (default) vs Booking (structured flow)
    const MODE_QA = 'qa';           // Answer questions, offer booking
    const MODE_BOOKING = 'booking'; // Structured 5-step flow
    
    // Emergency red-flag symptoms
    const EMERGENCY_KEYWORDS_EN = ['chest pain', 'can\'t breathe', 'unconscious', 'heavy bleeding', 'stroke', 'heart attack', 'severe pain', 'can not breathe', 'difficulty breathing'];
    const EMERGENCY_KEYWORDS_AR = ['ألم صدر', 'لا أستطيع التنفس', 'إغماء', 'نزيف شديد', 'جلطة', 'نوبة قلبية', 'ألم شديد', 'صعوبة التنفس', 'ضيق تنفس'];

    public function __construct(
        ?HospitalKnowledgeService $knowledgeService = null,
        ?ChatbotMetricsService $metricsService = null,
        ?ChatbotIntentClassifier $intentClassifier = null,
        ?ChatbotAIService $aiService = null
    ) {
        $this->aiEnabled = (bool) config('services.chatbot.ai_enabled', false);
        $this->geminiApiKey = config('services.gemini.api_key');
        $this->geminiModel = config('services.gemini.model', 'gemini-2.0-flash');
        $this->geminiEndpoint = config('services.gemini.endpoint', 'https://generativelanguage.googleapis.com/v1beta/models');
        $this->knowledgeService = $knowledgeService ?? new HospitalKnowledgeService();
        $this->metricsService = $metricsService ?? new ChatbotMetricsService();
        $this->aiService = $aiService ?? new ChatbotAIService();
        $this->intentClassifier = $intentClassifier ?? new ChatbotIntentClassifier();
        
        // Debug log configuration on construction (no secrets)
        Log::debug('ChatbotSessionService initialized', [
            'ai_enabled' => $this->aiEnabled,
            'api_key_configured' => !empty($this->geminiApiKey),
            'model' => $this->geminiModel,
        ]);
    }

    /**
     * Normalize Arabic text for better matching.
     * Removes diacritics, normalizes alef forms, etc.
     */
    protected function normalizeArabic(string $text): string
    {
        // Remove Arabic diacritics (tashkeel)
        $text = preg_replace('/[\x{064B}-\x{0652}]/u', '', $text);
        
        // Normalize Alef forms (أ إ آ → ا)
        $text = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $text);
        
        // Normalize Ya (ى → ي)
        $text = str_replace('ى', 'ي', $text);
        
        // Normalize Ta Marbuta (ة → ه) - optional, helps matching
        $text = str_replace('ة', 'ه', $text);
        
        // Remove Tatweel (ـ)
        $text = str_replace('ـ', '', $text);
        
        return $text;
    }
    
    /**
     * Check if the session is currently in booking mode.
     * CRITICAL: Used to gate AI calls and disclaimer suppression.
     */
    protected function isBookingMode(ChatbotSession $session): bool
    {
        return $session->getState('mode') === self::MODE_BOOKING;
    }
    
    /**
     * Detect emergency keywords in user message.
     * Returns true if any emergency keyword is found.
     */
    protected function detectEmergency(string $message): bool
    {
        $normalized = $this->normalizeArabic(mb_strtolower($message));
        
        // Check English keywords
        foreach (self::EMERGENCY_KEYWORDS_EN as $keyword) {
            if (str_contains(mb_strtolower($message), mb_strtolower($keyword))) {
                return true;
            }
        }
        
        // Check Arabic keywords
        foreach (self::EMERGENCY_KEYWORDS_AR as $keyword) {
            if (str_contains($normalized, $this->normalizeArabic($keyword))) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Strip medical disclaimers AND markdown from booking mode responses.
     * CRITICAL: Booking messages must be PLAIN TEXT - no markdown, no disclaimers.
     */
    protected function stripDisclaimers(string $text): string
    {
        // =====================================================================
        // 1. STRIP MARKDOWN TOKENS
        // =====================================================================
        // Headers
        $text = preg_replace('/^#{1,6}\s*/m', '', $text);
        // Bold
        $text = preg_replace('/\*\*([^*]+)\*\*/', '$1', $text);
        // Italic
        $text = preg_replace('/\*([^*]+)\*/', '$1', $text);
        // Backticks/code
        $text = preg_replace('/`([^`]+)`/', '$1', $text);
        // Links [text](url)
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text);
        
        // =====================================================================
        // 2. STRIP DISCLAIMERS (Arabic + English)
        // =====================================================================
        $patterns = [
            // Arabic disclaimers
            '/⚠️.*$/um',
            '/إذا كنت ترغب.*?حجز موعد.*?$/um',
            '/يمكنني مساعدتك في حجز.*?$/um',
            '/استشر طبيب.*?$/um',
            '/هذه معلومات عامة.*?$/um',
            // English disclaimers  
            '/⚠️.*$/im',
            '/If you\'d like.*?book.*?$/im',
            '/I can help you book.*?$/im',
            '/consult a doctor.*?$/im',
            '/general health information.*?$/im',
            // Sources/References
            '/Sources?:.*$/im',
            '/مصادر:.*$/um',
            '/\[\d+\].*$/m',
        ];
        
        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, '', $text);
        }
        
        // =====================================================================
        // 3. CLEANUP
        // =====================================================================
        // Remove extra whitespace
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = preg_replace('/\s+$/', '', $text);
        
        return trim($text);
    }
    
    /**
     * Log BLOCKED AI attempt (should never happen).
     */
    protected function logBlockedAI(string $context, ChatbotSession $session): void
    {
        Log::error('[BLOCKED_AI] AI call attempted in booking mode!', [
            'session_id' => $session->id,
            'context' => $context,
            'mode' => $session->getState('mode'),
            'step' => $session->getState('step'),
            'this_should_never_happen' => true,
        ]);
    }

    /**
     * Get or create a session for the user.
     */
    public function getSession(?string $sessionId, ?int $userId, string $locale = 'en'): ChatbotSession
    {
        if ($sessionId) {
            $session = ChatbotSession::where('id', $sessionId)
                ->where('expires_at', '>', now())
                ->first();
            
            if ($session) {
                if ($session->locale !== $locale) {
                    $session->update(['locale' => $locale]);
                }
                return $session;
            }
        }

        return ChatbotSession::create([
            'user_id' => $userId,
            'locale' => $locale,
            'state' => ['step' => self::STEP_INITIAL],
            'messages' => [],
            'expires_at' => now()->addHours(self::SESSION_TTL_HOURS),
        ]);
    }

    /**
     * Get welcome message for new sessions with fully normalized schema.
     * 
     * Returns:
     * - messages[] array
     * - intent: 'greeting'
     * - handler: 'getWelcomeMessage'
     * - provider: 'local'
     * - mode: 'qa'
     * - step: 'initial'
     * - ui: {type: 'topics', items: [topic actions]}
     */
    public function getWelcomeMessage(ChatbotSession $session): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        
        $greeting = $isArabic
            ? "أهلاً بك! 👋 أنا **مسار**، مساعدك الطبي الذكي.\n\nيمكنني مساعدتك في:\n• الإجابة على أسئلتك الصحية\n• حجز موعد مع طبيب\n• معلومات عن أقسام المستشفى\n\nاختر موضوعاً أو اكتب سؤالك:"
            : "Hello! 👋 I'm **Masar**, your intelligent medical assistant.\n\nI can help you with:\n• Answering health questions\n• Booking an appointment\n• Hospital information\n\nChoose a topic or type your question:";
        
        // Topic action chips for quick access
        $topicItems = $isArabic
            ? [
                ['action' => 'topic_sleep', 'label' => 'النوم والراحة', 'icon' => '😴'],
                ['action' => 'topic_nutrition', 'label' => 'التغذية', 'icon' => '🥗'],
                ['action' => 'topic_stress', 'label' => 'التوتر', 'icon' => '🧘'],
                ['action' => 'start_booking', 'label' => 'حجز موعد', 'icon' => '📅'],
            ]
            : [
                ['action' => 'topic_sleep', 'label' => 'Sleep & Rest', 'icon' => '😴'],
                ['action' => 'topic_nutrition', 'label' => 'Nutrition', 'icon' => '🥗'],
                ['action' => 'topic_stress', 'label' => 'Stress', 'icon' => '🧘'],
                ['action' => 'start_booking', 'label' => 'Book Appointment', 'icon' => '📅'],
            ];
        
        return [
            'messages' => [$greeting],
            'intent' => 'greeting',
            'handler' => 'getWelcomeMessage',
            'provider' => 'local',
            'mode' => self::MODE_QA,
            'step' => self::STEP_INITIAL,
            'current_step' => self::STEP_INITIAL,
            'ui' => [
                'type' => 'topics',
                'items' => $topicItems,
            ],
        ];
    }

    /**
     * Process an action-based quick reply (bypasses text classification).
     * 
     * Actions:
     * - topic_sleep, topic_nutrition, topic_stress -> medical_question (You Agent)
     * - start_booking -> handleBookingIntent
     * - cancel_booking -> handleCancel
     */
    public function processAction(ChatbotSession $session, string $action, string $locale): array
    {
        $startTime = microtime(true);
        $isArabic = $locale === 'ar';
        $step = $session->getState('step', self::STEP_INITIAL);
        $mode = $session->getState('mode', self::MODE_QA);
        $fromStep = $step;
        
        Log::info('[Router] Action received', [
            'session_id' => $session->id,
            'action' => $action,
            'mode' => $mode,
            'step' => $step,
            'input_type' => 'action',
            'locale' => $locale,
        ]);
        
        // =====================================================================
        // HARD GATE: BOOKING MODE - Handle booking-specific actions ONLY
        // =====================================================================
        if ($mode === self::MODE_BOOKING) {
            Log::info('[Router] BOOKING MODE action - bypassing AI', [
                'session_id' => $session->id,
                'action' => $action,
                'handler' => 'handleBookingAction',
                'provider' => 'database',
            ]);
            
            $response = $this->handleBookingAction($session, $action, $locale);
            return $this->finalizeResponse($session, $response, $fromStep, $startTime);
        }
        
        // =====================================================================
        // Q&A MODE ACTIONS BELOW
        // =====================================================================
        
        // Topic actions -> Route to You Agent for medical advice (QA mode only)
        if (str_starts_with($action, 'topic_')) {
            // CRITICAL: Force session to QA mode BEFORE processing
            $session->setState('mode', self::MODE_QA);
            $session->setState('step', self::STEP_INITIAL);
            
            $topicQuery = $this->actionToQuery($action, $isArabic);
            $session->addMessage('user', $topicQuery);
            
            Log::info('[processAction] Routing topic to You Agent', [
                'action' => $action,
                'query' => $topicQuery,
            ]);
            
            // Use aiService for You Agent routing
            $aiResult = $this->aiService->analyzeSymptoms($topicQuery, $locale, [
                'session_id' => $session->id,
                'mode' => 'qa',
                'history' => $session->getRecentMessages(5),
            ], 'medical_question');
            
            $reply = $aiResult['reply'] ?? '';
            $provider = $aiResult['provider'] ?? 'rules';
            
            Log::info('[processAction] AI result received', [
                'provider' => $provider,
                'has_reply' => !empty($reply),
            ]);
            
            // If AI failed, give generic advice
            if (empty($reply)) {
                $reply = $isArabic
                    ? "إليك بعض النصائح الصحية حول هذا الموضوع. للحصول على معلومات أكثر دقة، يرجى استشارة طبيب متخصص."
                    : "Here are some general health tips on this topic. For more specific information, please consult a specialist.";
                $provider = 'rules';
            }
            
            $response = [
                'messages' => [$reply],
                'quick_replies' => $isArabic 
                    ? ['احجز موعد', 'موضوع آخر']
                    : ['Book appointment', 'Another topic'],
                'step' => self::STEP_INITIAL,         // Explicit step
                'current_step' => self::STEP_INITIAL, // Backward compat
                'mode' => self::MODE_QA,              // Explicit mode
                'intent' => 'medical_question',       // Explicit intent
                'handler' => 'processAction',         // Explicit handler (Issue D fix)
                'provider' => $provider,              // Explicit provider
                'ui' => ['type' => 'none', 'items' => [], 'reset_booking' => false],
                'sources' => $aiResult['sources'] ?? [],
            ];
            
            $session->addMessage('assistant', $reply);
            $session->save();
            
            return $response;
        }
        
        // Start booking action
        if ($action === 'start_booking') {
            $response = $this->handleBookingIntent($session, $locale);
            return $this->finalizeResponse($session, $response, $fromStep, $startTime);
        }
        
        // Cancel booking action
        if ($action === 'cancel_booking') {
            $response = $this->handleCancel($session, $locale);
            return $this->finalizeResponse($session, $response, $fromStep, $startTime);
        }
        
        // Unknown action - treat as greeting
        $response = $this->handleGreeting($session, $locale);
        return $this->finalizeResponse($session, $response, $fromStep, $startTime);
    }
    
    /**
     * Convert action to natural language query for AI.
     */
    protected function actionToQuery(string $action, bool $isArabic): string
    {
        $queries = [
            'topic_sleep' => $isArabic ? 'أريد نصائح للنوم والراحة' : 'I want tips for sleep and rest',
            'topic_nutrition' => $isArabic ? 'أريد نصائح عن التغذية والحمية' : 'I want nutrition and diet tips',
            'topic_stress' => $isArabic ? 'أريد نصائح للتعامل مع التوتر والقلق' : 'I want tips for managing stress and anxiety',
            'topic_heart' => $isArabic ? 'أريد معلومات عن صحة القلب' : 'I want information about heart health',
            'topic_diabetes' => $isArabic ? 'أريد معلومات عن مرض السكري' : 'I want information about diabetes',
        ];
        
        return $queries[$action] ?? ($isArabic ? 'أريد نصائح طبية' : 'I want medical advice');
    }

    /**
     * Process a user message and return bot response.
     * 
     * This uses INTENT-BASED ROUTING:
     * 1. Classify intent (noise, greeting, emergency, booking, question, etc.)
     * 2. Handle by intent, not just by step
     * 3. Only advance steps on VALIDATED input
     */
    public function processMessage(ChatbotSession $session, string $message): array
    {
        $startTime = microtime(true);
        $locale = $session->locale;
        $isArabic = $locale === 'ar';

        // Add user message to history
        $session->addMessage('user', $message);

        $step = $session->getState('step', self::STEP_INITIAL);
        $mode = $session->getState('mode', self::MODE_QA);
        $fromStep = $step;
        
        // DEBUG: Log full session state to diagnose mode persistence
        Log::info('[Router] processMessage entry', [
            'session_id' => $session->id,
            'mode_from_state' => $mode,
            'step_from_state' => $step,
            'full_state' => $session->state,
            'message_preview' => mb_substr($message, 0, 30),
        ]);
        
        // =====================================================================
        // HARD GATE: BOOKING MODE - NO AI, NO INTENT CLASSIFICATION
        // This MUST be the first check after getting mode/step
        // =====================================================================
        if ($mode === self::MODE_BOOKING) {
            Log::info('[Router] BOOKING MODE - bypassing all AI', [
                'session_id' => $session->id,
                'mode' => $mode,
                'step' => $step,
                'input_type' => 'message',
                'handler' => 'handleBookingStepInput',
                'provider' => 'database',
            ]);
            
            $response = $this->handleBookingStepInput($session, $message, $step);
            return $this->finalizeResponse($session, $response, $fromStep, $startTime);
        }
        
        // =====================================================================
        // Q&A MODE ONLY BELOW THIS POINT
        // =====================================================================
        
        // 1. CLASSIFY INTENT (only in QA mode)
        $intentResult = $this->intentClassifier->classify($message, $locale);
        $intent = $intentResult['intent'];
        
        Log::debug('[Router] QA mode intent classified', [
            'session_id' => $session->id,
            'mode' => $mode,
            'step' => $step,
            'intent' => $intent,
            'confidence' => $intentResult['confidence'] ?? 0,
            'input_type' => 'message',
            'handler' => 'intent_router',
            'provider' => 'rules',
        ]);
        
        // 2. HANDLE EMERGENCY (highest priority, any step)
        if ($intent === 'emergency') {
            $response = $this->handleEmergency($session, $message, $intentResult);
            return $this->finalizeResponse($session, $response, $fromStep, $startTime);
        }
        
        // 3. HANDLE NOISE (reject and stay in same step)
        if ($intent === 'noise') {
            $response = $this->handleNoiseInput($session, $locale);
            return $this->finalizeResponse($session, $response, $fromStep, $startTime);
        }
        
        // 4. HANDLE NAVIGATION (back/cancel) during booking flow
        if ($intent === 'navigation_back' && $this->isInBookingFlow($step)) {
            $response = $this->handleBackNavigation($session, $step);
            return $this->finalizeResponse($session, $response, $fromStep, $startTime);
        }
        if ($intent === 'navigation_cancel' && $this->isInBookingFlow($step)) {
            $response = $this->handleCancelFlow($session);
            return $this->finalizeResponse($session, $response, $fromStep, $startTime);
        }
        
        // =====================================================================
        // 5. HARD GATE: Q&A INTENTS (MUST happen BEFORE step/booking logic!)
        // These intents NEVER show symptom chips, ALWAYS use You Agent retrieval
        // =====================================================================
        
        // 5a. IDENTITY ("من انت", "who are you") - NO chips, NO triage
        if ($intent === 'identity') {
            Log::info('Intent routing: IDENTITY', ['session_id' => $session->id, 'message' => $message]);
            $response = $this->handleIdentity($session, $locale);
            $response['ui'] = ['type' => 'none', 'items' => []]; // Force no chips
            return $this->finalizeResponse($session, $response, $fromStep, $startTime);
        }
        
        // 5b. MEDICAL NEWS ("اخبار طبية", "medical news") - NO chips, requires retrieval
        if ($intent === 'medical_news') {
            Log::info('Intent routing: MEDICAL_NEWS', ['session_id' => $session->id, 'message' => $message]);
            $response = $this->handleMedicalNews($session, $message, $locale);
            $response['ui'] = ['type' => 'chips', 'items' => $this->buildTopicChips($locale)]; // Topic chips only
            return $this->finalizeResponse($session, $response, $fromStep, $startTime);
        }
        
        // 5c. MEDICAL QUESTION ("نصائح", "tips", "how to") - NO symptom chips, requires retrieval
        if ($intent === 'medical_question') {
            Log::info('Intent routing: MEDICAL_QUESTION', ['session_id' => $session->id, 'message' => $message]);
            $response = $this->handleMedicalQuestion($session, $message, $locale);
            $response['ui'] = ['type' => 'none', 'items' => []]; // Force no symptom chips
            return $this->finalizeResponse($session, $response, $fromStep, $startTime);
        }
        
        // 5d. GREETING - simple response
        if ($intent === 'greeting') {
            Log::info('Intent routing: GREETING', ['session_id' => $session->id]);
            $response = $this->handleGreeting($session, $locale);
            $response['ui'] = ['type' => 'none', 'items' => []];
            return $this->finalizeResponse($session, $response, $fromStep, $startTime);
        }
        
        // 5e. HOSPITAL FAQ - NO symptom chips
        if ($intent === 'hospital_faq') {
            Log::info('Intent routing: HOSPITAL_FAQ', ['session_id' => $session->id, 'message' => $message]);
            $response = $this->handleHospitalFAQ($session, $message, $locale);
            $response['ui'] = ['type' => 'none', 'items' => []];
            return $this->finalizeResponse($session, $response, $fromStep, $startTime);
        }
        
        // 5f. CANCEL - Reset booking flow, return to Q&A mode
        if ($intent === 'cancel' || $intent === 'navigation_cancel') {
            Log::info('Intent routing: CANCEL - Resetting booking state', ['session_id' => $session->id]);
            $response = $this->handleCancel($session, $locale);
            return $this->finalizeResponse($session, $response, $fromStep, $startTime);
        }
        
        // =====================================================================
        // 6. ROUTE BY MODE + STEP (only if NOT a Q&A intent)
        // =====================================================================
        try {
            if ($mode === self::MODE_BOOKING && $this->isInBookingFlow($step)) {
                // In structured booking flow: validate input strictly
                $response = $this->handleBookingFlowStep($session, $message, $step, $intent);
            } else {
                // Q&A fallback or symptom_report: may offer booking
                Log::info('Intent routing: FALLBACK to handleQAMode', ['session_id' => $session->id, 'intent' => $intent]);
                $response = $this->handleQAMode($session, $message, $intent, $intentResult);
            }
        } catch (\Throwable $e) {
            Log::error('Chatbot error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $response = $this->buildErrorResponse($session);
        }

        return $this->finalizeResponse($session, $response, $fromStep, $startTime);
    }
    
    /**
     * Finalize response: add to history, log transition, return with metadata.
     * CRITICAL: Always include unified `ui` field for frontend rendering control.
     * BOOKING MODE: All disclaimers are stripped from responses.
     */
    protected function finalizeResponse(ChatbotSession $session, array $response, string $fromStep, float $startTime): array
    {
        // CRITICAL: Strip disclaimers from ALL booking mode responses
        if ($this->isBookingMode($session)) {
            if (isset($response['messages']) && is_array($response['messages'])) {
                $response['messages'] = array_map(
                    fn($msg) => $this->stripDisclaimers($msg), 
                    $response['messages']
                );
            }
            // Force provider to database for booking responses
            $response['provider'] = $response['provider'] ?? 'database';
        }
        
        foreach ($response['messages'] ?? [] as $msg) {
            $session->addMessage('assistant', $msg);
        }

        // DEBUG: Log state before save
        Log::info('[finalizeResponse] Saving session state', [
            'session_id' => $session->id,
            'mode_to_save' => $session->getState('mode'),
            'step_to_save' => $session->getState('step'),
            'full_state' => $session->state,
        ]);

        $session->save();

        $toStep = $session->getState('step');
        $mode = $session->getState('mode', self::MODE_QA);
        $duration = round((microtime(true) - $startTime) * 1000);
        
        // Log step transition
        if ($fromStep !== $toStep) {
            Log::info('Chatbot step transition', [
                'session_id' => $session->id,
                'from_step' => $fromStep,
                'to_step' => $toStep,
                'reason' => $response['transition_reason'] ?? 'validated_input',
            ]);
        }
        
        // Build unified UI object
        $ui = $response['ui'] ?? $this->buildUIFromResponse($response, $mode);
        
        // Comprehensive logging
        Log::info('Chatbot message processed', [
            'session_id' => $session->id,
            'intent' => $response['intent'] ?? 'unknown',
            'mode' => $mode,
            'step' => $toStep,
            'handler' => $response['handler'] ?? 'unknown',
            'provider' => $response['provider'] ?? 'rules',
            'ui_type' => $ui['type'] ?? 'none',
            'duration_ms' => $duration,
        ]);

        return array_merge($response, [
            'session_id' => $session->id,
            'mode' => $mode,
            'ui' => $ui,
            '_timing_ms' => $duration,
            '_intent' => $response['intent'] ?? null,
        ]);
    }
    
    /**
     * Build UI object from response fields for backward compatibility.
     * This ensures old responses get proper ui.type.
     */
    protected function buildUIFromResponse(array $response, string $mode): array
    {
        // If explicitly set to none
        if (isset($response['ui']['type']) && $response['ui']['type'] === 'none') {
            return ['type' => 'none', 'items' => []];
        }
        
        // In booking mode, map to card types
        if ($mode === self::MODE_BOOKING) {
            if (!empty($response['suggestions'])) {
                return [
                    'type' => 'department_cards',
                    'items' => $response['suggestions'],
                ];
            }
            if (!empty($response['doctors'])) {
                return [
                    'type' => 'doctor_cards', 
                    'items' => $response['doctors'],
                ];
            }
            if (!empty($response['payment_options'])) {
                return [
                    'type' => 'payment_cards',
                    'items' => $response['payment_options'],
                ];
            }
        }
        
        // For Q&A mode, default to no cards
        if ($mode === self::MODE_QA) {
            // Only show quick_replies, not cards
            if (!empty($response['quick_replies'])) {
                return [
                    'type' => 'quick_replies',
                    'items' => $response['quick_replies'],
                ];
            }
            return ['type' => 'none', 'items' => []];
        }
        
        return ['type' => 'none', 'items' => []];
    }
    
    // =========================================================================
    // BOOKING MODE HANDLERS - 100% DB-DRIVEN, NO AI
    // These are called from the HARD GATE at processMessage/processAction
    // =========================================================================
    
    /**
     * Handle text input during booking mode.
     * Routes to appropriate step handler based on current step.
     * CRITICAL: NO AI CALLS - only database operations.
     */
    protected function handleBookingStepInput(ChatbotSession $session, string $message, string $step): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        
        Log::info('[BOOKING] handleBookingStepInput', [
            'session_id' => $session->id,
            'step' => $step,
            'message_preview' => mb_substr($message, 0, 50),
            'provider' => 'database',
        ]);
        
        // Check for cancel/back keywords
        $normalizedMsg = $this->normalizeArabic(mb_strtolower($message));
        if (preg_match('/\b(cancel|الغاء|إلغاء)\b/iu', $normalizedMsg)) {
            return $this->handleCancel($session, $locale);
        }
        if (preg_match('/\b(back|رجوع|عودة)\b/iu', $normalizedMsg)) {
            return $this->handleBackStep($session);
        }
        
        // Route to step-specific handler
        return match ($step) {
            self::STEP_DEPARTMENT => $this->handleDepartmentSelection($session, $message),
            self::STEP_DOCTOR => $this->handleDoctorSelection($session, $message),
            self::STEP_SLOT => $this->handleSlotSelection($session, $message),
            self::STEP_PATIENT_INFO => $this->handlePatientInfoStep($session, $message),
            self::STEP_CONFIRM => $this->handleConfirmation($session, $message),
            default => $this->startBookingFlow($session, $locale), // Safety: restart if unknown step
        };
    }
    
    /**
     * Handle action payload during booking mode.
     * Routes structured actions (select-department, select-doctor, etc.) to handlers.
     * CRITICAL: NO AI CALLS - only database operations.
     */
    protected function handleBookingAction(ChatbotSession $session, string $action, string $locale): array
    {
        $isArabic = $locale === 'ar';
        
        Log::info('[BOOKING] handleBookingAction', [
            'session_id' => $session->id,
            'action' => $action,
            'provider' => 'database',
        ]);
        
        // Cancel booking
        if ($action === 'cancel_booking' || $action === 'cancel') {
            return $this->handleCancel($session, $locale);
        }
        
        // Back navigation  
        if ($action === 'go_back' || $action === 'back') {
            return $this->handleBackStep($session);
        }
        
        // Confirm booking
        if ($action === 'confirm_booking' || $action === 'confirm') {
            return $this->handleConfirmation($session, 'confirm');
        }
        
        // Select department (action may include ID in payload)
        if (str_starts_with($action, 'select_department')) {
            $deptId = $this->extractIdFromAction($action);
            if ($deptId) {
                return $this->handleDepartmentSelection($session, (string)$deptId);
            }
            return $this->redirectToCurrentStep($session, self::STEP_DEPARTMENT);
        }
        
        // Select doctor
        if (str_starts_with($action, 'select_doctor')) {
            $docId = $this->extractIdFromAction($action);
            if ($docId) {
                return $this->handleDoctorSelection($session, (string)$docId);
            }
            return $this->redirectToCurrentStep($session, self::STEP_DOCTOR);
        }
        
        // Select slot
        if (str_starts_with($action, 'select_slot')) {
            $slotData = $this->extractSlotFromAction($action);
            if ($slotData) {
                return $this->handleSlotSelection($session, json_encode($slotData));
            }
            return $this->redirectToCurrentStep($session, self::STEP_SLOT);
        }
        
        // Default: invalid action for booking mode, redirect to current step
        $step = $session->getState('step', self::STEP_DEPARTMENT);
        return $this->redirectToCurrentStep($session, $step);
    }
    
    /**
     * Extract ID from action string like "select_department_123" or "select_department:123"
     */
    protected function extractIdFromAction(string $action): ?int
    {
        if (preg_match('/[\-_:](\d+)$/', $action, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }
    
    /**
     * Extract slot data from action string
     */
    protected function extractSlotFromAction(string $action): ?array
    {
        // Format: select_slot_2026-01-17_09:00
        if (preg_match('/select_slot[_\-:](\d{4}-\d{2}-\d{2})[_\-](\d{2}:\d{2})/', $action, $matches)) {
            return [
                'date' => $matches[1],
                'slot_start' => $matches[1] . ' ' . $matches[2] . ':00',
            ];
        }
        return null;
    }
    
    /**
     * Handle going back one step in the booking flow.
     */
    protected function handleBackStep(ChatbotSession $session): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        $step = $session->getState('step', self::STEP_DEPARTMENT);
        
        // Define step order
        $stepOrder = [
            self::STEP_DEPARTMENT => null,  // Can't go back from first step
            self::STEP_DOCTOR => self::STEP_DEPARTMENT,
            self::STEP_SLOT => self::STEP_DOCTOR,
            self::STEP_PATIENT_INFO => self::STEP_SLOT,
            self::STEP_CONFIRM => self::STEP_PATIENT_INFO,
        ];
        
        $prevStep = $stepOrder[$step] ?? null;
        
        if (!$prevStep) {
            // At first step - offer to cancel
            return [
                'messages' => [$isArabic 
                    ? 'أنت في الخطوة الأولى. هل تريد إلغاء الحجز؟'
                    : 'You are at the first step. Do you want to cancel booking?'],
                'step' => $step,
                'current_step' => $step,
                'mode' => self::MODE_BOOKING,
                'provider' => 'database',
                'ui' => ['type' => 'none', 'items' => []],
            ];
        }
        
        $session->setState('step', $prevStep);
        return $this->redirectToCurrentStep($session, $prevStep);
    }
    
    /**
     * Handle booking flow steps with STRICT validation.
     * CRITICAL: No AI calls are made in this method - all responses are deterministic.
     */
    protected function handleBookingFlowStep(ChatbotSession $session, string $message, string $step, string $intent): array
    {
        Log::info('[BOOKING] Handling step', [
            'session_id' => $session->id,
            'step' => $step,
            'intent' => $intent,
            'message' => mb_substr($message, 0, 50),
        ]);
        
        // If user sends greeting/off-topic during booking, gently redirect
        if (in_array($intent, ['greeting', 'off_topic', 'unclear'])) {
            return $this->redirectToCurrentStep($session, $step);
        }
        
        // STRICT STEP ROUTING - No AI, all DB-driven
        return match ($step) {
            self::STEP_DEPARTMENT => $this->handleDepartmentSelection($session, $message),
            self::STEP_DOCTOR => $this->handleDoctorSelection($session, $message),
            self::STEP_SLOT => $this->handleSlotSelection($session, $message),
            self::STEP_PATIENT_INFO => $this->handlePatientInfoStep($session, $message),
            self::STEP_CONFIRM => $this->handleConfirmation($session, $message),
            default => $this->redirectToCurrentStep($session, $step),
        };
    }
    
    /**
     * STEP 1: Handle department selection.
     * Validates department exists in DB, advances to doctor selection.
     */
    protected function handleDepartmentSelection(ChatbotSession $session, string $message): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        
        // Try to find department by ID, slug, or name
        $department = $this->findDepartmentFromInput($message, $locale);
        
        if (!$department) {
            // Invalid selection - re-show departments
            $departments = Department::active()->ordered()->get();
            return [
                'messages' => [$isArabic
                    ? 'لم أتعرف على هذا القسم. الرجاء اختيار قسم من القائمة أدناه:'
                    : 'I didn\'t recognize that department. Please select one from the list:'],
                'step' => self::STEP_DEPARTMENT,
                'current_step' => self::STEP_DEPARTMENT,
                'step_number' => 1,
                'step_total' => 5,
                'mode' => self::MODE_BOOKING,
                'handler' => 'handleDepartmentSelection',
                'provider' => 'database',
                'validation_error' => true,
                'ui' => [
                    'type' => 'department_cards',
                    'items' => $departments->map(fn($d) => [
                        'id' => $d->id,
                        'name' => $isArabic ? $d->name_ar : $d->name_en,
                        'slug' => $d->slug,
                    ])->toArray(),
                ],
            ];
        }
        
        // Valid department - save and advance to doctor step
        // CRITICAL: Always re-assert mode=booking to ensure persistence
        $session->setState('mode', self::MODE_BOOKING);
        $session->setState('department_id', $department->id);
        $session->setState('department_name', $isArabic ? $department->name_ar : $department->name_en);
        $session->setState('step', self::STEP_DOCTOR);
        
        // Load doctors for this department
        $doctors = User::where('department_id', $department->id)
            ->where('is_active', true)
            ->whereHas('roles', fn($q) => $q->where('name', 'doctor'))
            ->get();
        
        if ($doctors->isEmpty()) {
            return [
                'messages' => [$isArabic
                    ? 'عذراً، لا يوجد أطباء متاحين في هذا القسم حالياً. الرجاء اختيار قسم آخر:'
                    : 'Sorry, no doctors are currently available in this department. Please select another:'],
                'step' => self::STEP_DEPARTMENT,
                'current_step' => self::STEP_DEPARTMENT,
                'step_number' => 1,
                'step_total' => 5,
                'mode' => self::MODE_BOOKING,
                'handler' => 'handleDepartmentSelection',
                'provider' => 'database',
                'ui' => [
                    'type' => 'department_cards',
                    'items' => Department::active()->ordered()->get()->map(fn($d) => [
                        'id' => $d->id,
                        'name' => $isArabic ? $d->name_ar : $d->name_en,
                        'slug' => $d->slug,
                    ])->toArray(),
                ],
            ];
        }
        
        Log::info('[BOOKING] Department selected', [
            'session_id' => $session->id,
            'department_id' => $department->id,
            'doctor_count' => $doctors->count(),
        ]);
        
        return [
            'messages' => [$isArabic
                ? 'اختر الطبيب:'
                : 'Select a doctor:'],
            'step' => self::STEP_DOCTOR,
            'current_step' => self::STEP_DOCTOR,
            'step_number' => 2,
            'step_total' => 5,
            'mode' => self::MODE_BOOKING,
            'handler' => 'handleDepartmentSelection',
            'provider' => 'database',
            'ui' => [
                'type' => 'doctor_cards',
                'items' => $doctors->map(fn($d) => [
                    'id' => $d->id,
                    'name' => $d->name,
                    'specialty' => $d->specialty ?? '',
                    'avatar' => $d->avatar_url ?? null,
                ])->toArray(),
            ],
        ];
    }
    
    /**
     * STEP 2: Handle doctor selection.
     * Validates doctor belongs to selected department, advances to slot selection.
     */
    protected function handleDoctorSelection(ChatbotSession $session, string $message): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        $departmentId = $session->getState('department_id');
        
        // Find doctor by ID or name
        $doctor = $this->findDoctorFromInput($message, $departmentId);
        
        if (!$doctor) {
            // Invalid selection - re-show doctors
            $doctors = User::where('department_id', $departmentId)
                ->where('is_active', true)
                ->whereHas('roles', fn($q) => $q->where('name', 'doctor'))
                ->get();
                
            return [
                'messages' => [$isArabic
                    ? 'لم أتعرف على هذا الطبيب. الرجاء اختيار طبيب من القائمة:'
                    : 'I didn\'t recognize that doctor. Please select one from the list:'],
                'step' => self::STEP_DOCTOR,
                'current_step' => self::STEP_DOCTOR,
                'step_number' => 2,
                'step_total' => 5,
                'mode' => self::MODE_BOOKING,
                'handler' => 'handleDoctorSelection',
                'provider' => 'database',
                'validation_error' => true,
                'ui' => [
                    'type' => 'doctor_cards',
                    'items' => $doctors->map(fn($d) => [
                        'id' => $d->id,
                        'name' => $d->name,
                        'specialty' => $d->specialty ?? '',
                    ])->toArray(),
                ],
            ];
        }
        
        // Valid doctor - save and advance to slot step
        // CRITICAL: Always re-assert mode=booking to ensure persistence
        $session->setState('mode', self::MODE_BOOKING);
        $session->setState('doctor_id', $doctor->id);
        $session->setState('doctor_name', $doctor->name);
        $session->setState('step', self::STEP_SLOT);
        
        // Load available slots using SlotAvailabilityService
        $slotService = app(\App\Services\SlotAvailabilityService::class);
        $today = now()->format('Y-m-d');
        $slots = $slotService->getAvailableSlots($doctor->id, $today);
        
        // If no slots today, try next 7 days
        $availableDays = [];
        for ($i = 0; $i < 7; $i++) {
            $date = now()->addDays($i)->format('Y-m-d');
            $daySlots = $slotService->getAvailableSlots($doctor->id, $date);
            if (!empty($daySlots)) {
                $availableDays[] = [
                    'date' => $date,
                    'date_formatted' => now()->addDays($i)->format('D, M j'),
                    'slots' => $daySlots,
                ];
            }
        }
        
        Log::info('[BOOKING] Doctor selected', [
            'session_id' => $session->id,
            'doctor_id' => $doctor->id,
            'available_days' => count($availableDays),
        ]);
        
        return [
            'messages' => [$isArabic
                ? 'اختر موعداً متاحاً:'
                : 'Select an available time slot:'],
            'step' => self::STEP_SLOT,
            'current_step' => self::STEP_SLOT,
            'step_number' => 3,
            'step_total' => 5,
            'mode' => self::MODE_BOOKING,
            'handler' => 'handleDoctorSelection',
            'provider' => 'database',
            'ui' => [
                'type' => 'slot_cards',
                'items' => $availableDays,
                'doctor_id' => $doctor->id,
            ],
        ];
    }
    
    /**
     * STEP 3: Handle slot selection.
     * Validates slot is available, advances to patient info.
     */
    protected function handleSlotSelection(ChatbotSession $session, string $message): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        $doctorId = $session->getState('doctor_id');
        
        // Parse slot time from message (format: "2026-01-17 09:00" or just "09:00")
        $slotTime = $this->parseSlotTime($message);
        
        if (!$slotTime) {
            // Invalid format - re-show slots
            $slotService = app(\App\Services\SlotAvailabilityService::class);
            $availableDays = [];
            for ($i = 0; $i < 7; $i++) {
                $date = now()->addDays($i)->format('Y-m-d');
                $daySlots = $slotService->getAvailableSlots($doctorId, $date);
                if (!empty($daySlots)) {
                    $availableDays[] = [
                        'date' => $date,
                        'slots' => $daySlots,
                    ];
                }
            }
            
            return [
                'messages' => [$isArabic
                    ? 'الرجاء اختيار موعد من المواعيد المتاحة:'
                    : 'Please select a time from the available slots:'],
                'step' => self::STEP_SLOT,
                'current_step' => self::STEP_SLOT,
                'step_number' => 3,
                'step_total' => 5,
                'mode' => self::MODE_BOOKING,
                'handler' => 'handleSlotSelection',
                'provider' => 'database',
                'validation_error' => true,
                'ui' => [
                    'type' => 'slot_cards',
                    'items' => $availableDays,
                ],
            ];
        }
        
        // Valid slot - save and advance to patient info
        // CRITICAL: Always re-assert mode=booking to ensure persistence
        $session->setState('mode', self::MODE_BOOKING);
        $session->setState('slot_start', $slotTime['start']);
        $session->setState('slot_end', $slotTime['end']);
        $session->setState('slot_date', $slotTime['date']);
        $session->setState('step', self::STEP_PATIENT_INFO);
        
        Log::info('[BOOKING] Slot selected', [
            'session_id' => $session->id,
            'slot_start' => $slotTime['start'],
        ]);
        
        // Get user profile for prefill
        $user = User::find($session->user_id);
        $prefill = $user ? [
            'full_name' => $user->name,
            'phone' => $user->phone ?? '',
            'national_id' => $user->national_id ?? '',
        ] : [];
        
        return [
            'messages' => [$isArabic
                ? 'أكمل بيانات المريض:'
                : 'Complete patient information:'],
            'step' => self::STEP_PATIENT_INFO,
            'current_step' => self::STEP_PATIENT_INFO,
            'step_number' => 4,
            'step_total' => 5,
            'mode' => self::MODE_BOOKING,
            'handler' => 'handleSlotSelection',
            'provider' => 'database',
            'ui' => [
                'type' => 'patient_form',
                'prefill' => $prefill,
                'fields' => [
                    ['name' => 'full_name', 'type' => 'text', 'required' => true, 'label_ar' => 'الاسم الكامل', 'label_en' => 'Full Name'],
                    ['name' => 'phone', 'type' => 'tel', 'required' => true, 'label_ar' => 'رقم الهاتف', 'label_en' => 'Phone Number'],
                    ['name' => 'age', 'type' => 'number', 'required' => true, 'label_ar' => 'العمر', 'label_en' => 'Age'],
                    ['name' => 'gender', 'type' => 'select', 'required' => true, 'label_ar' => 'الجنس', 'label_en' => 'Gender', 'options' => [
                        ['value' => 'male', 'label_ar' => 'ذكر', 'label_en' => 'Male'],
                        ['value' => 'female', 'label_ar' => 'أنثى', 'label_en' => 'Female'],
                    ]],
                    ['name' => 'national_id', 'type' => 'text', 'required' => false, 'label_ar' => 'رقم الهوية', 'label_en' => 'National ID'],
                    ['name' => 'symptoms', 'type' => 'textarea', 'required' => false, 'label_ar' => 'الأعراض/السبب', 'label_en' => 'Symptoms/Reason'],
                ],
            ],
        ];
    }
    
    /**
     * STEP 4: Handle patient info submission.
     * Validates required fields, advances to confirmation.
     */
    protected function handlePatientInfoStep(ChatbotSession $session, string $message): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        
        // Parse patient data from message (JSON or key:value pairs)
        $patientData = $this->parsePatientData($message);
        
        // Validate required fields
        $errors = [];
        if (empty($patientData['full_name'])) {
            $errors[] = $isArabic ? 'الاسم الكامل مطلوب' : 'Full name is required';
        }
        if (empty($patientData['phone'])) {
            $errors[] = $isArabic ? 'رقم الهاتف مطلوب' : 'Phone number is required';
        }
        if (empty($patientData['age'])) {
            $errors[] = $isArabic ? 'العمر مطلوب' : 'Age is required';
        }
        if (empty($patientData['gender'])) {
            $errors[] = $isArabic ? 'الجنس مطلوب' : 'Gender is required';
        }
        
        if (!empty($errors)) {
            return [
                'messages' => [implode("\n", $errors)],
                'step' => self::STEP_PATIENT_INFO,
                'current_step' => self::STEP_PATIENT_INFO,
                'step_number' => 4,
                'step_total' => 5,
                'mode' => self::MODE_BOOKING,
                'handler' => 'handlePatientInfoStep',
                'provider' => 'database',
                'validation_error' => true,
                'ui' => [
                    'type' => 'patient_form',
                    'prefill' => $patientData,
                    'errors' => $errors,
                ],
            ];
        }
        
        // Valid data - save and advance to confirmation
        // CRITICAL: Always re-assert mode=booking to ensure persistence
        $session->setState('mode', self::MODE_BOOKING);
        $session->setState('patient_name', $patientData['full_name']);
        $session->setState('patient_phone', $patientData['phone']);
        $session->setState('patient_age', $patientData['age']);
        $session->setState('patient_gender', $patientData['gender']);
        $session->setState('patient_national_id', $patientData['national_id'] ?? null);
        $session->setState('patient_symptoms', $patientData['symptoms'] ?? null);
        $session->setState('step', self::STEP_CONFIRM);
        
        Log::info('[BOOKING] Patient info submitted', [
            'session_id' => $session->id,
            'patient_name' => $patientData['full_name'],
        ]);
        
        // Build confirmation summary
        $summary = [
            'department' => $session->getState('department_name'),
            'doctor' => $session->getState('doctor_name'),
            'date' => $session->getState('slot_date'),
            'time' => $session->getState('slot_start'),
            'patient_name' => $patientData['full_name'],
            'patient_phone' => $patientData['phone'],
        ];
        
        return [
            'messages' => [$isArabic
                ? 'راجع تفاصيل الموعد واضغط تأكيد للحجز:'
                : 'Review your appointment details and confirm:'],
            'step' => self::STEP_CONFIRM,
            'current_step' => self::STEP_CONFIRM,
            'step_number' => 5,
            'step_total' => 5,
            'mode' => self::MODE_BOOKING,
            'handler' => 'handlePatientInfoStep',
            'provider' => 'database',
            'ui' => [
                'type' => 'confirmation',
                'summary' => $summary,
                'actions' => [
                    ['id' => 'confirm', 'label_ar' => 'تأكيد الحجز', 'label_en' => 'Confirm Booking', 'primary' => true],
                    ['id' => 'cancel', 'label_ar' => 'إلغاء', 'label_en' => 'Cancel', 'primary' => false],
                ],
            ],
        ];
    }
    
    /**
     * STEP 5: Handle booking confirmation.
     * Creates ticket in database and completes flow.
     */
    protected function handleConfirmation(ChatbotSession $session, string $message): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        
        // Check for confirmation
        $confirmed = $this->isConfirmation($message, $locale);
        
        if (!$confirmed) {
            // User declined - cancel booking
            return $this->handleCancel($session, $locale);
        }
        
        // Create ticket in database
        try {
            $ticket = Ticket::create([
                'patient_id' => $session->user_id,
                'department_id' => $session->getState('department_id'),
                'doctor_id' => $session->getState('doctor_id'),
                'subject' => $isArabic ? 'حجز موعد عبر المساعد الذكي' : 'Chatbot Appointment Booking',
                'description' => $session->getState('patient_symptoms') ?? '',
                'status' => 'pending',
                'priority' => 'normal',
                'source' => 'chatbot',
                'slot_start' => $session->getState('slot_start'),
                'slot_end' => $session->getState('slot_end'),
                'patient_name' => $session->getState('patient_name'),
                'patient_phone' => $session->getState('patient_phone'),
                'patient_age' => $session->getState('patient_age'),
                'patient_gender' => $session->getState('patient_gender'),
            ]);
            
            Log::info('[BOOKING] Ticket created', [
                'session_id' => $session->id,
                'ticket_id' => $ticket->id,
            ]);
            
            // Reset booking state
            $session->setState('mode', self::MODE_QA);
            $session->setState('step', self::STEP_COMPLETE);
            
            return [
                'messages' => [$isArabic
                    ? "✅ تم تأكيد حجزك بنجاح!\n\nرقم التذكرة: #{$ticket->id}\nالقسم: {$session->getState('department_name')}\nالطبيب: {$session->getState('doctor_name')}\nالتاريخ: {$session->getState('slot_date')}\n\nستتلقى رسالة تأكيد قريباً."
                    : "✅ Your booking is confirmed!\n\nTicket #: {$ticket->id}\nDepartment: {$session->getState('department_name')}\nDoctor: {$session->getState('doctor_name')}\nDate: {$session->getState('slot_date')}\n\nYou will receive a confirmation message soon."],
                'step' => self::STEP_COMPLETE,
                'current_step' => self::STEP_COMPLETE,
                'mode' => self::MODE_QA,
                'handler' => 'handleConfirmation',
                'provider' => 'database',
                'booking_complete' => true,
                'ticket_id' => $ticket->id,
                'ui' => [
                    'type' => 'booking_success',
                    'ticket_id' => $ticket->id,
                    'reset_booking' => true,
                ],
            ];
            
        } catch (\Exception $e) {
            Log::error('[BOOKING] Failed to create ticket', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'messages' => [$isArabic
                    ? 'عذراً، حدث خطأ أثناء الحجز. الرجاء المحاولة مرة أخرى.'
                    : 'Sorry, an error occurred during booking. Please try again.'],
                'step' => self::STEP_CONFIRM,
                'current_step' => self::STEP_CONFIRM,
                'mode' => self::MODE_BOOKING,
                'handler' => 'handleConfirmation',
                'provider' => 'database',
                'error' => true,
            ];
        }
    }
    
    // =========================================================================
    // HELPER METHODS FOR BOOKING FLOW
    // =========================================================================
    
    /**
     * Find department from user input (ID, slug, or name).
     */
    protected function findDepartmentFromInput(string $input, string $locale): ?Department
    {
        $input = trim($input);
        
        // Try by ID first
        if (is_numeric($input)) {
            return Department::active()->find($input);
        }
        
        // Try by slug
        $dept = Department::active()->where('slug', $input)->first();
        if ($dept) return $dept;
        
        // Try by name (fuzzy match)
        $normalized = $this->normalizeArabic(mb_strtolower($input));
        $departments = Department::active()->get();
        
        foreach ($departments as $dept) {
            $nameAr = $this->normalizeArabic(mb_strtolower($dept->name_ar));
            $nameEn = mb_strtolower($dept->name_en);
            
            if (str_contains($nameAr, $normalized) || str_contains($nameEn, $normalized)) {
                return $dept;
            }
        }
        
        return null;
    }
    
    /**
     * Find doctor from user input (ID or name).
     */
    protected function findDoctorFromInput(string $input, int $departmentId): ?User
    {
        $input = trim($input);
        
        // Try by ID first
        if (is_numeric($input)) {
            return User::where('id', $input)
                ->where('department_id', $departmentId)
                ->where('is_active', true)
                ->first();
        }
        
        // Try by name (fuzzy match)
        $normalized = mb_strtolower($input);
        $doctors = User::where('department_id', $departmentId)
            ->where('is_active', true)
            ->whereHas('roles', fn($q) => $q->where('name', 'doctor'))
            ->get();
        
        foreach ($doctors as $doctor) {
            if (str_contains(mb_strtolower($doctor->name), $normalized)) {
                return $doctor;
            }
        }
        
        return null;
    }
    
    /**
     * Parse slot time from user input.
     */
    protected function parseSlotTime(string $input): ?array
    {
        $input = trim($input);
        
        // Try JSON format first
        $json = json_decode($input, true);
        if ($json && isset($json['slot_start'])) {
            return [
                'start' => $json['slot_start'],
                'end' => $json['slot_end'] ?? null,
                'date' => $json['date'] ?? now()->format('Y-m-d'),
            ];
        }
        
        // Try datetime format: "2026-01-17 09:00"
        if (preg_match('/(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2})/', $input, $matches)) {
            $date = $matches[1];
            $time = $matches[2];
            return [
                'start' => "{$date} {$time}:00",
                'end' => "{$date} " . date('H:i:s', strtotime($time) + 1800),
                'date' => $date,
            ];
        }
        
        // Try time only format: "09:00" (assume today)
        if (preg_match('/(\d{2}:\d{2})/', $input, $matches)) {
            $date = now()->format('Y-m-d');
            $time = $matches[1];
            return [
                'start' => "{$date} {$time}:00",
                'end' => "{$date} " . date('H:i:s', strtotime($time) + 1800),
                'date' => $date,
            ];
        }
        
        return null;
    }
    
    /**
     * Parse patient data from message (JSON or simple text).
     */
    protected function parsePatientData(string $input): array
    {
        $input = trim($input);
        
        // Try JSON format first
        $json = json_decode($input, true);
        if ($json && is_array($json)) {
            return $json;
        }
        
        // Default empty
        return [
            'full_name' => '',
            'phone' => '',
            'age' => '',
            'gender' => '',
        ];
    }
    
    /**
     * Check if message is a confirmation.
     */
    protected function isConfirmation(string $message, string $locale): bool
    {
        $normalized = $this->normalizeArabic(mb_strtolower(trim($message)));
        
        $confirmWords = ['confirm', 'yes', 'ok', 'proceed', 'نعم', 'تاكيد', 'موافق', 'اوافق', 'تم'];
        
        foreach ($confirmWords as $word) {
            if (str_contains($normalized, $this->normalizeArabic($word))) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Redirect to current booking step (re-render step UI).
     * Used when user sends off-topic during booking.
     */
    protected function redirectToCurrentStep(ChatbotSession $session, string $step): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        
        Log::info('[BOOKING] Redirecting to current step', [
            'session_id' => $session->id,
            'step' => $step,
        ]);
        
        return match ($step) {
            self::STEP_DEPARTMENT => $this->startBookingFlow($session, $locale),
            self::STEP_DOCTOR => [
                'messages' => [$isArabic ? 'الرجاء اختيار طبيب من القائمة:' : 'Please select a doctor from the list:'],
                'step' => self::STEP_DOCTOR,
                'current_step' => self::STEP_DOCTOR,
                'step_number' => 2,
                'step_total' => 5,
                'mode' => self::MODE_BOOKING,
                'handler' => 'redirectToCurrentStep',
                'provider' => 'database',
                'ui' => [
                    'type' => 'doctor_cards',
                    'items' => User::where('department_id', $session->getState('department_id'))
                        ->where('is_active', true)
                        ->whereHas('roles', fn($q) => $q->where('name', 'doctor'))
                        ->get()
                        ->map(fn($d) => ['id' => $d->id, 'name' => $d->name])
                        ->toArray(),
                ],
            ],
            self::STEP_SLOT => [
                'messages' => [$isArabic ? 'الرجاء اختيار موعد:' : 'Please select a time slot:'],
                'step' => self::STEP_SLOT,
                'current_step' => self::STEP_SLOT,
                'step_number' => 3,
                'step_total' => 5,
                'mode' => self::MODE_BOOKING,
                'handler' => 'redirectToCurrentStep',
                'provider' => 'database',
                'ui' => ['type' => 'slot_cards', 'items' => []],
            ],
            self::STEP_PATIENT_INFO => [
                'messages' => [$isArabic ? 'الرجاء إكمال بيانات المريض:' : 'Please complete patient information:'],
                'step' => self::STEP_PATIENT_INFO,
                'current_step' => self::STEP_PATIENT_INFO,
                'step_number' => 4,
                'step_total' => 5,
                'mode' => self::MODE_BOOKING,
                'handler' => 'redirectToCurrentStep',
                'provider' => 'database',
                'ui' => ['type' => 'patient_form', 'fields' => []],
            ],
            self::STEP_CONFIRM => [
                'messages' => [$isArabic ? 'الرجاء تأكيد الحجز:' : 'Please confirm your booking:'],
                'step' => self::STEP_CONFIRM,
                'current_step' => self::STEP_CONFIRM,
                'step_number' => 5,
                'step_total' => 5,
                'mode' => self::MODE_BOOKING,
                'handler' => 'redirectToCurrentStep',
                'provider' => 'database',
                'ui' => ['type' => 'confirmation', 'summary' => []],
            ],
            default => $this->startBookingFlow($session, $locale),
        };
    }
    
    /**
     * Handle emergency detection during booking or Q&A.
     * Shows urgent banner, ER guidance, and optionally routes to Emergency department.
     */
    protected function handleEmergencyBooking(ChatbotSession $session, string $message): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        
        Log::warning('[EMERGENCY] Emergency detected in message', [
            'session_id' => $session->id,
            'message' => mb_substr($message, 0, 100),
        ]);
        
        // Find Emergency department if exists
        $emergencyDept = Department::where('slug', 'emergency')
            ->orWhere('name_en', 'like', '%Emergency%')
            ->orWhere('name_ar', 'like', '%طوارئ%')
            ->first();
        
        $urgentMessage = $isArabic
            ? "🚨 **تنبيه طوارئ**\n\nالأعراض التي ذكرتها قد تتطلب رعاية طبية فورية.\n\n**إذا كانت حالة طارئة:**\n• اتصل بالطوارئ فوراً: 911\n• توجه لأقرب قسم طوارئ\n\n**إذا لم تكن الحالة طارئة:**\nيمكنني مساعدتك في حجز موعد عاجل."
            : "🚨 **Emergency Alert**\n\nThe symptoms you described may require immediate medical attention.\n\n**If this is an emergency:**\n• Call emergency services: 911\n• Go to the nearest emergency room\n\n**If not an emergency:**\nI can help you book an urgent appointment.";
        
        return [
            'messages' => [$urgentMessage],
            'step' => self::STEP_INITIAL,
            'current_step' => self::STEP_INITIAL,
            'mode' => self::MODE_QA,
            'intent' => 'emergency',
            'handler' => 'handleEmergencyBooking',
            'provider' => 'local',
            'is_emergency' => true,
            'quick_replies' => $isArabic
                ? ['احجز موعد عاجل', 'لست في حالة طوارئ']
                : ['Book urgent appointment', 'Not an emergency'],
            'ui' => [
                'type' => 'emergency',
                'emergency' => true,
                'emergency_department' => $emergencyDept ? [
                    'id' => $emergencyDept->id,
                    'name' => $isArabic ? $emergencyDept->name_ar : $emergencyDept->name_en,
                ] : null,
            ],
        ];
    }
    
    /**
     * Handle Q&A mode: answer questions, offer booking when appropriate.
     */
    protected function handleQAMode(ChatbotSession $session, string $message, string $intent, array $intentResult): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        $departments = $this->getCachedDepartments($locale);
        
        // Handle pure greetings
        if ($intent === 'greeting') {
            return $this->handleGreeting($session, $locale);
        }
        
        // Handle identity questions (who are you, what can you do)
        if ($intent === 'identity') {
            return $this->handleIdentity($session, $locale);
        }
        
        // Handle medical news requests (REQUIRES internet retrieval)
        if ($intent === 'medical_news') {
            return $this->handleMedicalNews($session, $message, $locale);
        }
        
        // Handle explicit booking intent
        if ($intent === 'booking_intent') {
            return $this->handleBookingIntent($session, $locale);
        }
        
        // Handle confirmation (user agreed to book)
        if ($intent === 'confirmation' && $session->getState('awaiting_booking_confirmation')) {
            return $this->startBookingFlow($session, $locale);
        }
        
        // Handle rejection (user declined to book)
        if ($intent === 'rejection' && $session->getState('awaiting_booking_confirmation')) {
            $session->setState('awaiting_booking_confirmation', false);
            return [
                'messages' => [$isArabic 
                    ? 'حسناً، لا مشكلة. هل لديك أسئلة أخرى يمكنني مساعدتك بها؟'
                    : 'No problem! Is there anything else I can help you with?'],
                'current_step' => self::STEP_INITIAL,
                'intent' => $intent,
            ];
        }
        
        // =====================================================================
        // MEDICAL QUESTION vs SYMPTOM REPORT (from intent classifier)
        // This fixes the bug where "sleep tips" triggered symptom collection
        // =====================================================================
        
        // Handle GENERAL MEDICAL QUESTIONS (advice, tips, how-to)
        // Give helpful info WITHOUT forcing booking
        if ($intent === 'medical_question' && ($intentResult['is_advice_request'] ?? false)) {
            return $this->handleMedicalQuestion($session, $message, $locale);
        }
        
        // Handle SYMPTOM REPORTS (user describing actual health issues)
        // Offer helpful info AND booking
        if ($intent === 'symptom_report') {
            return $this->handleSymptomReport($session, $message, $locale, $departments);
        }
        
        // Use AI for hospital FAQ or general questions
        $aiResult = $this->analyzeWithAI($message, $locale, $departments, $session->getRecentMessages(10));
        
        // If AI suggests departments (symptom detected), offer booking
        if (($aiResult['intent'] === 'symptom' || $aiResult['intent'] === 'symptom_report') && !empty($aiResult['departments'])) {
            $session->setState('symptoms', $message);
            $session->setState('suggested_departments', $aiResult['departments']);
            $session->setState('awaiting_booking_confirmation', true);
            
            $suggestions = $this->mapDepartmentsToReal($aiResult['departments'], $departments);
            
            $bookingOffer = $isArabic 
                ? "\n\nهل تريد حجز موعد في أحد هذه الأقسام؟"
                : "\n\nWould you like to book an appointment in one of these departments?";
            
            return [
                'messages' => [$aiResult['reply'] . $bookingOffer],
                'suggestions' => $suggestions,
                'quick_replies' => $isArabic 
                    ? ['نعم، احجز موعد', 'لا، شكراً']
                    : ['Yes, book appointment', 'No, thanks'],
                'current_step' => self::STEP_SYMPTOMS,
                'should_offer_booking' => true,
                'intent' => 'symptom_report',
            ];
        }
        
        // General Q&A response (no booking needed)
        return [
            'messages' => [$aiResult['reply']],
            'quick_replies' => $aiResult['quick_replies'] ?? [],
            'current_step' => $session->getState('step', self::STEP_INITIAL),
            'should_offer_booking' => false,
            'intent' => $aiResult['intent'] ?? $intent,
        ];
    }
    
    /**
     * Handle greeting message.
     */
    protected function handleGreeting(ChatbotSession $session, string $locale): array
    {
        $isArabic = $locale === 'ar';
        $session->setState('step', self::STEP_INITIAL);
        
        $greeting = $isArabic
            ? "أهلاً بك! 👋 أنا مسار، مساعدك الطبي.\n\nيمكنني مساعدتك في:\n• الإجابة على أسئلتك الصحية\n• حجز موعد مع طبيب\n• معلومات عن أقسام المستشفى\n\nكيف يمكنني مساعدتك اليوم؟"
            : "Hello! 👋 I'm Masar, your medical assistant.\n\nI can help you with:\n• Answering health questions\n• Booking an appointment\n• Hospital department information\n\nHow can I help you today?";
        
        return [
            'messages' => [$greeting],
            'current_step' => self::STEP_INITIAL,
            'intent' => 'greeting',
            'handler' => 'handleGreeting',
            'provider' => 'local',
        ];
    }
    
    /**
     * Handle cancel intent - Reset booking flow and return to Q&A mode.
     * CRITICAL: This clears ALL booking state and signals frontend to hide booking UI.
     */
    protected function handleCancel(ChatbotSession $session, string $locale): array
    {
        $isArabic = $locale === 'ar';
        
        // Reset ALL booking-related state
        $session->setState('mode', self::MODE_QA);
        $session->setState('step', self::STEP_INITIAL);
        $session->setState('department_id', null);
        $session->setState('doctor_id', null);
        $session->setState('selected_time', null);
        $session->setState('payment_method', null);
        $session->setState('symptoms', null);
        $session->setState('awaiting_booking_confirmation', false);
        
        Log::info('[handleCancel] Booking state reset', ['session_id' => $session->id]);
        
        $reply = $isArabic
            ? "✅ تم إلغاء الحجز. كيف يمكنني مساعدتك؟"
            : "✅ Booking cancelled. How can I help you?";
        
        return [
            'messages' => [$reply],
            'quick_replies' => $isArabic 
                ? ['أخبار طبية', 'نصائح للنوم', 'من انت']
                : ['Medical News', 'Sleep Tips', 'Who are you'],
            'current_step' => self::STEP_INITIAL,
            'mode' => self::MODE_QA,
            'intent' => 'cancel',
            'should_offer_booking' => false,
            'handler' => 'handleCancel',
            'provider' => 'local',
            // CRITICAL: Signal frontend to reset booking UI
            'ui' => [
                'type' => 'none',
                'items' => [],
                'reset_booking' => true,
            ],
        ];
    }
    
    /**
     * Handle explicit booking intent.
     * CRITICAL: Immediately start booking flow with mode=booking.
     * NO symptom collection, NO AI - straight to department selection.
     */
    protected function handleBookingIntent(ChatbotSession $session, string $locale): array
    {
        Log::info('[BOOKING] handleBookingIntent - starting booking immediately', [
            'session_id' => $session->id,
            'locale' => $locale,
        ]);
        
        // IMMEDIATELY start booking - no symptom questions
        return $this->startBookingFlow($session, $locale);
    }
    
    /**
     * Start the structured booking flow.
     * FULLY DETERMINISTIC: Loads departments from DB, no AI text.
     */
    protected function startBookingFlow(ChatbotSession $session, string $locale): array
    {
        $isArabic = $locale === 'ar';
        
        // Set booking mode and step
        $session->setState('mode', self::MODE_BOOKING);
        $session->setState('step', self::STEP_DEPARTMENT);
        $session->setState('awaiting_booking_confirmation', false);
        
        // Load ALL departments directly from DB (no AI suggestions)
        $departments = Department::active()->ordered()->get();
        $departmentItems = $departments->map(fn($d) => [
            'id' => $d->id,
            'name' => $isArabic ? $d->name_ar : $d->name_en,
            'name_ar' => $d->name_ar,
            'name_en' => $d->name_en,
            'slug' => $d->slug,
            'icon' => $d->icon_key ?? 'hospital',
        ])->toArray();
        
        Log::info('[BOOKING] Started booking flow', [
            'session_id' => $session->id,
            'department_count' => count($departmentItems),
        ]);
        
        return [
            'messages' => [$isArabic
                ? 'اختر القسم المناسب لموعدك:'
                : 'Select the department for your appointment:'],
            'step' => self::STEP_DEPARTMENT,
            'current_step' => self::STEP_DEPARTMENT,
            'step_number' => 1,
            'step_total' => 5,
            'mode' => self::MODE_BOOKING,
            'intent' => 'booking_started',
            'handler' => 'startBookingFlow',
            'provider' => 'database',
            'ui' => [
                'type' => 'department_cards',
                'items' => $departmentItems,
            ],
        ];
    }
    
    /**
     * Handle noise input (single chars, dots, meaningless).
     */
    protected function handleNoiseInput(ChatbotSession $session, string $locale): array
    {
        $isArabic = $locale === 'ar';
        $step = $session->getState('step', self::STEP_INITIAL);
        
        return [
            'messages' => [$isArabic
                ? 'عذراً، لم أفهم ذلك. يرجى كتابة رسالة واضحة أو اختيار أحد الخيارات المتاحة.'
                : "Sorry, I didn't understand that. Please type a clear message or select one of the available options."],
            'current_step' => $step,
            'validation_error' => 'Input not recognized',
            'intent' => 'noise',
        ];
    }
    
    /**
     * Build topic chips for medical news.
     */
    protected function buildTopicChips(string $locale): array
    {
        $isArabic = $locale === 'ar';
        return $isArabic 
            ? [
                ['id' => 'heart', 'label' => 'صحة القلب'],
                ['id' => 'diabetes', 'label' => 'السكري'],
                ['id' => 'mental', 'label' => 'الصحة النفسية'],
                ['id' => 'nutrition', 'label' => 'التغذية'],
            ]
            : [
                ['id' => 'heart', 'label' => 'Heart Health'],
                ['id' => 'diabetes', 'label' => 'Diabetes'],
                ['id' => 'mental', 'label' => 'Mental Health'],
                ['id' => 'nutrition', 'label' => 'Nutrition'],
            ];
    }
    
    /**
     * Handle hospital FAQ questions.
     */
    protected function handleHospitalFAQ(ChatbotSession $session, string $message, string $locale): array
    {
        $isArabic = $locale === 'ar';
        
        // Use AI/You Agent for FAQ with retrieval
        $aiResult = $this->aiService->analyzeSymptoms($message, $locale, [], 'hospital_faq');
        
        $reply = $aiResult['reply'] ?? '';
        $sources = $aiResult['sources'] ?? [];
        
        // Fallback if no AI response
        if (empty($reply)) {
            $reply = $isArabic
                ? "للمزيد من المعلومات حول خدمات المستشفى، يمكنك الاتصال بنا أو زيارة صفحة الأسئلة الشائعة.\n\nهل تريد حجز موعد؟"
                : "For more information about hospital services, please contact us or visit our FAQ page.\n\nWould you like to book an appointment?";
        }
        
        return [
            'messages' => [$reply],
            'quick_replies' => $isArabic 
                ? ['حجز موعد', 'سؤال آخر']
                : ['Book Appointment', 'Another Question'],
            'current_step' => self::STEP_INITIAL,
            'intent' => 'hospital_faq',
            'should_offer_booking' => true,
            'sources' => $sources,
            'provider' => $aiResult['provider'] ?? 'rules',
        ];
    }
    
    /**
     * Handle identity questions (who are you, what can you do).
     */
    protected function handleIdentity(ChatbotSession $session, string $locale): array
    {
        $isArabic = $locale === 'ar';
        
        $reply = $isArabic
            ? "أنا **مسار** 🏥، مساعدك الطبي الذكي في المستشفى.\n\n**ما يمكنني مساعدتك فيه:**\n• الإجابة على الأسئلة الطبية العامة (مع مصادر موثوقة)\n• تقديم آخر الأخبار الصحية\n• مساعدتك في حجز موعد مع طبيب\n• الإجابة عن أسئلة حول خدمات المستشفى\n\n⚠️ تذكر: أنا لست طبيباً ولا أقدم تشخيصاً أو وصفات طبية.\n\nكيف يمكنني مساعدتك اليوم؟"
            : "I'm **Masar** 🏥, your intelligent hospital assistant.\n\n**What I can help you with:**\n• Answer general medical questions (with reliable sources)\n• Provide the latest health news\n• Help you book an appointment with a doctor\n• Answer questions about hospital services\n\n⚠️ Remember: I'm not a doctor and don't provide diagnoses or prescriptions.\n\nHow can I help you today?";
        
        return [
            'messages' => [$reply],
            'quick_replies' => $isArabic 
                ? ['أخبار طبية', 'حجز موعد', 'نصائح للنوم']
                : ['Medical News', 'Book Appointment', 'Sleep Tips'],
            'current_step' => self::STEP_INITIAL,
            'intent' => 'identity',
            'should_offer_booking' => false,
            'handler' => 'handleIdentity',
            'provider' => 'local',
        ];
    }
    
    /**
     * Handle medical news requests (REQUIRES internet retrieval).
     * Uses You Agent to fetch latest health news with citations.
     */
    protected function handleMedicalNews(ChatbotSession $session, string $message, string $locale): array
    {
        $isArabic = $locale === 'ar';
        
        // Use ChatbotAIService for You Agent + fallback to Gemini + rules
        $aiResult = $this->aiService->analyzeSymptoms($message, $locale, [
            'session_id' => $session->id,
            'mode' => 'qa',
            'history' => $session->getRecentMessages(5),
        ], 'medical_news');
        
        // Format response with sources if available
        $reply = $aiResult['reply'] ?? '';
        $sources = $aiResult['sources'] ?? [];
        $provider = $aiResult['provider'] ?? 'rules';
        
        // Log You Agent call result
        Log::info('[handleMedicalNews] You Agent result', [
            'provider' => $provider,
            'has_reply' => !empty($reply),
        ]);
        
        return [
            'messages' => [$reply],
            'quick_replies' => $isArabic 
                ? ['صحة القلب', 'السكري', 'الصحة النفسية', 'التغذية']
                : ['Heart Health', 'Diabetes', 'Mental Health', 'Nutrition'],
            'current_step' => self::STEP_INITIAL,
            'intent' => 'medical_news',
            'should_offer_booking' => false,
            'sources' => $sources,
            'provider' => $provider,
            'handler' => 'handleMedicalNews',
        ];
    }
    
    /**
     * Handle GENERAL MEDICAL QUESTIONS (tips, advice, how-to).
     * 
     * This is for questions like:
     * - "How can I sleep better?"
     * - "Tips for reducing stress"
     * - "نصائح للنوم"
     * 
     * Uses internet retrieval for grounded responses with citations.
     */
    protected function handleMedicalQuestion(ChatbotSession $session, string $message, string $locale): array
    {
        $isArabic = $locale === 'ar';
        
        // Use ChatbotAIService for You Agent + fallback to Gemini + rules
        // This properly routes to You Agent first for internet-grounded responses
        $aiResult = $this->aiService->analyzeSymptoms($message, $locale, [
            'session_id' => $session->id,
            'mode' => 'qa',
            'history' => $session->getRecentMessages(5),
        ], 'medical_question');
        
        // Get reply and sources from AI result
        $reply = $aiResult['reply'] ?? '';
        $sources = $aiResult['sources'] ?? [];
        $provider = $aiResult['provider'] ?? 'rules';
        
        // Log You Agent call result
        Log::info('[handleMedicalQuestion] You Agent result', [
            'provider' => $provider,
            'has_reply' => !empty($reply),
            'has_sources' => count($sources) > 0,
        ]);
        
        // If AI failed, fall back to offline advice
        if (empty($reply)) {
            $messageLower = mb_strtolower($message);
            $offlineAdvice = $this->getMedicalAdvice($messageLower, $isArabic);
            $reply = $offlineAdvice['advice'];
            $provider = 'rules';
        }
        
        // Optionally offer booking at the end (not forced)
        $bookingOffer = $isArabic
            ? "\n\nإذا كنت ترغب، يمكنني مساعدتك في حجز موعد مع طبيب."
            : "\n\nIf you'd like, I can help you book an appointment with a doctor.";
        
        return [
            'messages' => [$reply . $bookingOffer],
            'quick_replies' => $isArabic 
                ? ['احجز موعد', 'لدي سؤال آخر']
                : ['Book appointment', 'I have another question'],
            'current_step' => self::STEP_INITIAL,
            'should_offer_booking' => true,
            'intent' => 'medical_question',
            'sources' => $sources,
            'provider' => $provider,
            'handler' => 'handleMedicalQuestion',
        ];
    }
    
    /**
     * Get medical advice based on detected topic.
     */
    protected function getMedicalAdvice(string $message, bool $isArabic): array
    {
        // Detect topic
        $topic = 'general';
        
        $sleepKeywords = ['sleep', 'insomnia', 'tired', 'fatigue', 'نوم', 'أرق', 'تعب', 'نعاس'];
        $dietKeywords = ['diet', 'eat', 'food', 'nutrition', 'weight', 'غذاء', 'أكل', 'وزن', 'تخسيس'];
        $stressKeywords = ['stress', 'anxiety', 'worried', 'nervous', 'قلق', 'توتر', 'إجهاد'];
        $exerciseKeywords = ['exercise', 'workout', 'fitness', 'رياضة', 'تمارين'];
        $headacheKeywords = ['headache', 'migraine', 'صداع'];
        
        foreach ($sleepKeywords as $kw) {
            if (str_contains($message, $kw)) { $topic = 'sleep'; break; }
        }
        if ($topic === 'general') {
            foreach ($dietKeywords as $kw) {
                if (str_contains($message, $kw)) { $topic = 'diet'; break; }
            }
        }
        if ($topic === 'general') {
            foreach ($stressKeywords as $kw) {
                if (str_contains($message, $kw)) { $topic = 'stress'; break; }
            }
        }
        if ($topic === 'general') {
            foreach ($exerciseKeywords as $kw) {
                if (str_contains($message, $kw)) { $topic = 'exercise'; break; }
            }
        }
        if ($topic === 'general') {
            foreach ($headacheKeywords as $kw) {
                if (str_contains($message, $kw)) { $topic = 'headache'; break; }
            }
        }
        
        // Return advice based on topic
        return match ($topic) {
            'sleep' => [
                'advice' => $isArabic
                    ? "إليك بعض النصائح لتحسين نومك:\n\n• حافظ على جدول نوم منتظم - اذهب للنوم واستيقظ في نفس الوقت يومياً\n• تجنب الكافيين بعد الظهر\n• اجعل غرفة نومك مظلمة وباردة وهادئة\n• تجنب الشاشات (الهاتف، التلفاز) قبل النوم بساعة\n• مارس تمارين الاسترخاء قبل النوم"
                    : "Here are some tips to improve your sleep:\n\n• Maintain a regular sleep schedule - go to bed and wake up at the same time daily\n• Avoid caffeine after noon\n• Keep your bedroom dark, cool, and quiet\n• Avoid screens (phone, TV) for at least 1 hour before bed\n• Practice relaxation techniques before sleeping",
                'topic' => 'sleep',
            ],
            'diet' => [
                'advice' => $isArabic
                    ? "نصائح غذائية عامة:\n\n• تناول وجبات متوازنة تحتوي على البروتين والخضروات والكربوهيدرات\n• اشرب 8 أكواب ماء يومياً على الأقل\n• قلل من السكريات والأطعمة المصنعة\n• تناول الفواكه والخضروات يومياً\n• لا تتخطى وجبة الإفطار"
                    : "General nutrition advice:\n\n• Eat balanced meals with protein, vegetables, and carbohydrates\n• Drink at least 8 glasses of water daily\n• Reduce sugar and processed foods\n• Eat fruits and vegetables daily\n• Don't skip breakfast",
                'topic' => 'diet',
            ],
            'stress' => [
                'advice' => $isArabic
                    ? "للتعامل مع التوتر والقلق:\n\n• مارس التنفس العميق - 4 ثوانٍ شهيق، 4 ثوانٍ زفير\n• خصص وقتاً للاسترخاء يومياً\n• مارس الرياضة بانتظام\n• تحدث مع شخص تثق به\n• حدد أولوياتك وتجنب الإرهاق\n\nإذا كان التوتر يؤثر على حياتك اليومية، فكر في استشارة متخصص."
                    : "For managing stress and anxiety:\n\n• Practice deep breathing - 4 seconds inhale, 4 seconds exhale\n• Schedule daily relaxation time\n• Exercise regularly\n• Talk to someone you trust\n• Prioritize tasks and avoid burnout\n\nIf stress is affecting your daily life, consider consulting a professional.",
                'topic' => 'stress',
            ],
            'exercise' => [
                'advice' => $isArabic
                    ? "نصائح للياقة البدنية:\n\n• ابدأ بتمارين خفيفة مثل المشي 30 دقيقة يومياً\n• زد الكثافة تدريجياً\n• مارس تمارين الإحماء قبل التمرين\n• اشرب الماء قبل وأثناء وبعد التمرين\n• خذ أيام راحة للسماح لجسمك بالتعافي"
                    : "Fitness tips:\n\n• Start with light exercises like walking 30 minutes daily\n• Gradually increase intensity\n• Always warm up before exercising\n• Stay hydrated before, during, and after exercise\n• Take rest days to allow your body to recover",
                'topic' => 'exercise',
            ],
            'headache' => [
                'advice' => $isArabic
                    ? "للتعامل مع الصداع:\n\n• اشرب كمية كافية من الماء - الجفاف سبب شائع للصداع\n• استرح في مكان هادئ ومظلم\n• ضع كمادة باردة على جبهتك\n• تجنب الإجهاد والتوتر قدر الإمكان\n\n⚠️ راجع طبيباً إذا كان الصداع شديداً جداً أو مستمراً أكثر من 3 أيام"
                    : "For managing headaches:\n\n• Stay hydrated - dehydration is a common cause of headaches\n• Rest in a quiet, dark room\n• Apply a cold compress to your forehead\n• Reduce stress and tension when possible\n\n⚠️ See a doctor if your headache is very severe or lasting more than 3 days",
                'topic' => 'headache',
            ],
            default => [
                'advice' => $isArabic
                    ? "أنا هنا لمساعدتك! يمكنني تقديم نصائح حول:\n\n• النوم والراحة\n• التغذية والحمية\n• التوتر والقلق\n• اللياقة البدنية\n\nما الموضوع الذي تريد معرفة المزيد عنه؟"
                    : "I'm here to help! I can provide tips about:\n\n• Sleep and rest\n• Nutrition and diet\n• Stress and anxiety\n• Fitness and exercise\n\nWhat topic would you like to know more about?",
                'topic' => 'general',
            ],
        };
    }
    
    /**
     * Handle SYMPTOM REPORTS (user describing actual health issues).
     * 
     * This is for messages like:
     * - "I have a headache since yesterday"
     * - "عندي ألم في صدري"
     * 
     * Returns helpful guidance AND offers booking.
     */
    protected function handleSymptomReport(ChatbotSession $session, string $message, string $locale, array $departments): array
    {
        $isArabic = $locale === 'ar';
        
        // Use AI to analyze symptoms and suggest departments
        $aiResult = $this->analyzeWithAI($message, $locale, $departments, $session->getRecentMessages(5));
        
        // Store symptoms and prepare for booking
        $session->setState('symptoms', $message);
        if (!empty($aiResult['departments'])) {
            $session->setState('suggested_departments', $aiResult['departments']);
        }
        $session->setState('awaiting_booking_confirmation', true);
        
        $suggestions = $this->mapDepartmentsToReal($aiResult['departments'] ?? [], $departments);
        
        $bookingOffer = $isArabic 
            ? "\n\nهل تريد حجز موعد؟"
            : "\n\nWould you like to book an appointment?";
        
        return [
            'messages' => [$aiResult['reply'] . $bookingOffer],
            'suggestions' => $suggestions,
            'quick_replies' => $isArabic 
                ? ['نعم، احجز موعد', 'لا، شكراً']
                : ['Yes, book appointment', 'No, thanks'],
            'current_step' => self::STEP_SYMPTOMS,
            'should_offer_booking' => true,
            'intent' => 'symptom_report',
        ];
    }
    
    /**
     * Handle emergency detection.
     */
    protected function handleEmergency(ChatbotSession $session, string $message, array $intentResult): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        
        $this->metricsService->recordEmergency();
        
        $emergencyMsg = $isArabic
            ? "🚨 **تحذير: هذه حالة طوارئ محتملة!**\n\nبناءً على ما وصفته، يرجى:\n\n1. **اتصل بالطوارئ فوراً: 911**\n2. أو توجه فوراً لأقرب غرفة طوارئ\n\n⚠️ لا تنتظر - صحتك أولوية.\n\nهل تريد أن أساعدك في حجز موعد طوارئ عاجل؟"
            : "🚨 **Warning: This may be an emergency!**\n\nBased on what you described, please:\n\n1. **Call emergency services immediately: 911**\n2. Or go to the nearest emergency room now\n\n⚠️ Do not wait - your health is the priority.\n\nWould you like me to help book an urgent emergency appointment?";
        
        return [
            'messages' => [$emergencyMsg],
            'is_emergency' => true,
            'quick_replies' => $isArabic
                ? [['label' => 'نعم، حجز طوارئ', 'payload' => ['type' => 'emergency_booking']], ['label' => 'فهمت، شكراً', 'payload' => ['type' => 'acknowledge']]]
                : [['label' => 'Yes, book emergency', 'payload' => ['type' => 'emergency_booking']], ['label' => 'I understand, thanks', 'payload' => ['type' => 'acknowledge']]],
            'current_step' => $session->getState('step'),
            'intent' => 'emergency',
            'safety' => [
                'is_emergency' => true,
                'reason' => $intentResult['reason'] ?? 'Emergency symptoms detected',
            ],
        ];
    }
    
    /**
     * Render department selection step.
     */
    protected function renderDepartmentStep(ChatbotSession $session, string $prefix = ''): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        $departments = $this->getCachedDepartments($locale);
        
        $suggestedSlugs = $session->getState('suggested_departments', []);
        $suggestions = $this->mapDepartmentsToReal($suggestedSlugs, $departments);
        
        if (empty($suggestions)) {
            $suggestions = array_map(fn($d) => ['id' => $d['id'], 'name' => $d['name'], 'slug' => $d['slug']], $departments);
        }
        
        return [
            'messages' => [$prefix . ($isArabic ? 'يرجى اختيار القسم:' : 'Please select a department:')],
            'suggestions' => $suggestions,
            'current_step' => self::STEP_DEPARTMENT,
            'step_number' => 1,
            'action' => 'select_department',
        ];
    }
    
    /**
     * Render doctor selection step.
     */
    protected function renderDoctorStep(ChatbotSession $session, string $prefix = ''): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        $departmentId = $session->getState('department_id');
        $doctors = $this->getDoctorsForDepartment($departmentId);
        
        return [
            'messages' => [$prefix . ($isArabic ? 'يرجى اختيار الطبيب:' : 'Please select a doctor:')],
            'doctors' => array_map(fn($d) => ['id' => $d['id'], 'name' => $d['name']], $doctors),
            'current_step' => self::STEP_DOCTOR,
            'step_number' => 2,
            'action' => 'select_doctor',
        ];
    }
    
    /**
     * Render patient info step.
     */
    protected function renderPatientInfoStep(ChatbotSession $session, string $prefix = ''): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        
        return [
            'messages' => [$prefix . ($isArabic ? 'يرجى إدخال معلومات المريض:' : 'Please provide patient information:')],
            'current_step' => self::STEP_PATIENT_INFO,
            'step_number' => 3,
            'action' => 'collect_patient_info',
        ];
    }
    
    /**
     * Render payment step.
     */
    protected function renderPaymentStep(ChatbotSession $session, string $prefix = ''): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        
        return [
            'messages' => [$prefix . ($isArabic 
                ? "يرجى اختيار طريقة الدفع:\n\n1️⃣ الدفع الآن\n2️⃣ الدفع في المستشفى"
                : "Please select a payment method:\n\n1️⃣ Pay Now\n2️⃣ Pay at Hospital")],
            'current_step' => self::STEP_PAYMENT,
            'step_number' => 4,
            'action' => 'select_payment',
            'payment_options' => [
                ['id' => 'online', 'label' => $isArabic ? 'الدفع الآن' : 'Pay Now'],
                ['id' => 'pay_at_hospital', 'label' => $isArabic ? 'الدفع في المستشفى' : 'Pay at Hospital'],
            ],
        ];
    }
    
    /**
     * Render confirm step.
     */
    protected function renderConfirmStep(ChatbotSession $session, string $prefix = ''): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        $summary = $this->buildFullBookingSummary($session);
        
        return [
            'messages' => [$prefix . $summary, $isArabic ? 'هل تريد تأكيد الحجز؟' : 'Confirm this booking?'],
            'current_step' => self::STEP_CONFIRM,
            'step_number' => 5,
            'action' => 'confirm',
            'quick_replies' => $isArabic ? ['نعم، تأكيد', 'لا، إلغاء'] : ['Yes, confirm', 'No, cancel'],
        ];
    }
    
    /**
     * Check if message is a back navigation request.
     */
    protected function isBackRequest(string $normalizedMsg): bool
    {
        $backKeywords = ['back', 'previous', 'go back', 'رجوع', 'السابق', 'ارجع'];
        foreach ($backKeywords as $kw) {
            if (str_contains($normalizedMsg, $kw)) return true;
        }
        return false;
    }
    
    /**
     * Check if message is a cancel request.
     */
    protected function isCancelRequest(string $normalizedMsg): bool
    {
        $cancelKeywords = ['cancel', 'stop', 'quit', 'start over', 'الغاء', 'توقف', 'ابدا من جديد'];
        foreach ($cancelKeywords as $kw) {
            if (str_contains($normalizedMsg, $kw)) return true;
        }
        return false;
    }
    
    /**
     * Check if currently in booking flow (steps 1-5).
     */
    protected function isInBookingFlow(string $step): bool
    {
        return in_array($step, [
            self::STEP_DEPARTMENT, self::STEP_DOCTOR, self::STEP_PATIENT_INFO,
            self::STEP_PAYMENT, self::STEP_CONFIRM
        ]);
    }
    
    /**
     * Handle back navigation - go to previous step.
     */
    protected function handleBackNavigation(ChatbotSession $session, string $currentStep): array
    {
        $isArabic = $session->locale === 'ar';
        
        $previousStep = match ($currentStep) {
            self::STEP_DOCTOR => self::STEP_DEPARTMENT,
            self::STEP_PATIENT_INFO => self::STEP_DOCTOR,
            self::STEP_PAYMENT => self::STEP_PATIENT_INFO,
            self::STEP_CONFIRM => self::STEP_PAYMENT,
            default => self::STEP_SYMPTOMS,
        };
        
        $session->setState('step', $previousStep);
        $session->save();
        
        $stepNames = [
            self::STEP_SYMPTOMS => $isArabic ? 'وصف الأعراض' : 'Symptom Description',
            self::STEP_DEPARTMENT => $isArabic ? 'اختيار القسم' : 'Department Selection',
            self::STEP_DOCTOR => $isArabic ? 'اختيار الطبيب' : 'Doctor Selection',
            self::STEP_PATIENT_INFO => $isArabic ? 'معلومات المريض' : 'Patient Information',
            self::STEP_PAYMENT => $isArabic ? 'طريقة الدفع' : 'Payment Method',
        ];
        
        $backMessage = $isArabic 
            ? "⬅️ تم الرجوع إلى: {$stepNames[$previousStep]}. يرجى المتابعة."
            : "⬅️ Returned to: {$stepNames[$previousStep]}. Please continue.";
        
        return [
            'messages' => [$backMessage],
            'current_step' => $previousStep,
            'action' => 'back_navigation',
            'quick_replies' => $isArabic ? ['متابعة', 'إلغاء الحجز'] : ['Continue', 'Cancel booking'],
        ];
    }
    
    /**
     * Handle cancel flow - return to symptom step.
     */
    protected function handleCancelFlow(ChatbotSession $session): array
    {
        $isArabic = $session->locale === 'ar';
        
        // Clear booking state but keep session
        $session->setState('step', self::STEP_SYMPTOMS);
        $session->setState('department_id', null);
        $session->setState('doctor_id', null);
        $session->setState('symptoms', null);
        $session->save();
        
        return [
            'messages' => [$isArabic 
                ? '❌ تم إلغاء الحجز. كيف يمكنني مساعدتك؟'
                : '❌ Booking cancelled. How can I help you?'],
            'current_step' => self::STEP_SYMPTOMS,
            'action' => 'flow_cancelled',
        ];
    }


    /**
     * Handle initial/symptom step.
     */
    protected function handleSymptomStep(ChatbotSession $session, string $message): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        $departments = $this->getCachedDepartments($locale);

        $aiResult = $this->analyzeWithAI($message, $locale, $departments, $session->getRecentMessages(5));

        // Greetings
        if ($aiResult['intent'] === 'greeting') {
            $session->setState('step', self::STEP_SYMPTOMS);
            return [
                'messages' => [$aiResult['reply']],
                'quick_replies' => [],
                'suggestions' => [],
                'current_step' => self::STEP_SYMPTOMS,
            ];
        }

        // Symptoms with department suggestion
        if ($aiResult['intent'] === 'symptom' && !empty($aiResult['departments'])) {
            $session->setState('step', self::STEP_DEPARTMENT);
            $session->setState('symptoms', $message);
            $session->setState('suggested_departments', $aiResult['departments']);

            $suggestions = $this->mapDepartmentsToReal($aiResult['departments'], $departments);

            // If no real matches found, try fallback slugs
            if (empty($suggestions)) {
                $fallbackSlugs = ['internal-medicine', 'general-medicine', 'emergency'];
                $suggestions = $this->mapDepartmentsToReal($fallbackSlugs, $departments);
            }

            return [
                'messages' => [$aiResult['reply']],
                'quick_replies' => [],
                'suggestions' => $suggestions,
                'current_step' => self::STEP_DEPARTMENT,
                'action' => 'select_department',
            ];
        }

        // Need clarification
        $session->setState('step', self::STEP_SYMPTOMS);
        return [
            'messages' => [$aiResult['reply']],
            'quick_replies' => $aiResult['quick_replies'] ?? [],
            'suggestions' => [],
            'current_step' => self::STEP_SYMPTOMS,
        ];
    }

    /**
     * Handle department selection with STRICT validation.
     * Only advances to next step if a valid department_id is provided.
     */
    protected function handleDepartmentSelection(ChatbotSession $session, string $message): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        $departments = $this->getCachedDepartments($locale);

        // Try to extract department from JSON payload first (frontend structured input)
        $decoded = json_decode($message, true);
        if (is_array($decoded) && isset($decoded['department_id'])) {
            $deptId = (int) $decoded['department_id'];
            $selectedDept = collect($departments)->firstWhere('id', $deptId);
        } else {
            // Try numeric ID extraction
            $numericId = $this->intentClassifier->extractNumericId($message);
            if ($numericId) {
                $selectedDept = collect($departments)->firstWhere('id', $numericId);
            }
            
            // Fallback to name/slug matching
            if (!isset($selectedDept) || !$selectedDept) {
                $selectedDept = $this->matchDepartmentFromInput($message, $departments);
            }
        }

        // VALIDATION FAILED: Stay on this step
        if (!$selectedDept) {
            $suggestedSlugs = $session->getState('suggested_departments', []);
            $suggestions = $this->mapDepartmentsToReal($suggestedSlugs, $departments);
            
            if (empty($suggestions)) {
                $suggestions = array_map(fn($d) => ['id' => $d['id'], 'name' => $d['name'], 'slug' => $d['slug']], $departments);
            }
            
            // De-duplicate by ID
            $suggestions = array_values(array_reduce($suggestions, function ($carry, $item) {
                $carry[$item['id']] = $item;
                return $carry;
            }, []));

            return [
                'messages' => [$isArabic 
                    ? 'لم أتمكن من تحديد القسم. يرجى اختيار من القائمة أو النقر على أحد الأقسام:'
                    : "I couldn't identify that department. Please select from the list or click on one:"],
                'suggestions' => $suggestions,
                'current_step' => self::STEP_DEPARTMENT,
                'step_number' => 1,
                'step_total' => 5,
                'action' => 'select_department',
                'validation_error' => 'Invalid department selection',
            ];
        }

        // VALIDATION PASSED: Advance to doctor step
        Log::info('Department selected', [
            'session_id' => $session->id,
            'department_id' => $selectedDept['id'],
            'department_name' => $selectedDept['name'],
        ]);
        
        $session->setState('department_id', $selectedDept['id']);
        $session->setState('department_name', $selectedDept['name']);
        $session->setState('step', self::STEP_DOCTOR);

        $doctors = $this->getDoctorsForDepartment($selectedDept['id']);

        // Handle case: no doctors available
        if (empty($doctors)) {
            $session->setState('step', self::STEP_PATIENT_INFO);
            $session->setState('doctor_id', null);
            $session->setState('doctor_name', $isArabic ? 'سيتم التعيين لاحقاً' : 'To be assigned');
            
            return [
                'messages' => [$isArabic
                    ? "✅ تم اختيار قسم **{$selectedDept['name']}**.\n\nلا يوجد أطباء متاحين حالياً في هذا القسم، لكن يمكنك المتابعة وسيتم تعيين طبيب لاحقاً.\n\n**الخطوة 3 من 5: معلومات المريض**"
                    : "✅ You've selected **{$selectedDept['name']}**.\n\nNo doctors currently available in this department, but you can proceed and a doctor will be assigned.\n\n**Step 3 of 5: Patient Information**"],
                'current_step' => self::STEP_PATIENT_INFO,
                'step_number' => 3,
                'step_total' => 5,
                'action' => 'collect_patient_info',
                'transition_reason' => 'no_doctors_available',
            ];
        }

        // De-duplicate doctors by ID
        $uniqueDoctors = array_values(array_reduce($doctors, function ($carry, $doc) {
            $carry[$doc['id']] = ['id' => $doc['id'], 'name' => $doc['name']];
            return $carry;
        }, []));

        return [
            'messages' => [$isArabic
                ? "✅ تم اختيار قسم **{$selectedDept['name']}**.\n\n**الخطوة 2 من 5: اختيار الطبيب**\n\nيرجى اختيار الطبيب:"
                : "✅ You've selected **{$selectedDept['name']}**.\n\n**Step 2 of 5: Select Doctor**\n\nPlease choose a doctor:"],
            'doctors' => $uniqueDoctors,
            'current_step' => self::STEP_DOCTOR,
            'step_number' => 2,
            'step_total' => 5,
            'action' => 'select_doctor',
            'transition_reason' => 'validated_department_id',
        ];
    }

    /**
     * Handle doctor selection with STRICT validation.
     * Only advances to next step if a valid doctor_id is provided.
     */
    protected function handleDoctorSelection(ChatbotSession $session, string $message): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        $departmentId = $session->getState('department_id');
        $doctors = $this->getDoctorsForDepartment($departmentId);
        
        // Try to extract doctor from JSON payload first (frontend structured input)
        $decoded = json_decode($message, true);
        if (is_array($decoded) && isset($decoded['doctor_id'])) {
            $docId = (int) $decoded['doctor_id'];
            $selectedDoctor = collect($doctors)->firstWhere('id', $docId);
        } else {
            // Try numeric ID extraction
            $numericId = $this->intentClassifier->extractNumericId($message);
            if ($numericId) {
                $selectedDoctor = collect($doctors)->firstWhere('id', $numericId);
            }
            
            // Fallback to name matching
            if (!isset($selectedDoctor) || !$selectedDoctor) {
                $selectedDoctor = $this->matchDoctorFromInput($message, $doctors);
            }
        }

        // VALIDATION FAILED: Stay on this step
        if (!$selectedDoctor && !empty($doctors)) {
            // De-duplicate doctors by ID
            $uniqueDoctors = array_values(array_reduce($doctors, function ($carry, $doc) {
                $carry[$doc['id']] = ['id' => $doc['id'], 'name' => $doc['name']];
                return $carry;
            }, []));
            
            return [
                'messages' => [$isArabic 
                    ? 'لم أتمكن من تحديد الطبيب. يرجى اختيار من القائمة أو النقر على أحد الأطباء:'
                    : "I couldn't identify that doctor. Please select from the list or click on one:"],
                'doctors' => $uniqueDoctors,
                'current_step' => self::STEP_DOCTOR,
                'step_number' => 2,
                'step_total' => 5,
                'action' => 'select_doctor',
                'validation_error' => 'Invalid doctor selection',
            ];
        }

        // VALIDATION PASSED: Advance to patient info step
        Log::info('Doctor selected', [
            'session_id' => $session->id,
            'doctor_id' => $selectedDoctor['id'] ?? null,
            'doctor_name' => $selectedDoctor['name'] ?? null,
        ]);
        
        $session->setState('doctor_id', $selectedDoctor['id'] ?? null);
        $session->setState('doctor_name', $selectedDoctor['name'] ?? null);
        $session->setState('step', self::STEP_PATIENT_INFO);

        // Pre-fill patient info from authenticated user profile (Smart Inference)
        $user = $session->user;
        if ($user) {
            $session->setState('patient_name', $user->name ?? '');
            $session->setState('contact_phone', $user->phone ?? '');
        }

        $docName = $selectedDoctor['name'] ?? '';
        
        // Build prompt for patient info - showing pre-filled data
        $prefillName = $session->getState('patient_name', '');
        $prefillPhone = $session->getState('contact_phone', '');
        
        if ($isArabic) {
            $prefilledInfo = '';
            if ($prefillName) {
                $prefilledInfo .= "✅ الاسم: {$prefillName}\n";
            }
            if ($prefillPhone) {
                $prefilledInfo .= "✅ الهاتف: {$prefillPhone}\n";
            }
            
            $msg = "✅ تم اختيار الدكتور **{$docName}**.\n\n**الخطوة 3 من 5: معلومات المريض**\n\n📋 المعلومات المتوفرة:\n{$prefilledInfo}\nيرجى تأكيد أو إكمال المعلومات:";
            
            return [
                'messages' => [$msg],
                'current_step' => self::STEP_PATIENT_INFO,
                'step_number' => 3,
                'step_total' => 5,
                'action' => 'collect_patient_info',
                'prefilled' => [
                    'name' => $prefillName,
                    'phone' => $prefillPhone,
                ],
                'required_fields' => ['age', 'gender'],
                'transition_reason' => 'validated_doctor_id',
            ];
        }
        
        $prefilledInfo = '';
        if ($prefillName) {
            $prefilledInfo .= "✅ Name: {$prefillName}\n";
        }
        if ($prefillPhone) {
            $prefilledInfo .= "✅ Phone: {$prefillPhone}\n";
        }
        
        return [
            'messages' => ["✅ You've selected **Dr. {$docName}**.\n\n**Step 3 of 5: Patient Information**\n\n📋 Available Info:\n{$prefilledInfo}\nPlease confirm or complete:"],
            'current_step' => self::STEP_PATIENT_INFO,
            'step_number' => 3,
            'step_total' => 5,
            'action' => 'collect_patient_info',
            'prefilled' => [
                'name' => $prefillName,
                'phone' => $prefillPhone,
            ],
            'required_fields' => ['age', 'gender'],
            'transition_reason' => 'validated_doctor_id',
        ];
    }

    /**
     * Handle patient info collection (Step 3 - Hybrid form with smart inference).
     * Auto-fills known data, asks only for missing required fields.
     */
    protected function handlePatientInfoStep(ChatbotSession $session, string $message): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        
        // Parse patient info from message (could be JSON from frontend form or natural text)
        $patientData = $this->parsePatientInfoFromMessage($message, $session);
        
        // Validate required fields
        $age = $patientData['age'] ?? $session->getState('patient_age');
        $gender = $patientData['gender'] ?? $session->getState('patient_gender');
        $name = $patientData['name'] ?? $session->getState('patient_name');
        $phone = $patientData['phone'] ?? $session->getState('contact_phone');
        
        // Check for emergency inference from symptoms
        $isEmergency = $session->getState('is_emergency', false) || $this->detectEmergency($session->getState('symptoms', ''));
        
        // Save all patient data to session
        if ($name) $session->setState('patient_name', $name);
        if ($phone) $session->setState('contact_phone', $phone);
        if ($age) $session->setState('patient_age', (int) $age);
        if ($gender) $session->setState('patient_gender', $gender);
        $session->setState('is_emergency', $isEmergency);
        
        // Check if we have required fields
        $missingFields = [];
        if (!$session->getState('patient_age')) $missingFields[] = $isArabic ? 'العمر' : 'age';
        if (!$session->getState('patient_gender')) $missingFields[] = $isArabic ? 'الجنس' : 'gender';
        
        if (!empty($missingFields)) {
            $missing = implode(', ', $missingFields);
            return [
                'messages' => [$isArabic 
                    ? "يرجى إدخال المعلومات المطلوبة: {$missing}"
                    : "Please provide the required information: {$missing}"],
                'current_step' => self::STEP_PATIENT_INFO,
                'step_number' => 3,
                'step_total' => 5,
                'action' => 'collect_patient_info',
                'missing_fields' => $missingFields,
            ];
        }
        
        // All required data collected - proceed to PAYMENT step
        $session->setState('step', self::STEP_PAYMENT);
        
        // Show emergency banner if detected
        $emergencyBanner = '';
        if ($isEmergency) {
            $emergencyBanner = $isArabic 
                ? "⚠️ تم اكتشاف حالة طارئة! سيتم إعطاء أولوية لحالتك.\n\n"
                : "⚠️ Emergency detected! Your case will be prioritized.\n\n";
        }
        
        $paymentPrompt = $isArabic
            ? "{$emergencyBanner}📋 تم حفظ معلوماتك.\n\n💳 اختر طريقة الدفع:\n\n1️⃣ الدفع الآن (إلكتروني)\n2️⃣ الدفع في المستشفى"
            : "{$emergencyBanner}📋 Your information has been saved.\n\n💳 Select payment method:\n\n1️⃣ Pay Now (Online)\n2️⃣ Pay at Hospital";
        
        return [
            'messages' => [$paymentPrompt],
            'current_step' => self::STEP_PAYMENT,
            'step_number' => 4,
            'step_total' => 5,
            'action' => 'select_payment',
            'is_emergency' => $isEmergency,
            'payment_options' => [
                ['id' => 'online', 'label' => $isArabic ? 'الدفع الآن' : 'Pay Now'],
                ['id' => 'pay_at_hospital', 'label' => $isArabic ? 'الدفع في المستشفى' : 'Pay at Hospital'],
            ],
        ];
    }

    /**
     * Handle payment method selection (Step 4 - MANDATORY).
     * Pay Now → assigned status, Pay at Hospital → awaiting_payment
     */
    protected function handlePaymentStep(ChatbotSession $session, string $message): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        $msgLower = mb_strtolower($this->normalizeArabic($message));
        
        // Detect payment method selection
        $isPayNow = str_contains($msgLower, 'pay now') || str_contains($msgLower, 'online') 
            || str_contains($msgLower, 'الدفع الان') || str_contains($msgLower, 'الكتروني')
            || str_contains($msgLower, '1') || str_contains($msgLower, 'الآن');
            
        $isPayAtHospital = str_contains($msgLower, 'hospital') || str_contains($msgLower, 'pay at')
            || str_contains($msgLower, 'المستشفى') || str_contains($msgLower, 'لاحقا')
            || str_contains($msgLower, '2') || str_contains($msgLower, 'في المستشفى');
        
        if (!$isPayNow && !$isPayAtHospital) {
            return [
                'messages' => [$isArabic 
                    ? "يرجى اختيار طريقة الدفع:\n\n1️⃣ الدفع الآن\n2️⃣ الدفع في المستشفى"
                    : "Please select a payment method:\n\n1️⃣ Pay Now\n2️⃣ Pay at Hospital"],
                'current_step' => self::STEP_PAYMENT,
                'step_number' => 4,
                'step_total' => 5,
                'action' => 'select_payment',
                'payment_options' => [
                    ['id' => 'online', 'label' => $isArabic ? 'الدفع الآن' : 'Pay Now'],
                    ['id' => 'pay_at_hospital', 'label' => $isArabic ? 'الدفع في المستشفى' : 'Pay at Hospital'],
                ],
            ];
        }
        
        // Save payment method
        $paymentMethod = $isPayNow ? 'online' : 'pay_at_hospital';
        $session->setState('payment_method', $paymentMethod);
        
        // Set scheduled_at to tomorrow 10 AM as default (can be customized)
        $scheduledAt = now('Asia/Riyadh')->addDay()->setTime(10, 0);
        $session->setState('scheduled_at', $scheduledAt->toIso8601String());
        $session->setState('scheduled_display', $scheduledAt->format('l, F j, Y - g:i A'));
        
        // Move to confirmation step
        $session->setState('step', self::STEP_CONFIRM);
        
        // Build full booking summary with payment info
        $summary = $this->buildFullBookingSummary($session);
        
        $paymentNote = $isArabic
            ? ($isPayNow 
                ? "\n\n💳 الدفع: إلكتروني - سيتم معالجة الحجز فوراً"
                : "\n\n💳 الدفع: في المستشفى - يجب مراجعة الاستقبال للتأكيد")
            : ($isPayNow
                ? "\n\n💳 Payment: Online - Your booking will be processed immediately"
                : "\n\n💳 Payment: At Hospital - Please visit reception to confirm");
        
        return [
            'messages' => [$summary . $paymentNote, $isArabic ? 'هل تريد تأكيد الحجز؟' : 'Confirm this booking?'],
            'current_step' => self::STEP_CONFIRM,
            'step_number' => 5,
            'step_total' => 5,
            'action' => 'confirm',
            'quick_replies' => $isArabic ? ['نعم، تأكيد', 'لا، إلغاء'] : ['Yes, confirm', 'No, cancel'],
            'payment_method' => $paymentMethod,
        ];
    }

    /**
     * Parse patient info from message (JSON or natural language).
     */
    protected function parsePatientInfoFromMessage(string $message, ChatbotSession $session): array
    {
        // Try JSON first (from frontend form submission)
        $decoded = json_decode($message, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
        
        // Parse natural language
        $data = [];
        $msgLower = mb_strtolower($message);
        
        // Extract age
        if (preg_match('/(\d{1,3})\s*(years?|سنة|سنوات|عام)?/i', $message, $matches)) {
            $age = (int) $matches[1];
            if ($age > 0 && $age < 150) {
                $data['age'] = $age;
            }
        }
        
        // Extract gender
        if (str_contains($msgLower, 'male') || str_contains($msgLower, 'ذكر') || str_contains($msgLower, 'رجل')) {
            $data['gender'] = 'male';
        } elseif (str_contains($msgLower, 'female') || str_contains($msgLower, 'انثى') || str_contains($msgLower, 'أنثى') || str_contains($msgLower, 'امرأة')) {
            $data['gender'] = 'female';
        }
        
        // Extract phone (simple pattern)
        if (preg_match('/(\+?\d{9,15})/', $message, $matches)) {
            $data['phone'] = $matches[1];
        }
        
        return $data;
    }

    /**
     * Detect emergency symptoms from message.
     */
    protected function detectEmergency(string $message): bool
    {
        $msgLower = mb_strtolower($this->normalizeArabic($message));
        
        foreach (self::EMERGENCY_KEYWORDS_EN as $keyword) {
            if (str_contains($msgLower, $keyword)) {
                return true;
            }
        }
        
        foreach (self::EMERGENCY_KEYWORDS_AR as $keyword) {
            if (str_contains($msgLower, $this->normalizeArabic($keyword))) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Build full booking summary including patient info and payment.
     */
    protected function buildFullBookingSummary(ChatbotSession $session): string
    {
        $isArabic = $session->locale === 'ar';
        
        $dept = $session->getState('department_name', '-');
        $doctor = $session->getState('doctor_name', $isArabic ? 'سيتم التعيين' : 'To be assigned');
        $datetime = $session->getState('scheduled_display', '-');
        $symptoms = Str::limit($session->getState('symptoms', '-'), 100);
        $name = $session->getState('patient_name', '-');
        $age = $session->getState('patient_age', '-');
        $gender = $session->getState('patient_gender', '-');
        $phone = $session->getState('contact_phone', '-');
        $isEmergency = $session->getState('is_emergency', false);
        
        $emergencyBadge = $isEmergency ? ($isArabic ? ' 🚨 طوارئ' : ' 🚨 Emergency') : '';

        if ($isArabic) {
            return "📋 ملخص الحجز{$emergencyBadge}:\n\n" .
                   "👤 الاسم: {$name}\n" .
                   "📅 العمر: {$age}\n" .
                   "⚧ الجنس: {$gender}\n" .
                   "📞 الهاتف: {$phone}\n\n" .
                   "🏥 القسم: {$dept}\n" .
                   "👨‍⚕️ الطبيب: {$doctor}\n" .
                   "📅 الموعد: {$datetime}\n" .
                   "📝 الأعراض: {$symptoms}";
        }

        return "📋 Booking Summary{$emergencyBadge}:\n\n" .
               "👤 Name: {$name}\n" .
               "📅 Age: {$age}\n" .
               "⚧ Gender: {$gender}\n" .
               "📞 Phone: {$phone}\n\n" .
               "🏥 Department: {$dept}\n" .
               "👨‍⚕️ Doctor: {$doctor}\n" .
               "📅 Date/Time: {$datetime}\n" .
               "📝 Symptoms: {$symptoms}";
    }

    /**
     * Handle date/time selection (DEPRECATED - kept for backward compatibility).
     */
    protected function handleDateTimeSelection(ChatbotSession $session, string $message): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';

        $parsed = $this->parseDateTimeFromMessage($message, $locale);

        if (!$parsed) {
            return [
                'messages' => [$isArabic
                    ? 'لم أفهم الموعد. يرجى اختيار من القائمة أو كتابة مثل "غداً الساعة 2 مساءً"'
                    : "I didn't understand. Select from the list or type like \"tomorrow at 2 PM\""],
                'quick_replies' => $this->getTimeSlotQuickReplies($locale),
                'current_step' => self::STEP_DATETIME,
            ];
        }

        $session->setState('scheduled_at', $parsed['iso']);
        $session->setState('scheduled_display', $parsed['display']);
        $session->setState('step', self::STEP_CONFIRM);

        $summary = $this->buildBookingSummary($session);

        return [
            'messages' => [$summary, $isArabic ? 'هل تريد تأكيد الحجز؟' : 'Confirm this booking?'],
            'quick_replies' => $isArabic ? ['نعم، تأكيد', 'لا، تعديل'] : ['Yes, confirm', 'No, modify'],
            'current_step' => self::STEP_CONFIRM,
            'action' => 'confirm',
        ];
    }

    /**
     * Handle confirmation.
     */
    protected function handleConfirmation(ChatbotSession $session, string $message): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        $msgLower = mb_strtolower($this->normalizeArabic($message));

        $isYes = str_contains($msgLower, 'yes') || str_contains($msgLower, 'نعم') 
              || str_contains($msgLower, 'confirm') || str_contains($msgLower, 'تاكيد')
              || str_contains($msgLower, 'ok') || str_contains($msgLower, 'اوك')
              || str_contains($msgLower, 'موافق');

        $isNo = str_contains($msgLower, 'no') || str_contains($msgLower, 'لا')
             || str_contains($msgLower, 'cancel') || str_contains($msgLower, 'الغاء')
             || str_contains($msgLower, 'تعديل');

        if ($isNo) {
            $session->setState('step', self::STEP_SYMPTOMS);
            return [
                'messages' => [$isArabic ? 'تم إلغاء الحجز. كيف يمكنني مساعدتك؟' : 'Booking cancelled. How can I help?'],
                'quick_replies' => [],
                'current_step' => self::STEP_SYMPTOMS,
            ];
        }

        if (!$isYes) {
            return [
                'messages' => [$isArabic ? 'يرجى تأكيد أو إلغاء الحجز:' : 'Please confirm or cancel:'],
                'quick_replies' => $isArabic ? ['نعم، تأكيد', 'لا، إلغاء'] : ['Yes, confirm', 'No, cancel'],
                'current_step' => self::STEP_CONFIRM,
            ];
        }

        try {
            $ticket = $this->createTicket($session);
            $session->setState('step', self::STEP_COMPLETE);
            $session->setState('ticket_id', $ticket->id);

            return [
                'messages' => [$isArabic
                    ? "✅ تم إنشاء الحجز بنجاح!\n\n🎫 رقم التذكرة: #{$ticket->id}\n\nشكراً لاستخدامك خدماتنا!"
                    : "✅ Booking created!\n\n🎫 Ticket ID: #{$ticket->id}\n\nThank you!"],
                'quick_replies' => [],
                'current_step' => self::STEP_COMPLETE,
                'ticket_id' => $ticket->id,
                'action' => 'ticket_created',
            ];
        } catch (\Throwable $e) {
            Log::error('Ticket creation failed', ['error' => $e->getMessage()]);
            return [
                'messages' => [$isArabic
                    ? '❌ حدث خطأ. يرجى المحاولة مرة أخرى.'
                    : '❌ Error occurred. Please try again.'],
                'quick_replies' => $isArabic ? ['حاول مرة أخرى'] : ['Try again'],
                'current_step' => self::STEP_CONFIRM,
            ];
        }
    }

    /**
     * Use Gemini AI for natural language understanding.
     */
    protected function analyzeWithAI(string $message, string $locale, array $departments, array $history): array
    {
        $isArabic = $locale === 'ar';
        $normalizedMsg = $this->normalizeArabic($message);
        $deptList = collect($departments)->pluck('name', 'slug')->toJson(JSON_UNESCAPED_UNICODE);

        Log::debug('ChatbotAI analyzing', [
            'ai_enabled' => $this->aiEnabled,
            'has_api_key' => !empty($this->geminiApiKey),
            'message_preview' => Str::limit($message, 30),
            'history_count' => count($history),
            'locale' => $locale,
        ]);

        if (!$this->aiEnabled || empty($this->geminiApiKey)) {
            Log::info('Using FALLBACK (rule-based)', [
                'reason' => !$this->aiEnabled ? 'AI disabled' : 'No API key',
            ]);
            return $this->analyzeWithRules($normalizedMsg, $message, $locale, $departments);
        }

        $aiStart = microtime(true);
        
        try {
            $lang = $isArabic ? 'Arabic' : 'English';
            
            // UPGRADED SYSTEM PROMPT: Conversational + Safe + Context-Aware
            $systemPrompt = <<<PROMPT
You are "Masar", a warm, professional hospital assistant for Masar Healthcare System.

PERSONA:
- Warm, calm, empathetic, and helpful
- Bilingual: respond in {$lang} ONLY
- Healthcare-support tone (not clinical, not casual)
- Use emojis sparingly (1-2 per message) for warmth

AVAILABLE DEPARTMENTS (slug => name):
{$deptList}

BEHAVIOR RULES:
1. Read the full conversation history below before responding
2. NEVER repeat a question the patient already answered in conversation
3. When symptoms are vague, ask 1 clarifying question at a time
4. Before moving steps, briefly acknowledge what the patient said
5. If multiple symptoms mentioned, pick the most relevant 1-2 departments
6. Offer next-step choices when appropriate

RESPONSE TYPES:
- "greeting": patient says hello/hi → welcome warmly, ask how you can help
- "symptom": patient describes symptoms → suggest 1-3 relevant departments
- "unclear": too vague → ask one clarifying question politely
- "faq": general hospital question → answer if you know, else say you'll connect them
- "emergency": severe symptoms detected → immediately escalate

EMERGENCY DETECTION (ALWAYS escalate these):
Chest pain + breathing difficulty, severe bleeding, loss of consciousness, 
stroke symptoms (sudden weakness, speech problems), severe allergic reaction,
ألم صدر شديد، نزيف شديد، فقدان الوعي، صعوبة في التنفس، جلطة

MEDICAL SAFETY (CRITICAL):
- NEVER diagnose ("you have X")
- NEVER prescribe medications
- NEVER give dosage instructions
- Phrase advice as: "based on what you're describing, the {dept} department would be appropriate"
- For concerning symptoms: "I recommend seeking prompt medical attention"

RESPONSE FORMAT (JSON only):
{
  "intent": "greeting" | "symptom" | "unclear" | "faq" | "emergency",
  "reply": "your conversational response in {$lang}",
  "departments": ["slug1", "slug2"],
  "quick_replies": ["optional", "helpful", "suggestions"],
  "safety_flags": {"is_emergency": false, "needs_escalation": false}
}

OUTPUT ONLY VALID JSON. No markdown, no explanation.
PROMPT;

            // RAG: Retrieve and inject relevant hospital knowledge
            $knowledgeSnippets = $this->knowledgeService->retrieve($message, $locale);
            if (!empty($knowledgeSnippets)) {
                $knowledgeContext = $this->knowledgeService->formatForContext($knowledgeSnippets, $locale);
                $systemPrompt .= "\n\n" . $knowledgeContext;
                
                Log::debug('RAG knowledge injected', [
                    'snippets_count' => count($knowledgeSnippets),
                    'types' => array_column($knowledgeSnippets, 'type'),
                ]);
            }

            $url = "{$this->geminiEndpoint}/{$this->geminiModel}:generateContent?key={$this->geminiApiKey}";

            
            // BUILD CONVERSATION CONTENTS: Real history + Current Message only
            // NO synthetic/fabricated model responses
            $contents = [];
            
            // 1. Include actual conversation history (last 20 turns, token-aware)
            $historyTokenEstimate = 0;
            $maxHistoryTokens = 3000; // Reserve tokens for history
            $historyMessages = [];
            
            $recentHistory = array_slice($history, -20);
            
            foreach ($recentHistory as $msg) {
                $role = $msg['role'] === 'user' ? 'user' : 'model';
                $text = $msg['content'] ?? '';
                
                // Skip empty messages
                if (empty(trim($text))) {
                    continue;
                }
                
                // Rough token estimate (1 token ≈ 4 chars)
                $historyTokenEstimate += strlen($text) / 4;
                
                if ($historyTokenEstimate > $maxHistoryTokens) {
                    Log::debug('History truncated due to token limit', ['included' => count($historyMessages)]);
                    break;
                }
                
                $historyMessages[] = ['role' => $role, 'parts' => [['text' => $text]]];
            }
            
            // Add history to contents (real messages only)
            foreach ($historyMessages as $histMsg) {
                $contents[] = $histMsg;
            }
            
            // 2. Current user message
            $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];
            
            // SECURITY: Log only metadata, not message content (PII/PHI protection)
            Log::debug('Gemini request prepared', [
                'total_messages' => count($contents),
                'history_included' => count($historyMessages),
                'current_msg_length' => strlen($message),
            ]);
            
            $response = Http::connectTimeout(self::CONNECT_TIMEOUT)
                ->timeout(self::REQUEST_TIMEOUT)
                ->post($url, [
                    // Use proper system_instruction field (Gemini API standard)
                    'system_instruction' => [
                        'parts' => [['text' => $systemPrompt]]
                    ],
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => $isArabic ? 0.6 : 0.7, // Higher for natural responses
                        'topP' => 0.9,
                        'topK' => 40,
                        'maxOutputTokens' => 800,
                    ],
                    'safetySettings' => [
                        ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_ONLY_HIGH'],
                        ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_ONLY_HIGH'],
                    ],
                ]);

            $aiDuration = round((microtime(true) - $aiStart) * 1000);

            if (!$response->successful()) {
                Log::warning('Gemini API error', [
                    'status' => $response->status(),
                    'body' => Str::limit($response->body(), 200),
                    'duration_ms' => $aiDuration,
                ]);
                throw new \RuntimeException('API error: ' . $response->status());
            }

            $text = $response->json('candidates.0.content.parts.0.text') ?? '';
            $text = preg_replace('/^```json\s*/i', '', trim($text));
            $text = preg_replace('/\s*```$/i', '', $text);
            
            $parsed = json_decode($text, true);
            
            if (json_last_error() !== JSON_ERROR_NONE || !isset($parsed['intent'])) {
                // SECURITY: Don't log raw AI response (may contain patient info)
                Log::warning('Gemini returned invalid JSON', [
                    'json_error' => json_last_error_msg(),
                    'response_length' => strlen($text),
                ]);
                throw new \RuntimeException('Invalid JSON');
            }

            // Handle emergency escalation
            if (($parsed['intent'] ?? '') === 'emergency' || ($parsed['safety_flags']['is_emergency'] ?? false)) {
                $parsed['intent'] = 'emergency';
                $parsed['is_emergency'] = true;
                $this->metricsService->recordEmergency();
            }

            Log::info('Gemini AI SUCCESS', [
                'duration_ms' => $aiDuration,
                'intent' => $parsed['intent'],
                'departments' => $parsed['departments'] ?? [],
                'history_used' => count($historyMessages),
            ]);
            
            // Record success metrics
            $this->metricsService->recordAIRequest(true, $aiDuration, true);

            return $parsed;

        } catch (\Throwable $e) {
            $aiDuration = round((microtime(true) - $aiStart) * 1000);
            Log::warning('Gemini FAILED, using fallback', [
                'error' => $e->getMessage(),
                'duration_ms' => $aiDuration,
            ]);
            
            // Record failure metrics
            $this->metricsService->recordAIRequest(true, $aiDuration, false);
        }

        // Fallback to rule-based
        $this->metricsService->recordAIRequest(false, 0, true);
        return $this->analyzeWithRules($normalizedMsg, $message, $locale, $departments);
    }

    /**
     * Rule-based fallback with comprehensive Arabic keyword support.
     */
    protected function analyzeWithRules(string $normalizedMsg, string $originalMsg, string $locale, array $departments): array
    {
        $isArabic = $locale === 'ar';
        $msgLower = mb_strtolower($normalizedMsg);

        // SECURITY: Don't log message content (PII/PHI)
        Log::debug('Using rule-based analysis', ['msg_length' => strlen($msgLower)]);

        // Greetings (normalized Arabic)
        $greetings = ['hi', 'hello', 'hey', 'morning', 'evening', 'السلام', 'مرحبا', 'اهلا', 'صباح', 'مساء', 'هلا', 'هاي'];
        foreach ($greetings as $g) {
            if (str_contains($msgLower, $g)) {
                return [
                    'intent' => 'greeting',
                    'reply' => $isArabic
                        ? 'أهلاً وسهلاً! 👋 أنا مساعدك الطبي. كيف يمكنني مساعدتك؟ صف لي أعراضك.'
                        : "Hello! 👋 I'm your medical assistant. How can I help? Describe your symptoms.",
                    'departments' => [],
                    'quick_replies' => [],
                ];
            }
        }

        // Comprehensive symptom keywords (Arabic normalized + English)
        $keywords = [
            // GI / Stomach - CRITICAL FIX
            'stomach' => 'gastroenterology', 'gastro' => 'gastroenterology', 'nausea' => 'gastroenterology',
            'vomit' => 'gastroenterology', 'diarrhea' => 'gastroenterology', 'constipation' => 'gastroenterology',
            'abdomen' => 'gastroenterology', 'belly' => 'gastroenterology', 'digest' => 'gastroenterology',
            'معده' => 'gastroenterology', 'معدة' => 'gastroenterology', 'بطن' => 'gastroenterology', 'بطني' => 'gastroenterology',
            'مغص' => 'gastroenterology', 'غثيان' => 'gastroenterology', 'قيء' => 'gastroenterology', 'تقيا' => 'gastroenterology',
            'اسهال' => 'gastroenterology', 'امساك' => 'gastroenterology', 'حموضه' => 'gastroenterology', 'حموضة' => 'gastroenterology',
            'انتفاخ' => 'gastroenterology', 'هضم' => 'gastroenterology', 'توعك' => 'gastroenterology',
            
            // Cardiology
            'heart' => 'cardiology', 'chest' => 'cardiology', 'cardiac' => 'cardiology', 'palpitation' => 'cardiology',
            'قلب' => 'cardiology', 'صدر' => 'cardiology', 'خفقان' => 'cardiology', 'ضغط' => 'cardiology',
            
            // Neurology
            'head' => 'neurology', 'brain' => 'neurology', 'headache' => 'neurology', 'migraine' => 'neurology',
            'dizzy' => 'neurology', 'vertigo' => 'neurology', 'seizure' => 'neurology',
            'راس' => 'neurology', 'صداع' => 'neurology', 'دوخه' => 'neurology', 'دوار' => 'neurology',
            'مخ' => 'neurology', 'عصب' => 'neurology',
            
            // Orthopedics
            'bone' => 'orthopedics', 'joint' => 'orthopedics', 'fracture' => 'orthopedics', 'muscle' => 'orthopedics',
            'back' => 'orthopedics', 'knee' => 'orthopedics', 'spine' => 'orthopedics',
            'عظام' => 'orthopedics', 'عظم' => 'orthopedics', 'مفصل' => 'orthopedics', 'ظهر' => 'orthopedics',
            'ركبه' => 'orthopedics', 'كسر' => 'orthopedics', 'عضل' => 'orthopedics',
            
            // Ophthalmology
            'eye' => 'ophthalmology', 'vision' => 'ophthalmology', 'blind' => 'ophthalmology', 'sight' => 'ophthalmology',
            'عين' => 'ophthalmology', 'نظر' => 'ophthalmology', 'رؤيه' => 'ophthalmology', 'بصر' => 'ophthalmology',
            
            // Dermatology
            'skin' => 'dermatology', 'rash' => 'dermatology', 'acne' => 'dermatology', 'itch' => 'dermatology',
            'جلد' => 'dermatology', 'طفح' => 'dermatology', 'حكه' => 'dermatology', 'بثور' => 'dermatology',
            
            // ENT
            'ear' => 'ent', 'throat' => 'ent', 'nose' => 'ent', 'sinus' => 'ent', 'hearing' => 'ent',
            'اذن' => 'ent', 'حلق' => 'ent', 'انف' => 'ent', 'سمع' => 'ent', 'جيوب' => 'ent',
            
            // Pediatrics
            'child' => 'pediatrics', 'baby' => 'pediatrics', 'infant' => 'pediatrics', 'kid' => 'pediatrics',
            'طفل' => 'pediatrics', 'اطفال' => 'pediatrics', 'رضيع' => 'pediatrics', 'مولود' => 'pediatrics',
            
            // Internal Medicine (general)
            'fever' => 'internal-medicine', 'cough' => 'internal-medicine', 'cold' => 'internal-medicine',
            'flu' => 'internal-medicine', 'tired' => 'internal-medicine', 'fatigue' => 'internal-medicine',
            'حراره' => 'internal-medicine', 'سعال' => 'internal-medicine', 'كحه' => 'internal-medicine',
            'زكام' => 'internal-medicine', 'انفلونزا' => 'internal-medicine', 'تعب' => 'internal-medicine',
            
            // Pulmonology
            'breathing' => 'pulmonology', 'lung' => 'pulmonology', 'asthma' => 'pulmonology', 'shortness' => 'pulmonology',
            'تنفس' => 'pulmonology', 'رئه' => 'pulmonology', 'ربو' => 'pulmonology', 'ضيق' => 'pulmonology',
            
            // Emergency
            'emergency' => 'emergency', 'urgent' => 'emergency', 'accident' => 'emergency', 'bleeding' => 'emergency',
            'طوارئ' => 'emergency', 'اسعاف' => 'emergency', 'نزيف' => 'emergency', 'حادث' => 'emergency',
        ];

        $matched = [];
        foreach ($keywords as $kw => $slug) {
            if (str_contains($msgLower, $kw) && !in_array($slug, $matched)) {
                $matched[] = $slug;
            }
        }

        if (!empty($matched)) {
            // Verify matched slugs exist in DB
            $validSlugs = collect($departments)->pluck('slug')->toArray();
            $matched = array_filter($matched, fn($s) => in_array($s, $validSlugs));
            
            // Fallback to internal-medicine if no valid match
            if (empty($matched) && in_array('internal-medicine', $validSlugs)) {
                $matched = ['internal-medicine'];
            }

            return [
                'intent' => 'symptom',
                'reply' => $isArabic
                    ? 'بناءً على أعراضك، أقترح القسم(الأقسام) التالية. اختر للمتابعة:'
                    : 'Based on your symptoms, I suggest these department(s). Select to continue:',
                'departments' => array_slice($matched, 0, 3),
                'quick_replies' => [],
            ];
        }

        // Unclear - ask clarifying question
        return [
            'intent' => 'unclear',
            'reply' => $isArabic
                ? 'هل يمكنك وصف أعراضك بشكل أوضح؟ أين تشعر بالألم أو عدم الراحة؟'
                : 'Can you describe your symptoms more clearly? Where do you feel pain or discomfort?',
            'departments' => [],
            'quick_replies' => $isArabic
                ? ['ألم في المعدة', 'صداع', 'ألم في الصدر', 'مشاكل في الجلد']
                : ['Stomach pain', 'Headache', 'Chest pain', 'Skin problems'],
        ];
    }

    // Helper methods

    protected function getCachedDepartments(string $locale): array
    {
        return Cache::remember("chatbot_depts_{$locale}", self::CACHE_TTL, function () use ($locale) {
            return Department::where('is_active', true)
                ->get(['id', 'slug', 'name_en', 'name_ar'])
                ->map(fn($d) => [
                    'id' => $d->id,
                    'slug' => $d->slug,
                    'name' => $locale === 'ar' ? $d->name_ar : $d->name_en,
                    'name_en' => $d->name_en,
                    'name_ar' => $d->name_ar,
                ])
                ->toArray();
        });
    }

    protected function mapDepartmentsToReal(array $slugs, array $departments): array
    {
        $deptBySlugs = collect($departments)->keyBy('slug');
        $result = [];
        foreach ($slugs as $slug) {
            if (isset($deptBySlugs[$slug])) {
                $result[] = $deptBySlugs[$slug];
            }
        }
        return $result;
    }

    protected function matchDepartmentFromInput(string $input, array $departments): ?array
    {
        $inputLower = mb_strtolower($this->normalizeArabic($input));
        
        foreach ($departments as $dept) {
            $deptNameNorm = mb_strtolower($this->normalizeArabic($dept['name']));
            $deptSlug = mb_strtolower($dept['slug']);
            
            if ($deptNameNorm === $inputLower 
                || $deptSlug === $inputLower
                || (string)$dept['id'] === $input
                || str_contains($inputLower, $deptNameNorm)
                || str_contains($deptNameNorm, $inputLower)) {
                return $dept;
            }
        }
        return null;
    }

    protected function getDoctorsForDepartment(string $departmentId): array
    {
        return User::role('doctor')
            ->where('department_id', $departmentId)
            ->select(['id', 'name'])
            ->get()
            ->toArray();
    }

    protected function matchDoctorFromInput(string $input, array $doctors): ?array
    {
        $inputLower = mb_strtolower($input);
        foreach ($doctors as $doc) {
            if ((string)$doc['id'] === $input || str_contains($inputLower, mb_strtolower($doc['name']))) {
                return $doc;
            }
        }
        return null;
    }

    protected function getTimeSlotQuickReplies(string $locale): array
    {
        return $locale === 'ar'
            ? ['غداً 9:00 صباحاً', 'غداً 2:00 مساءً', 'بعد غد 10:00 صباحاً']
            : ['Tomorrow 9:00 AM', 'Tomorrow 2:00 PM', 'Day after 10:00 AM'];
    }

    protected function parseDateTimeFromMessage(string $message, string $locale): ?array
    {
        $now = now('Asia/Riyadh');
        $msgLower = mb_strtolower($this->normalizeArabic($message));
        
        $date = null;
        $hour = 9;

        if (str_contains($msgLower, 'tomorrow') || str_contains($msgLower, 'غدا')) {
            $date = $now->copy()->addDay();
        } elseif (str_contains($msgLower, 'day after') || str_contains($msgLower, 'بعد غد')) {
            $date = $now->copy()->addDays(2);
        } else {
            $date = $now->copy()->addDay();
        }

        if (preg_match('/(\d{1,2})(?::(\d{2}))?\s*(am|pm|صباح|مساء)?/i', $msgLower, $matches)) {
            $hour = (int)$matches[1];
            $isPM = isset($matches[3]) && (strtolower($matches[3]) === 'pm' || str_contains($matches[3], 'مساء'));
            if ($isPM && $hour < 12) $hour += 12;
            if (!$isPM && $hour === 12) $hour = 0;
        }

        $date->setTime($hour, 0);

        $displayFormat = $locale === 'ar' 
            ? $date->translatedFormat('l j F Y - g:i A')
            : $date->format('l, F j, Y - g:i A');

        return [
            'iso' => $date->toIso8601String(),
            'display' => $displayFormat . ' (Makkah)',
        ];
    }

    protected function buildBookingSummary(ChatbotSession $session): string
    {
        $isArabic = $session->locale === 'ar';
        
        $dept = $session->getState('department_name', '-');
        $doctor = $session->getState('doctor_name', $isArabic ? 'سيتم التعيين' : 'To be assigned');
        $datetime = $session->getState('scheduled_display', '-');
        $symptoms = Str::limit($session->getState('symptoms', '-'), 100);

        if ($isArabic) {
            return "📋 ملخص الحجز:\n\n🏥 القسم: {$dept}\n👨‍⚕️ الطبيب: {$doctor}\n📅 الموعد: {$datetime}\n📝 الأعراض: {$symptoms}";
        }

        return "📋 Booking Summary:\n\n🏥 Department: {$dept}\n👨‍⚕️ Doctor: {$doctor}\n📅 Date/Time: {$datetime}\n📝 Symptoms: {$symptoms}";
    }

    protected function createTicket(ChatbotSession $session): Ticket
    {
        $paymentMethod = $session->getState('payment_method', 'pay_at_hospital');
        $isEmergency = $session->getState('is_emergency', false);
        
        // Determine status based on payment method
        // Pay Now → assigned (immediate processing)
        // Pay at Hospital → awaiting_payment (Reception must confirm)
        $status = match($paymentMethod) {
            'online' => 'assigned',
            default => 'awaiting_payment',
        };
        
        // Set priority based on emergency flag
        $priority = $isEmergency ? 'urgent' : 'medium';
        
        $ticket = Ticket::create([
            'patient_id' => $session->user_id,
            'creator_id' => $session->user_id,
            'department_id' => $session->getState('department_id'),
            'assigned_to' => $session->getState('doctor_id'),
            'type' => 'appointment',
            'status' => $status,
            'priority' => $priority,
            'source' => 'chatbot', // CRITICAL: Mark as chatbot-created ticket
            'subject' => ($session->locale === 'ar' ? 'حجز عبر المساعد الآلي: ' : 'Chatbot Booking: ') . Str::limit($session->getState('symptoms', 'Appointment'), 50),
            'description' => $session->getState('symptoms'),
            'scheduled_at' => $session->getState('scheduled_at'),
            // Patient Info fields (Step 3)
            'patient_age' => $session->getState('patient_age'),
            'patient_gender' => $session->getState('patient_gender'),
            'contact_method' => 'in_app',
            'contact_phone' => $session->getState('contact_phone'),
            'is_emergency' => $isEmergency,
            'medical_conditions' => $session->getState('medical_conditions'),
            'additional_notes' => $session->getState('additional_notes'),
        ]);
        
        // Create audit event
        \App\Models\TicketEvent::create([
            'ticket_id' => $ticket->id,
            'user_id' => $session->user_id,
            'event_type' => 'created',
            'meta' => [
                'source' => 'chatbot',
                'payment_method' => $paymentMethod,
                'is_emergency' => $isEmergency,
                'locale' => $session->locale,
            ],
        ]);
        
        return $ticket;
    }

    protected function buildErrorResponse(ChatbotSession $session): array
    {
        $isArabic = $session->locale === 'ar';
        return [
            'messages' => [$isArabic ? 'عذراً، حدث خطأ. يرجى المحاولة مرة أخرى.' : 'Sorry, an error occurred. Please try again.'],
            'quick_replies' => [],
            'suggestions' => [],
        ];
    }
}
