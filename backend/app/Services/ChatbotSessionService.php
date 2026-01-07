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

    // Conversation steps - STRICT 5-STEP BOOKING FLOW
    const STEP_INITIAL = 'initial';
    const STEP_SYMPTOMS = 'symptoms';
    const STEP_DEPARTMENT = 'department';      // Step 1: Department Selection
    const STEP_DOCTOR = 'doctor';              // Step 2: Doctor Selection
    const STEP_PATIENT_INFO = 'patient_info';  // Step 3: Patient Information (hybrid)
    const STEP_PAYMENT = 'payment';            // Step 4: Payment Method (MANDATORY)
    const STEP_CONFIRM = 'confirm';            // Step 5: Summary & Confirmation
    const STEP_COMPLETE = 'complete';
    
    // Emergency red-flag symptoms
    const EMERGENCY_KEYWORDS_EN = ['chest pain', 'can\'t breathe', 'unconscious', 'heavy bleeding', 'stroke', 'heart attack', 'severe pain'];
    const EMERGENCY_KEYWORDS_AR = ['ألم صدر', 'لا أستطيع التنفس', 'إغماء', 'نزيف شديد', 'جلطة', 'نوبة قلبية', 'ألم شديد'];

    public function __construct()
    {
        $this->aiEnabled = (bool) config('services.chatbot.ai_enabled', false);
        $this->geminiApiKey = config('services.gemini.api_key');
        $this->geminiModel = config('services.gemini.model', 'gemini-2.0-flash');
        $this->geminiEndpoint = config('services.gemini.endpoint', 'https://generativelanguage.googleapis.com/v1beta/models');
        
        // Debug log configuration on construction
        Log::debug('ChatbotSessionService initialized', [
            'ai_enabled' => $this->aiEnabled,
            'api_key_set' => !empty($this->geminiApiKey),
            'api_key_length' => strlen($this->geminiApiKey ?? ''),
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
     * Process a user message and return bot response.
     */
    public function processMessage(ChatbotSession $session, string $message): array
    {
        $startTime = microtime(true);
        $locale = $session->locale;

        // Add user message to history
        $session->addMessage('user', $message);

        $step = $session->getState('step', self::STEP_INITIAL);

        try {
            $response = match ($step) {
                self::STEP_INITIAL, self::STEP_SYMPTOMS => $this->handleSymptomStep($session, $message),
                self::STEP_DEPARTMENT => $this->handleDepartmentSelection($session, $message),
                self::STEP_DOCTOR => $this->handleDoctorSelection($session, $message),
                self::STEP_PATIENT_INFO => $this->handlePatientInfoStep($session, $message),
                self::STEP_PAYMENT => $this->handlePaymentStep($session, $message),
                self::STEP_CONFIRM => $this->handleConfirmation($session, $message),
                default => $this->handleSymptomStep($session, $message),
            };
        } catch (\Throwable $e) {
            Log::error('Chatbot error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $response = $this->buildErrorResponse($session);
        }

        foreach ($response['messages'] ?? [] as $msg) {
            $session->addMessage('assistant', $msg);
        }

        $session->save();

        $duration = round((microtime(true) - $startTime) * 1000);
        Log::info('Chatbot message processed', [
            'session_id' => $session->id,
            'step' => $session->getState('step'),
            'duration_ms' => $duration,
            'message_preview' => Str::limit($message, 50),
        ]);

        return array_merge($response, [
            'session_id' => $session->id,
            '_timing_ms' => $duration,
        ]);
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
     * Handle department selection.
     */
    protected function handleDepartmentSelection(ChatbotSession $session, string $message): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        $departments = $this->getCachedDepartments($locale);

        $selectedDept = $this->matchDepartmentFromInput($message, $departments);

        if (!$selectedDept) {
            $suggestions = $this->mapDepartmentsToReal(
                $session->getState('suggested_departments', []),
                $departments
            );

            return [
                'messages' => [$isArabic 
                    ? 'لم أتمكن من تحديد القسم. يرجى اختيار من القائمة:'
                    : "I couldn't identify your selection. Please choose from the list:"],
                'suggestions' => $suggestions,
                'current_step' => self::STEP_DEPARTMENT,
                'action' => 'select_department',
            ];
        }

        $session->setState('department_id', $selectedDept['id']);
        $session->setState('department_name', $selectedDept['name']);
        $session->setState('step', self::STEP_DOCTOR);

        $doctors = $this->getDoctorsForDepartment($selectedDept['id']);

        if (empty($doctors)) {
            $session->setState('step', self::STEP_DATETIME);
            $session->setState('doctor_id', null);
            
            return [
                'messages' => [$isArabic
                    ? "تم اختيار قسم {$selectedDept['name']}. لا يوجد أطباء متاحين حالياً، لكن يمكنك الحجز وسيتم تعيين طبيب لاحقاً.\n\nما هو الموعد المناسب لك؟"
                    : "You've selected {$selectedDept['name']}. No doctors available now, but you can book and a doctor will be assigned.\n\nWhat date and time works for you?"],
                'quick_replies' => $this->getTimeSlotQuickReplies($locale),
                'current_step' => self::STEP_DATETIME,
            ];
        }

        return [
            'messages' => [$isArabic
                ? "تم اختيار قسم {$selectedDept['name']}. يرجى اختيار الطبيب:"
                : "You've selected {$selectedDept['name']}. Please choose a doctor:"],
            'doctors' => array_map(fn($d) => ['id' => $d['id'], 'name' => $d['name']], $doctors),
            'current_step' => self::STEP_DOCTOR,
            'action' => 'select_doctor',
        ];
    }

    /**
     * Handle doctor selection.
     */
    protected function handleDoctorSelection(ChatbotSession $session, string $message): array
    {
        $locale = $session->locale;
        $isArabic = $locale === 'ar';
        $departmentId = $session->getState('department_id');
        $doctors = $this->getDoctorsForDepartment($departmentId);

        $selectedDoctor = $this->matchDoctorFromInput($message, $doctors);

        if (!$selectedDoctor && !empty($doctors)) {
            return [
                'messages' => [$isArabic ? 'يرجى اختيار طبيب من القائمة:' : 'Please select a doctor:'],
                'doctors' => array_map(fn($d) => ['id' => $d['id'], 'name' => $d['name']], $doctors),
                'current_step' => self::STEP_DOCTOR,
                'action' => 'select_doctor',
            ];
        }

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
            
            $message = "تم اختيار الدكتور {$docName}.\n\n📋 معلومات المريض:\n{$prefilledInfo}\nيرجى إكمال المعلومات التالية أو تأكيدها:";
            
            return [
                'messages' => [$message],
                'current_step' => self::STEP_PATIENT_INFO,
                'step_number' => 3,
                'step_total' => 5,
                'action' => 'collect_patient_info',
                'prefilled' => [
                    'name' => $prefillName,
                    'phone' => $prefillPhone,
                ],
                'required_fields' => ['age', 'gender'],
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
            'messages' => ["You've selected Dr. {$docName}.\n\n📋 Patient Information:\n{$prefilledInfo}\nPlease complete or confirm the following:"],
            'current_step' => self::STEP_PATIENT_INFO,
            'step_number' => 3,
            'step_total' => 5,
            'action' => 'collect_patient_info',
            'prefilled' => [
                'name' => $prefillName,
                'phone' => $prefillPhone,
            ],
            'required_fields' => ['age', 'gender'],
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
            'normalized_preview' => Str::limit($normalizedMsg, 30),
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
            $prompt = <<<PROMPT
You are a hospital booking assistant. Analyze the patient's message.

Available departments (slug => name): {$deptList}

RULES:
1. Respond ONLY in {$lang}
2. For greetings (hi/hello/السلام/اهلا/مرحبا): welcome warmly, ask about symptoms
3. For symptoms: suggest 1-3 departments from the list (use exact slugs)
4. Be warm but concise

IMPORTANT symptom mappings:
- Stomach/معدة/بطن/مغص/غثيان/قيء/اسهال/امساك/حموضة → gastroenterology or internal-medicine
- Heart/chest/قلب/صدر → cardiology
- Head/brain/headache/صداع/رأس/دوخة → neurology
- Bone/joint/عظام/مفصل/ظهر → orthopedics
- Eye/عين/نظر → ophthalmology
- Skin/جلد/طفح/حكة → dermatology
- Child/طفل/أطفال → pediatrics

Respond with JSON only:
{
  "intent": "greeting" | "symptom" | "unclear",
  "reply": "your conversational response",
  "departments": ["slug1", "slug2"],
  "quick_replies": []
}
PROMPT;

            $url = "{$this->geminiEndpoint}/{$this->geminiModel}:generateContent?key={$this->geminiApiKey}";
            
            $response = Http::connectTimeout(self::CONNECT_TIMEOUT)
                ->timeout(self::REQUEST_TIMEOUT)
                ->post($url, [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $prompt]]],
                        ['role' => 'model', 'parts' => [['text' => '{"intent":"greeting","reply":"مرحبا!","departments":[],"quick_replies":[]}']]],
                        ['role' => 'user', 'parts' => [['text' => "Patient message: \"{$message}\""]]],
                    ],
                    'generationConfig' => ['temperature' => 0.3, 'maxOutputTokens' => 512],
                ]);

            $aiDuration = round((microtime(true) - $aiStart) * 1000);

            if (!$response->successful()) {
                Log::warning('Gemini API error', [
                    'status' => $response->status(),
                    'duration_ms' => $aiDuration,
                ]);
                throw new \RuntimeException('API error: ' . $response->status());
            }

            $text = $response->json('candidates.0.content.parts.0.text') ?? '';
            $text = preg_replace('/^```json\s*/i', '', trim($text));
            $text = preg_replace('/\s*```$/i', '', $text);
            
            $parsed = json_decode($text, true);
            
            if (json_last_error() !== JSON_ERROR_NONE || !isset($parsed['intent'])) {
                Log::warning('Gemini returned invalid JSON', ['raw' => Str::limit($text, 200)]);
                throw new \RuntimeException('Invalid JSON');
            }

            Log::info('Gemini AI SUCCESS', [
                'duration_ms' => $aiDuration,
                'intent' => $parsed['intent'],
                'departments' => $parsed['departments'] ?? [],
            ]);

            return $parsed;

        } catch (\Throwable $e) {
            $aiDuration = round((microtime(true) - $aiStart) * 1000);
            Log::warning('Gemini FAILED, using fallback', [
                'error' => $e->getMessage(),
                'duration_ms' => $aiDuration,
            ]);
        }

        return $this->analyzeWithRules($normalizedMsg, $message, $locale, $departments);
    }

    /**
     * Rule-based fallback with comprehensive Arabic keyword support.
     */
    protected function analyzeWithRules(string $normalizedMsg, string $originalMsg, string $locale, array $departments): array
    {
        $isArabic = $locale === 'ar';
        $msgLower = mb_strtolower($normalizedMsg);

        Log::debug('Using rule-based analysis', ['msg' => Str::limit($msgLower, 50)]);

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
