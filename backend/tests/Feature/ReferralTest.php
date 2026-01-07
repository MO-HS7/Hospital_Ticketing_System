<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Encounter;
use App\Models\Referral;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ReferralTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Config::set('masar.auto_assignment_enabled', false);
    }

    // ==========================================
    // CORE RBAC TESTS
    // ==========================================

    public function test_patient_cannot_create_referral(): void
    {
        $patient = User::where('email', 'patient@hospital.com')->firstOrFail();
        $doctor = User::where('email', 'doctor@hospital.com')->firstOrFail();
        $fromDept = Department::first();
        $toDept = Department::where('id', '!=', $fromDept->id)->first();

        $ticket = Ticket::create([
            'patient_id' => $patient->id,
            'creator_id' => $patient->id,
            'department_id' => $fromDept->id,
            'assigned_to' => $doctor->id,
            'type' => 'appointment',
            'subject' => 'Test',
            'description' => 'Test',
            'status' => 'in_progress',
            'priority' => 'medium',
        ]);

        Sanctum::actingAs($patient);

        $response = $this->postJson("/api/tickets/{$ticket->id}/refer", [
            'to_department_id' => $toDept->id,
            'reason' => 'Patient trying to refer',
        ]);

        $response->assertStatus(403);
    }

    public function test_doctor_cannot_refer_unassigned_ticket(): void
    {
        $doctor = User::where('email', 'doctor@hospital.com')->firstOrFail();
        $patient = User::where('email', 'patient@hospital.com')->firstOrFail();
        $fromDept = Department::first();
        $toDept = Department::where('id', '!=', $fromDept->id)->first();

        $ticket = Ticket::create([
            'patient_id' => $patient->id,
            'creator_id' => $patient->id,
            'department_id' => $fromDept->id,
            'assigned_to' => null,
            'type' => 'appointment',
            'subject' => 'Test',
            'description' => 'Test',
            'status' => 'pending',
            'priority' => 'medium',
        ]);

        Sanctum::actingAs($doctor);

        $response = $this->postJson("/api/tickets/{$ticket->id}/refer", [
            'to_department_id' => $toDept->id,
            'reason' => 'Doctor trying to refer unassigned',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('error_code', 'not_assigned');
    }

    public function test_referral_to_same_department_rejected(): void
    {
        $doctor = User::where('email', 'doctor@hospital.com')->firstOrFail();
        $patient = User::where('email', 'patient@hospital.com')->firstOrFail();
        $dept = Department::first();

        $ticket = Ticket::create([
            'patient_id' => $patient->id,
            'creator_id' => $patient->id,
            'department_id' => $dept->id,
            'assigned_to' => $doctor->id,
            'type' => 'appointment',
            'subject' => 'Test',
            'description' => 'Test',
            'status' => 'in_progress',
            'priority' => 'medium',
        ]);

        Sanctum::actingAs($doctor);

        $response = $this->postJson("/api/tickets/{$ticket->id}/refer", [
            'to_department_id' => $dept->id,
            'reason' => 'Same department referral',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error_code', 'same_department');
    }

    public function test_admin_can_view_referrals_list(): void
    {
        $admin = User::where('email', 'admin@hospital.com')->firstOrFail();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/referrals');
        $response->assertStatus(200);
    }

    // ==========================================
    // VALIDATION TESTS
    // ==========================================

    public function test_referral_missing_reason_returns_422(): void
    {
        $doctor = User::where('email', 'doctor@hospital.com')->firstOrFail();
        $patient = User::where('email', 'patient@hospital.com')->firstOrFail();
        $fromDept = Department::first();
        $toDept = Department::where('id', '!=', $fromDept->id)->first();

        $ticket = Ticket::create([
            'patient_id' => $patient->id,
            'creator_id' => $patient->id,
            'department_id' => $fromDept->id,
            'assigned_to' => $doctor->id,
            'type' => 'appointment',
            'subject' => 'Test',
            'description' => 'Test',
            'status' => 'in_progress',
            'priority' => 'medium',
        ]);

        Sanctum::actingAs($doctor);

        $response = $this->postJson("/api/tickets/{$ticket->id}/refer", [
            'to_department_id' => $toDept->id,
            // Missing reason
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_referral_invalid_department_returns_422(): void
    {
        $doctor = User::where('email', 'doctor@hospital.com')->firstOrFail();
        $patient = User::where('email', 'patient@hospital.com')->firstOrFail();
        $fromDept = Department::first();

        $ticket = Ticket::create([
            'patient_id' => $patient->id,
            'creator_id' => $patient->id,
            'department_id' => $fromDept->id,
            'assigned_to' => $doctor->id,
            'type' => 'appointment',
            'subject' => 'Test',
            'description' => 'Test',
            'status' => 'in_progress',
            'priority' => 'medium',
        ]);

        Sanctum::actingAs($doctor);

        $response = $this->postJson("/api/tickets/{$ticket->id}/refer", [
            'to_department_id' => 'invalid-uuid-here',
            'reason' => 'Test reason',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to_department_id']);
    }

    // ==========================================
    // PATIENT VIEW RESTRICTION
    // ==========================================

    public function test_patient_cannot_list_referrals(): void
    {
        $patient = User::where('email', 'patient@hospital.com')->firstOrFail();

        Sanctum::actingAs($patient);

        $response = $this->getJson('/api/referrals');
        $response->assertStatus(403);
    }
}
