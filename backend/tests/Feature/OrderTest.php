<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_patient_cannot_create_order(): void
    {
        $patient = User::where('email', 'patient@hospital.com')->firstOrFail();
        $doctor = User::where('email', 'doctor@hospital.com')->firstOrFail();
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

        Sanctum::actingAs($patient);

        $response = $this->postJson("/api/tickets/{$ticket->id}/orders", [
            'type' => 'lab',
            'items' => ['CBC', 'Blood Sugar'],
        ]);

        $response->assertStatus(403);
    }

    public function test_doctor_cannot_create_order_for_unassigned_ticket(): void
    {
        $doctor = User::where('email', 'doctor@hospital.com')->firstOrFail();
        $patient = User::where('email', 'patient@hospital.com')->firstOrFail();
        $dept = Department::first();

        $ticket = Ticket::create([
            'patient_id' => $patient->id,
            'creator_id' => $patient->id,
            'department_id' => $dept->id,
            'assigned_to' => null,
            'type' => 'appointment',
            'subject' => 'Test',
            'description' => 'Test',
            'status' => 'pending',
            'priority' => 'medium',
        ]);

        Sanctum::actingAs($doctor);

        $response = $this->postJson("/api/tickets/{$ticket->id}/orders", [
            'type' => 'lab',
            'items' => ['CBC'],
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('error_code', 'not_assigned');
    }

    public function test_order_missing_items_returns_422(): void
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

        $response = $this->postJson("/api/tickets/{$ticket->id}/orders", [
            'type' => 'lab',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_order_invalid_type_returns_422(): void
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

        $response = $this->postJson("/api/tickets/{$ticket->id}/orders", [
            'type' => 'invalid_type',
            'items' => ['CBC'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_admin_can_view_orders_list(): void
    {
        $admin = User::where('email', 'admin@hospital.com')->firstOrFail();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/orders');
        $response->assertStatus(200);
    }
}
