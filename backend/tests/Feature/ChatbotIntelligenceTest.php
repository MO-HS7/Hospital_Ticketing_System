<?php

namespace Tests\Feature;

use App\Models\ChatbotSession;
use App\Models\Department;
use App\Models\User;
use App\Services\ChatbotSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Acceptance Tests for Chatbot Intelligence Upgrade
 * 
 * These tests verify:
 * 1. Conversation history is included in AI requests
 * 2. Step state is maintained and respected
 * 3. Back navigation works correctly
 * 4. Emergency escalation triggers properly
 */
class ChatbotIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed departments
        Department::create(['name_en' => 'Cardiology', 'name_ar' => 'أمراض القلب', 'slug' => 'cardiology', 'is_active' => true]);
        Department::create(['name_en' => 'Neurology', 'name_ar' => 'الأعصاب', 'slug' => 'neurology', 'is_active' => true]);
        Department::create(['name_en' => 'Gastroenterology', 'name_ar' => 'الجهاز الهضمي', 'slug' => 'gastroenterology', 'is_active' => true]);
        Department::create(['name_en' => 'Emergency', 'name_ar' => 'الطوارئ', 'slug' => 'emergency', 'is_active' => true]);
    }

    /**
     * Test: Conversation history is stored and retrieved correctly.
     */
    public function test_conversation_history_is_persisted(): void
    {
        $session = ChatbotSession::create([
            'locale' => 'en',
            'state' => ['step' => 'symptoms'],
            'messages' => [],
            'expires_at' => now()->addHours(24),
        ]);

        $service = new ChatbotSessionService();
        
        // First message
        $response1 = $service->processMessage($session, 'Hello');
        $session->refresh();
        
        $this->assertCount(2, $session->messages); // user + assistant
        $this->assertEquals('user', $session->messages[0]['role']);
        $this->assertEquals('Hello', $session->messages[0]['content']);
        
        // Second message
        $response2 = $service->processMessage($session, 'I have a headache');
        $session->refresh();
        
        $this->assertCount(4, $session->messages); // 2 turns
    }

    /**
     * Test: Step state is maintained across messages.
     */
    public function test_step_state_is_maintained(): void
    {
        $session = ChatbotSession::create([
            'locale' => 'en',
            'state' => ['step' => 'department', 'suggested_departments' => ['cardiology']],
            'messages' => [],
            'expires_at' => now()->addHours(24),
        ]);

        Department::firstOrCreate(['slug' => 'cardiology'], [
            'name_en' => 'Cardiology', 'name_ar' => 'القلب', 'is_active' => true
        ]);

        $service = new ChatbotSessionService();
        
        // Select department
        $response = $service->processMessage($session, 'cardiology');
        $session->refresh();
        
        // Should progress to doctor step
        $this->assertContains($session->getState('step'), ['doctor', 'datetime', 'patient_info']);
        $this->assertNotNull($session->getState('department_id'));
    }

    /**
     * Test: Back navigation during booking flow.
     */
    public function test_back_navigation_works(): void
    {
        $session = ChatbotSession::create([
            'locale' => 'en',
            'state' => ['step' => 'doctor', 'department_id' => 1],
            'messages' => [],
            'expires_at' => now()->addHours(24),
        ]);

        $service = new ChatbotSessionService();
        
        $response = $service->processMessage($session, 'go back');
        $session->refresh();
        
        $this->assertEquals('department', $session->getState('step'));
        $this->assertEquals('back_navigation', $response['action'] ?? null);
    }

    /**
     * Test: Cancel booking flow.
     */
    public function test_cancel_flow_works(): void
    {
        $session = ChatbotSession::create([
            'locale' => 'en',
            'state' => ['step' => 'payment', 'department_id' => 1, 'doctor_id' => 1],
            'messages' => [],
            'expires_at' => now()->addHours(24),
        ]);

        $service = new ChatbotSessionService();
        
        $response = $service->processMessage($session, 'cancel');
        $session->refresh();
        
        $this->assertEquals('symptoms', $session->getState('step'));
        $this->assertNull($session->getState('department_id'));
    }

    /**
     * Test: Recent messages method returns correct count.
     */
    public function test_recent_messages_returns_limited_history(): void
    {
        $session = ChatbotSession::create([
            'locale' => 'en',
            'state' => ['step' => 'symptoms'],
            'messages' => [],
            'expires_at' => now()->addHours(24),
        ]);

        // Add 25 messages
        for ($i = 0; $i < 25; $i++) {
            $session->addMessage('user', "Message $i");
        }
        $session->save();
        $session->refresh();

        // Should only keep last 20
        $this->assertCount(20, $session->messages);
        
        // getRecentMessages should limit further
        $recent = $session->getRecentMessages(5);
        $this->assertCount(5, $recent);
    }
    
    // =========================================================================
    // NEW ACCEPTANCE TESTS FOR STRICT VALIDATION
    // =========================================================================
    
    /**
     * Test: Greeting does not advance step (stays in current step).
     */
    public function test_greeting_does_not_advance_step(): void
    {
        $session = ChatbotSession::create([
            'locale' => 'en',
            'state' => ['step' => 'department', 'mode' => 'booking', 'suggested_departments' => ['cardiology']],
            'messages' => [],
            'expires_at' => now()->addHours(24),
        ]);

        $service = new ChatbotSessionService();
        
        // Send greeting while in department step
        $response = $service->processMessage($session, 'hello');
        $session->refresh();
        
        // Should NOT advance to doctor step
        $this->assertEquals('department', $session->getState('step'));
        // Should contain redirect message
        $this->assertStringContainsString('department', strtolower($response['messages'][0] ?? ''));
    }
    
    /**
     * Test: Noise input does not advance step.
     */
    public function test_noise_input_does_not_advance_step(): void
    {
        $session = ChatbotSession::create([
            'locale' => 'en',
            'state' => ['step' => 'department', 'mode' => 'booking'],
            'messages' => [],
            'expires_at' => now()->addHours(24),
        ]);

        $service = new ChatbotSessionService();
        
        // Send noise input (single character)
        $response = $service->processMessage($session, 't');
        $session->refresh();
        
        // Should stay on department step
        $this->assertEquals('department', $session->getState('step'));
        $this->assertEquals('noise', $response['intent'] ?? $response['_intent'] ?? null);
    }
    
    /**
     * Test: Arabic noise input does not advance step.
     */
    public function test_arabic_noise_input_does_not_advance_step(): void
    {
        $session = ChatbotSession::create([
            'locale' => 'ar',
            'state' => ['step' => 'department', 'mode' => 'booking'],
            'messages' => [],
            'expires_at' => now()->addHours(24),
        ]);

        $service = new ChatbotSessionService();
        
        // Send noise input (dot)
        $response = $service->processMessage($session, '.');
        $session->refresh();
        
        // Should stay on department step
        $this->assertEquals('department', $session->getState('step'));
    }
    
    /**
     * Test: Invalid doctor_id returns validation error and stays on step 2.
     */
    public function test_invalid_doctor_id_returns_validation_error(): void
    {
        $dept = Department::firstOrCreate(['slug' => 'cardiology'], [
            'name_en' => 'Cardiology', 'name_ar' => 'القلب', 'is_active' => true
        ]);
        
        $session = ChatbotSession::create([
            'locale' => 'en',
            'state' => ['step' => 'doctor', 'mode' => 'booking', 'department_id' => $dept->id],
            'messages' => [],
            'expires_at' => now()->addHours(24),
        ]);

        $service = new ChatbotSessionService();
        
        // Send invalid doctor selection
        $response = $service->processMessage($session, 'some random text');
        $session->refresh();
        
        // Should stay on doctor step
        $this->assertEquals('doctor', $session->getState('step'));
        // Should have validation error
        $this->assertNotNull($response['validation_error'] ?? null);
    }
    
    /**
     * Test: Emergency symptoms trigger escalation.
     */
    public function test_emergency_symptoms_trigger_escalation(): void
    {
        $session = ChatbotSession::create([
            'locale' => 'en',
            'state' => ['step' => 'initial'],
            'messages' => [],
            'expires_at' => now()->addHours(24),
        ]);

        $service = new ChatbotSessionService();
        
        // Send emergency symptom
        $response = $service->processMessage($session, "I have chest pain and can't breathe");
        
        // Should be flagged as emergency
        $this->assertTrue($response['is_emergency'] ?? false);
        $this->assertEquals('emergency', $response['intent'] ?? $response['_intent'] ?? null);
    }
    
    /**
     * Test: Arabic emergency symptoms trigger escalation.
     */
    public function test_arabic_emergency_triggers_escalation(): void
    {
        $session = ChatbotSession::create([
            'locale' => 'ar',
            'state' => ['step' => 'initial'],
            'messages' => [],
            'expires_at' => now()->addHours(24),
        ]);

        $service = new ChatbotSessionService();
        
        // Send emergency symptom in Arabic
        $response = $service->processMessage($session, 'ألم صدر شديد لا أستطيع التنفس');
        
        // Should be flagged as emergency
        $this->assertTrue($response['is_emergency'] ?? false);
    }
    
    /**
     * Test: Booking requires explicit confirmation.
     */
    public function test_booking_requires_confirmation(): void
    {
        $session = ChatbotSession::create([
            'locale' => 'en',
            'state' => ['step' => 'symptoms'],
            'messages' => [],
            'expires_at' => now()->addHours(24),
        ]);

        $service = new ChatbotSessionService();
        
        // Describe symptoms - should NOT auto-start booking
        $response = $service->processMessage($session, 'I have a headache');
        $session->refresh();
        
        // Should offer booking but not force it
        $this->assertContains($session->getState('step'), ['symptoms', 'initial']);
        
        // Check if booking is offered
        if (isset($response['should_offer_booking'])) {
            $this->assertTrue($response['should_offer_booking']);
        }
    }
}
