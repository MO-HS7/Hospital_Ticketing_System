<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatbotSessionService;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    protected ChatbotSessionService $sessionService;

    public function __construct(ChatbotSessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
     * Start a new chat session (returns language selection).
     */
    public function start(Request $request)
    {
        $locale = $request->input('locale', $request->header('Accept-Language', 'en'));
        $locale = str_starts_with($locale, 'ar') ? 'ar' : 'en';
        $userId = $request->user()?->id;

        // Create new session (always starts at language step)
        $session = $this->sessionService->getSession(null, $userId, $locale);

        // Return welcome message with language options
        $response = $this->sessionService->getWelcomeMessage($session);

        return response()->json($response);
    }

    /**
     * Process a chat message (stateful conversation).
     */
    public function message(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'session_id' => 'nullable|string|max:64',
            'locale' => 'nullable|in:en,ar',
        ]);

        $message = $request->input('message');
        $sessionId = $request->input('session_id');
        $locale = $request->input('locale', $request->header('Accept-Language', 'en'));
        $locale = str_starts_with($locale, 'ar') ? 'ar' : 'en';
        
        $userId = $request->user()?->id;

        // Get or create session
        $session = $this->sessionService->getSession($sessionId, $userId, $locale);

        // If new session (no session_id provided), return welcome message first
        if (!$sessionId && $session->getState('step') === 'language') {
            $welcomeResponse = $this->sessionService->getWelcomeMessage($session);
            // Then process the message
            $response = $this->sessionService->processMessage($session, $message);
            // Prepend welcome messages
            $response['messages'] = array_merge($welcomeResponse['messages'] ?? [], $response['messages'] ?? []);
            return response()->json($response);
        }

        // Process message
        $response = $this->sessionService->processMessage($session, $message);

        return response()->json($response);
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

        return response()->json($response);
    }
}
