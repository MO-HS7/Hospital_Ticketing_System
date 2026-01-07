<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class StaffChatbotController extends Controller
{
    /**
     * Handle staff chatbot message.
     */
    public function chat(Request $request): JsonResponse
    {
        // Rate limiting: 30 messages per minute per user
        $rateLimitKey = 'chatbot:' . $request->user()->id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 30)) {
            return response()->json([
                'message' => 'Too many messages. Please wait before sending another.',
                'error_code' => 'rate_limited',
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, 60);

        // Input validation with stricter rules
        $validated = $request->validate([
            'message' => 'required|string|min:2|max:2000',
            'conversation_id' => 'nullable|string|max:100|alpha_dash',
        ]);

        $user = $request->user();
        
        // Role check - staff only
        $staffRoles = ['maintenance', 'reception', 'lab_technician', 'radiologist', 'pharmacist', 'doctor', 'admin'];
        if (!$user->hasRole($staffRoles)) {
            return response()->json([
                'message' => 'Staff chatbot is only available for staff members.',
                'error_code' => 'unauthorized_role',
            ], 403);
        }

        $userMessage = trim($validated['message']);
        
        // Spam/empty check
        if (strlen($userMessage) < 2 || preg_match('/^(.)\1{10,}$/', $userMessage)) {
            return response()->json([
                'reply' => 'Please provide a valid message describing your IT issue.',
                'conversation_id' => $validated['conversation_id'] ?? uniqid('chat_'),
                'can_create_ticket' => false,
            ]);
        }

        // Medical advice guard
        $medicalKeywords = ['patient', 'diagnosis', 'treatment', 'medicine', 'prescription', 'symptom', 'disease', 'مريض', 'تشخيص', 'علاج', 'دواء'];
        $isMedical = collect($medicalKeywords)->contains(fn($k) => stripos($userMessage, $k) !== false);
        
        if ($isMedical) {
            return response()->json([
                'reply' => $this->getMedicalRefusalMessage(),
                'conversation_id' => $validated['conversation_id'] ?? uniqid('chat_'),
                'can_create_ticket' => false,
            ]);
        }

        $conversationId = $validated['conversation_id'] ?? uniqid('chat_');

        // Build system prompt for IT support scope
        $systemPrompt = $this->buildSystemPrompt($user);

        try {
            $response = $this->callGemini($systemPrompt, $userMessage, $conversationId);
            
            return response()->json([
                'reply' => $response['reply'],
                'conversation_id' => $conversationId,
                'can_create_ticket' => $response['can_create_ticket'] ?? false,
                'suggested_subject' => $response['suggested_subject'] ?? null,
            ]);
        } catch (\Exception $e) {
            // Log without noisy details
            Log::warning('Staff chatbot Gemini call failed', ['user_id' => $user->id]);
            
            return response()->json([
                'reply' => $this->getFallbackResponse($user),
                'conversation_id' => $conversationId,
                'can_create_ticket' => true,
            ]);
        }
    }

    /**
     * Create maintenance ticket from chat.
     */
    public function createTicket(Request $request): JsonResponse
    {
        // Rate limiting: 5 tickets per hour per user
        $rateLimitKey = 'chatbot_ticket:' . $request->user()->id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return response()->json([
                'message' => 'You have created too many tickets. Please wait before creating another.',
                'error_code' => 'rate_limited',
            ], 429);
        }

        $validated = $request->validate([
            'subject' => 'required|string|min:5|max:255',
            'description' => 'required|string|min:10|max:5000',
            'priority' => 'sometimes|in:low,medium,high,urgent',
        ]);

        $user = $request->user();
        
        // Staff only
        $staffRoles = ['maintenance', 'reception', 'lab_technician', 'radiologist', 'pharmacist', 'doctor', 'admin'];
        if (!$user->hasRole($staffRoles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        RateLimiter::hit($rateLimitKey, 3600);

        // Create maintenance ticket - INTERNAL ONLY (no patient, no medical routing)
        $ticket = Ticket::create([
            'patient_id' => null,  // MANDATORY: maintenance tickets have NO patient
            'creator_id' => $user->id,
            'department_id' => $this->getMaintenanceDepartmentId(),
            'type' => 'maintenance',
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'status' => 'pending',
            'priority' => $validated['priority'] ?? 'medium',
            'assigned_to' => null,  // Initially unassigned
        ]);

        return response()->json([
            'ticket' => $ticket,
            'message' => 'Maintenance ticket created successfully.',
        ], 201);
    }

    /**
     * Build system prompt for IT support scope.
     */
    private function buildSystemPrompt($user): string
    {
        $lang = app()->getLocale() === 'ar' ? 'Arabic' : 'English';
        $name = $user->name;
        $role = $user->roles->first()?->name ?? 'staff';

        return <<<PROMPT
You are a helpful IT Support Assistant for Masar Hospital (مسار). 
Your role is to help hospital staff with technical issues ONLY.

IMPORTANT RULES:
1. You ONLY handle IT/technical support issues (computer problems, network issues, printer problems, software bugs, system access, password resets, hardware failures).
2. You must NEVER provide medical advice or discuss patient care.
3. If an issue cannot be resolved through chat, suggest creating a maintenance ticket.
4. Respond in {$lang}.
5. Keep responses concise and professional.

Current user: {$name} (Role: {$role})

When the issue seems unresolvable via chat, include in your response:
- A suggestion to create a maintenance ticket
- Set can_create_ticket: true in your analysis

Common IT issues you can help with:
- Password resets and account access
- Computer/laptop issues
- Network connectivity
- Printer problems
- Hospital software issues
- Email problems
- System errors
PROMPT;
    }

    /**
     * Call Gemini API.
     */
    private function callGemini(string $systemPrompt, string $userMessage, string $conversationId): array
    {
        $apiKey = config('services.google.gemini_api_key');
        
        if (!$apiKey) {
            // Fallback to mock response if no API key
            return $this->getMockResponse($userMessage);
        }

        $response = Http::timeout(10)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$apiKey}", [
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $systemPrompt]]],
                    ['role' => 'model', 'parts' => [['text' => 'Understood. I will only help with IT support issues.']]],
                    ['role' => 'user', 'parts' => [['text' => $userMessage]]],
                ],
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'I apologize, I could not process your request.';
            
            // Check if response suggests creating a ticket
            $canCreateTicket = str_contains(strtolower($reply), 'ticket') || 
                              str_contains(strtolower($reply), 'maintenance') ||
                              str_contains(strtolower($reply), 'تذكرة');
            
            return [
                'reply' => $reply,
                'can_create_ticket' => $canCreateTicket,
                'suggested_subject' => $canCreateTicket ? $this->extractSubject($userMessage) : null,
            ];
        }

        throw new \Exception('Gemini API error: ' . $response->status());
    }

    /**
     * Get mock response for development/fallback.
     */
    private function getMockResponse(string $message): array
    {
        $lowerMessage = strtolower($message);
        
        if (str_contains($lowerMessage, 'password') || str_contains($lowerMessage, 'login')) {
            return [
                'reply' => "I understand you're having login/password issues. Here's what you can try:\n\n1. Clear your browser cache and cookies\n2. Try a different browser\n3. Check if Caps Lock is on\n\nIf the issue persists, I can help you create a maintenance ticket for the IT team.",
                'can_create_ticket' => true,
                'suggested_subject' => 'Password/Login Issue',
            ];
        }
        
        if (str_contains($lowerMessage, 'printer') || str_contains($lowerMessage, 'print')) {
            return [
                'reply' => "For printer issues, please try:\n\n1. Check if the printer is powered on and connected\n2. Restart the print spooler service\n3. Check for paper jams\n\nWould you like me to create a maintenance ticket for this?",
                'can_create_ticket' => true,
                'suggested_subject' => 'Printer Issue',
            ];
        }
        
        if (str_contains($lowerMessage, 'slow') || str_contains($lowerMessage, 'network')) {
            return [
                'reply' => "Network/slow performance issues:\n\n1. Try restarting your computer\n2. Check if other colleagues have the same issue\n3. Close unnecessary browser tabs\n\nIf this is a widespread issue, I can create a maintenance ticket.",
                'can_create_ticket' => true,
                'suggested_subject' => 'Network/Performance Issue',
            ];
        }

        return [
            'reply' => "I'm here to help with IT and technical issues. Please describe your problem in more detail:\n\n- What application or system is affected?\n- What error message do you see?\n- When did this issue start?\n\nIf you can't resolve the issue, I can help you create a maintenance ticket for the IT team.",
            'can_create_ticket' => true,
            'suggested_subject' => 'IT Support Request',
        ];
    }

    /**
     * Get fallback response when API fails.
     */
    private function getFallbackResponse($user): string
    {
        $lang = app()->getLocale();
        
        if ($lang === 'ar') {
            return "عذرًا، أواجه صعوبة تقنية حاليًا. يمكنك إنشاء تذكرة صيانة للحصول على المساعدة من فريق تكنولوجيا المعلومات.";
        }
        
        return "I'm experiencing a technical difficulty. You can create a maintenance ticket to get help from the IT team.";
    }

    /**
     * Get refusal message for medical queries.
     */
    private function getMedicalRefusalMessage(): string
    {
        $lang = app()->getLocale();
        
        if ($lang === 'ar') {
            return "عذرًا، أنا مساعد دعم تقنية المعلومات فقط. لا يمكنني تقديم نصائح طبية أو المساعدة في شؤون المرضى. يرجى التواصل مع الطاقم الطبي المختص.";
        }
        
        return "I'm sorry, I'm an IT support assistant only. I cannot provide medical advice or help with patient-related matters. Please contact the appropriate medical staff.";
    }

    /**
     * Extract subject from user message.
     */
    private function extractSubject(string $message): string
    {
        $subject = substr($message, 0, 100);
        return strlen($message) > 100 ? $subject . '...' : $subject;
    }

    /**
     * Get Maintenance department ID for staff tickets.
     * These tickets are routed ONLY to maintenance portal - never to doctors.
     */
    private function getMaintenanceDepartmentId(): ?string
    {
        $dept = \App\Models\Department::where('slug', 'maintenance')
            ->orWhere('slug', 'it-maintenance')
            ->first();
        return $dept?->id;
    }
}
