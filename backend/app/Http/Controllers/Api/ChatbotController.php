<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatbotSessionService;
use App\Services\ChatbotMetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    protected ChatbotSessionService $sessionService;
    protected ChatbotMetricsService $metricsService;

    public function __construct(ChatbotSessionService $sessionService, ChatbotMetricsService $metricsService)
    {
        $this->sessionService = $sessionService;
        $this->metricsService = $metricsService;
    }

    /**
     * Start/init a new chat session (returns session_id and initial state).
     * This endpoint MUST always return 200 - never crash.
     */
    public function start(Request $request)
    {
        $requestId = uniqid('req_');
        
        try {
            $locale = $request->input('locale', $request->header('Accept-Language', 'en'));
            $locale = str_starts_with($locale, 'ar') ? 'ar' : 'en';
            $userId = $request->user()?->id;

            // Create new session
            $session = $this->sessionService->getSession(null, $userId, $locale);

            // Return session info with welcome message
            $welcomeResponse = $this->sessionService->getWelcomeMessage($session);
            
            return response()->json([
                'request_id' => $requestId,
                'session_id' => $session->id,
                'mode' => 'qa',
                'step' => 'initial',
                'locale' => $locale,
                'messages' => $welcomeResponse['messages'] ?? [],
                'reply' => implode("\n\n", $welcomeResponse['messages'] ?? []),
                'intent' => 'greeting',
                'handler' => 'init',
                'provider' => 'local',
                'ui' => ['type' => 'none', 'items' => []],
                '_ai_status' => $this->getAIStatusLabel(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[Chatbot Controller] Init failed', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);
            
            // Return safe error response (never 500)
            return response()->json([
                'request_id' => $requestId,
                'session_id' => null,
                'mode' => 'qa',
                'step' => 'initial',
                'reply' => 'مرحبا! كيف يمكنني مساعدتك؟',
                'intent' => 'error',
                'handler' => 'init',
                'provider' => 'local',
                'ui' => ['type' => 'none', 'items' => []],
                'error_code' => 'INIT_FAILED',
            ], 200); // Return 200 so frontend doesn't crash
        }
    }
    
    /**
     * Alias for start() - for frontend convenience.
     */
    public function init(Request $request)
    {
        return $this->start($request);
    }

    /**
     * Process a chat message (stateful conversation).
     * Supports both text messages and action-based quick replies.
     */
    public function message(Request $request)
    {
        $requestId = uniqid('req_');
        
        try {
            // Validate: message OR action required
            $request->validate([
                'message' => 'nullable|string|max:1000',
                'action' => 'nullable|string|max:50',
                'session_id' => 'nullable|string|max:64',
                'locale' => 'nullable|in:en,ar',
            ]);
            
            // Must have either message or action
            if (empty($request->input('message')) && empty($request->input('action'))) {
                return response()->json([
                    'request_id' => $requestId,
                    'error_code' => 'MISSING_INPUT',
                    'error_message' => 'Either message or action is required',
                ], 422);
            }

            $message = $request->input('message', '');
            $action = $request->input('action');
            $sessionId = $request->input('session_id');
            $locale = $request->input('locale', $request->header('Accept-Language', 'en'));
            $locale = str_starts_with($locale, 'ar') ? 'ar' : 'en';
            
            $userId = $request->user()?->id;

            // Get or create session (session_id can be null - will auto-create)
            $session = $this->sessionService->getSession($sessionId, $userId, $locale);

            // === TRACE LOG: Incoming request ===
            Log::info('[Chatbot Controller] Incoming request', [
                'request_id' => $requestId,
                'session_id' => $session->id,
                'message' => mb_substr($message, 0, 100),
                'action' => $action,
                'locale' => $locale,
                'step' => $session->getState('step'),
                'mode' => $session->getState('mode'),
            ]);

            // === ACTION-BASED ROUTING ===
            // Actions bypass text classification and route directly to handlers
            if ($action) {
                $response = $this->sessionService->processAction($session, $action, $locale);
            } else {
                // Process text message
                $response = $this->sessionService->processMessage($session, $message);
            }
            
            // Build complete response with ALL required fields
            return $this->buildSuccessResponse($response, $session, $requestId);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'request_id' => $requestId,
                'session_id' => null,
                'error_code' => 'VALIDATION_ERROR',
                'error_message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('[Chatbot Controller] Unexpected error', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'request_id' => $requestId,
                'session_id' => null,
                'error_code' => 'INTERNAL_ERROR',
                'error_message' => 'An error occurred processing your message. Please try again.',
                'reply' => app()->getLocale() === 'ar' 
                    ? 'حدث خطأ أثناء معالجة رسالتك. يرجى المحاولة مرة أخرى.'
                    : 'An error occurred processing your message. Please try again.',
                'intent' => 'error',
                'handler' => 'errorHandler',
                'provider' => 'local',
                'mode' => 'qa',
                'step' => 'initial',
                'ui' => ['type' => 'none', 'items' => []],
            ], 200); // Return 200 so frontend can show the error message
        }
    }
    
    /**
     * Build a complete success response with ALL required fields.
     */
    protected function buildSuccessResponse(array $response, $session, string $requestId): \Illuminate\Http\JsonResponse
    {
        // Ensure required fields exist
        $intent = $response['intent'] ?? 'unknown';
        $handler = $response['handler'] ?? 'unknown';
        $provider = $response['provider'] ?? 'rules';
        $mode = $response['mode'] ?? 'qa';
        $step = $response['current_step'] ?? $session->getState('step', 'initial');
        $uiType = $response['ui']['type'] ?? 'none';
        
        // === TRACE LOG: Response generated ===
        Log::info('[Chatbot Controller] Response generated', [
            'request_id' => $requestId,
            'session_id' => $session->id,
            'intent' => $intent,
            'mode' => $mode,
            'step' => $step,
            'ui_type' => $uiType,
            'provider' => $provider,
            'handler' => $handler,
        ]);
        
        // Add AI status indicator
        $response['_ai_status'] = $this->getAIStatusLabel();
        
        // CRITICAL: Add ALL required fields directly on response
        $response['session_id'] = $session->id;
        $response['request_id'] = $requestId;
        $response['intent'] = $intent;
        $response['handler'] = $handler;
        $response['provider'] = $provider;
        $response['mode'] = $mode;
        $response['step'] = $step;
        
        // Add reply field (maps from messages array)
        $response['reply'] = implode("\n\n", $response['messages'] ?? ['']);
        
        // Ensure ui field exists
        if (!isset($response['ui'])) {
            $response['ui'] = ['type' => 'none', 'items' => []];
        }
        
        // Add debug object
        $response['debug'] = [
            'request_id' => $requestId,
            'intent' => $intent,
            'handler' => $handler,
            'provider' => $provider,
            'mode' => $mode,
            'step' => $step,
            'ui_type' => $uiType,
        ];
        
        // DEV-only debug panel
        if (config('app.debug')) {
            $response['_debug'] = $this->getDebugInfo($response);
        }

        return response()->json($response);
    }
    
    /**
     * Get debug info for dev mode (no secrets/PII).
     */
    protected function getDebugInfo(array $response): array
    {
        return [
            'model' => config('services.gemini.model', 'gemini-2.0-flash'),
            'ai_enabled' => (bool) config('services.chatbot.ai_enabled', false),
            'used_ai' => $response['_used_ai'] ?? null,
            'latency_ms' => $response['_latency_ms'] ?? null,
            'history_count' => $response['_history_count'] ?? null,
            'intent' => $response['intent'] ?? null,
            'step' => $response['current_step'] ?? null,
        ];
    }

    /**
     * Legacy analyze endpoint (redirects to message).
     */
    public function analyze(Request $request)
    {
        return $this->message($request);
    }

    /**
     * Get current session state.
     */
    public function session(Request $request)
    {
        $sessionId = $request->input('session_id');
        $locale = $request->input('locale', 'en');
        $userId = $request->user()?->id;

        if (!$sessionId) {
            return response()->json(['error' => 'session_id required'], 400);
        }

        $session = $this->sessionService->getSession($sessionId, $userId, $locale);

        return response()->json([
            'session_id' => $session->id,
            'state' => $session->state,
            'messages' => $session->getRecentMessages(20),
            'locale' => $session->locale,
            '_ai_status' => $this->getAIStatusLabel(),
        ]);
    }

    /**
     * Reset/clear session.
     */
    public function reset(Request $request)
    {
        $sessionId = $request->input('session_id');
        
        if ($sessionId) {
            \App\Models\ChatbotSession::where('id', $sessionId)->delete();
        }

        return response()->json(['success' => true]);
    }
    
    /**
     * Get chatbot AI status and metrics (admin endpoint).
     */
    public function status(Request $request)
    {
        return response()->json($this->metricsService->getAIStatus());
    }
    
    /**
     * Get chatbot metrics for a specific date (admin endpoint).
     */
    public function metrics(Request $request)
    {
        $date = $request->input('date', now()->format('Y-m-d'));
        return response()->json($this->metricsService->getMetrics($date));
    }
    
    /**
     * Get AI status label for responses.
     */
    protected function getAIStatusLabel(): string
    {
        $aiEnabled = (bool) config('services.chatbot.ai_enabled', false);
        $apiKeySet = !empty(config('services.gemini.api_key'));
        
        if ($aiEnabled && $apiKeySet) {
            return 'AI: Gemini (ON)';
        } elseif ($aiEnabled) {
            return 'AI: Missing Key';
        }
        return 'Fallback (OFF)';
    }

    /**
     * Quick action: select department.
     */
    public function selectDepartment(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'department_id' => 'required',
        ]);

        $session = $this->sessionService->getSession(
            $request->input('session_id'),
            $request->user()?->id,
            $request->input('locale', 'en')
        );

        // Inject department selection as message
        $response = $this->sessionService->processMessage($session, (string)$request->input('department_id'));
        $response['_ai_status'] = $this->getAIStatusLabel();

        return response()->json($response);
    }

    /**
     * Quick action: select doctor.
     */
    public function selectDoctor(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'doctor_id' => 'required',
        ]);

        $session = $this->sessionService->getSession(
            $request->input('session_id'),
            $request->user()?->id,
            $request->input('locale', 'en')
        );

        $response = $this->sessionService->processMessage($session, (string)$request->input('doctor_id'));
        $response['_ai_status'] = $this->getAIStatusLabel();

        return response()->json($response);
    }

    /**
     * Quick action: select time slot.
     */
    public function selectTime(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'datetime' => 'required|string',
        ]);

        $session = $this->sessionService->getSession(
            $request->input('session_id'),
            $request->user()?->id,
            $request->input('locale', 'en')
        );

        $response = $this->sessionService->processMessage($session, $request->input('datetime'));
        $response['_ai_status'] = $this->getAIStatusLabel();

        return response()->json($response);
    }
}

