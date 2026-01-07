<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TicketLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_patient_can_create_ticket_and_add_note(): void
    {
        $patient = User::where('email', 'patient@hospital.com')->firstOrFail();
        $department = Department::query()->firstOrFail();

        Sanctum::actingAs($patient);

        $createResponse = $this->postJson('/api/tickets', [
            'department_id' => $department->id,
            'type' => 'appointment',
            'subject' => 'Test ticket subject',
            'description' => 'Test ticket description',
            'priority' => 'medium',
        ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('patient_id', $patient->id)
            ->assertJsonPath('department_id', $department->id);

        $ticketId = $createResponse->json('id');
        $this->assertNotNull($ticketId);

        $noteResponse = $this->postJson("/api/tickets/{$ticketId}/notes", [
            'body' => 'Test note body',
        ]);

        $noteResponse->assertStatus(201)
            ->assertJsonPath('body', 'Test note body')
            ->assertJsonStructure([
                'id',
                'ticket_id',
                'user_id',
                'body',
                'user',
            ]);

        $showResponse = $this->getJson("/api/tickets/{$ticketId}");

        $showResponse->assertOk()
            ->assertJsonPath('id', $ticketId)
            ->assertJsonStructure([
                'id',
                'status',
                'patient',
                'department',
                'assignee',
                'notes',
                'events',
                'sla',
            ]);

        $notes = $showResponse->json('notes');
        $this->assertIsArray($notes);
        $this->assertCount(1, $notes);
        $this->assertSame('Test note body', $notes[0]['body'] ?? null);

        $events = $showResponse->json('events');
        $this->assertIsArray($events);

        $eventTypes = array_map(fn ($e) => $e['event_type'] ?? null, $events);
        $this->assertContains('created', $eventTypes);
        $this->assertContains('note_added', $eventTypes);
    }

    public function test_patient_cannot_accept_or_complete_ticket(): void
    {
        $patient = User::where('email', 'patient@hospital.com')->firstOrFail();
        $department = Department::query()->firstOrFail();

        Sanctum::actingAs($patient);

        $createResponse = $this->postJson('/api/tickets', [
            'department_id' => $department->id,
            'type' => 'appointment',
            'subject' => 'Test ticket subject',
            'description' => 'Test ticket description',
            'priority' => 'medium',
        ]);

        $createResponse->assertStatus(201);

        $ticketId = $createResponse->json('id');
        $this->assertNotNull($ticketId);

        $this->postJson("/api/tickets/{$ticketId}/accept")
            ->assertForbidden();

        $this->postJson("/api/tickets/{$ticketId}/complete")
            ->assertForbidden();
    }

    public function test_doctor_can_accept_and_complete_appointment_ticket(): void
    {
        $patient = User::where('email', 'patient@hospital.com')->firstOrFail();
        $doctor = User::where('email', 'doctor@hospital.com')->firstOrFail();
        $department = Department::query()->firstOrFail();

        Sanctum::actingAs($patient);

        $createResponse = $this->postJson('/api/tickets', [
            'department_id' => $department->id,
            'type' => 'appointment',
            'subject' => 'Test ticket subject',
            'description' => 'Test ticket description',
            'priority' => 'medium',
        ]);

        $createResponse->assertStatus(201);

        $ticketId = $createResponse->json('id');
        $this->assertNotNull($ticketId);

        Sanctum::actingAs($doctor);

        $acceptResponse = $this->postJson("/api/tickets/{$ticketId}/accept");

        $acceptResponse->assertOk()
            ->assertJsonPath('status', 'in_progress')
            ->assertJsonPath('assigned_to', $doctor->id);

        $this->assertNotNull($acceptResponse->json('accepted_at'));

        $completeResponse = $this->postJson("/api/tickets/{$ticketId}/complete");

        $completeResponse->assertOk()
            ->assertJsonPath('assigned_to', $doctor->id);

        $this->assertContains($completeResponse->json('status'), ['completed', 'closed_late']);
        $this->assertNotNull($completeResponse->json('completed_at'));
    }

    public function test_doctor_cannot_accept_maintenance_ticket(): void
    {
        $patient = User::where('email', 'patient@hospital.com')->firstOrFail();
        $doctor = User::where('email', 'doctor@hospital.com')->firstOrFail();
        $department = Department::query()->firstOrFail();

        Sanctum::actingAs($patient);

        $createResponse = $this->postJson('/api/tickets', [
            'department_id' => $department->id,
            'type' => 'maintenance',
            'subject' => 'Test maintenance ticket',
            'description' => 'Test ticket description',
            'priority' => 'medium',
        ]);

        $createResponse->assertStatus(201);

        $ticketId = $createResponse->json('id');
        $this->assertNotNull($ticketId);

        Sanctum::actingAs($doctor);

        $this->postJson("/api/tickets/{$ticketId}/accept")
            ->assertForbidden();
    }

    public function test_reception_must_provide_patient_id_for_appointment(): void
    {
        $reception = User::where('email', 'reception@hospital.com')->firstOrFail();
        $department = Department::query()->firstOrFail();

        Sanctum::actingAs($reception);

        // Should fail without patient_id
        $response = $this->postJson('/api/tickets', [
            'department_id' => $department->id,
            'type' => 'appointment',
            'subject' => 'Test ticket for patient',
            'description' => 'Test description',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['patient_id']);
    }

    public function test_reception_can_create_ticket_with_patient_id(): void
    {
        $reception = User::where('email', 'reception@hospital.com')->firstOrFail();
        $patient = User::where('email', 'patient@hospital.com')->firstOrFail();
        $department = Department::query()->firstOrFail();

        Sanctum::actingAs($reception);

        // Should succeed with patient_id
        $response = $this->postJson('/api/tickets', [
            'department_id' => $department->id,
            'type' => 'appointment',
            'subject' => 'Test ticket for patient',
            'description' => 'Test description',
            'patient_id' => $patient->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('patient_id', $patient->id)
            ->assertJsonPath('creator_id', $reception->id);
    }

    /**
     * REGRESSION TEST: Patient can create ticket without sending patient_id.
     * The ForcePatientIdForPatients middleware should auto-inject it.
     */
    public function test_patient_can_create_appointment_without_patient_id(): void
    {
        $patient = User::where('email', 'patient@hospital.com')->firstOrFail();
        $department = Department::query()->firstOrFail();

        Sanctum::actingAs($patient);

        // Explicitly NOT sending patient_id - middleware should inject it
        $response = $this->postJson('/api/tickets', [
            'department_id' => $department->id,
            'type' => 'appointment',
            'subject' => 'Test appointment from patient',
            'description' => 'Testing middleware patient_id injection',
            'priority' => 'medium',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('patient_id', $patient->id)
            ->assertJsonPath('status', 'pending');
    }

    /**
     * Test that /api/me returns user with roles successfully.
     */
    public function test_me_endpoint_returns_user_with_roles(): void
    {
        $patient = User::where('email', 'patient@hospital.com')->firstOrFail();

        Sanctum::actingAs($patient);

        $response = $this->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonPath('email', 'patient@hospital.com')
            ->assertJsonStructure(['id', 'name', 'email', 'roles']);
    }
}

