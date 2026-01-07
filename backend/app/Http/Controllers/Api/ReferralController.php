<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Encounter;
use App\Models\Referral;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Services\DoctorAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReferralController extends Controller
{
    /**
     * Create a referral (doctor refers patient to another department).
     */
    public function refer(Request $request, Ticket $ticket): JsonResponse
    {
        // Authorize
        $this->authorize('create', Referral::class);

        // Validate request
        $validated = $request->validate([
            'to_department_id' => 'required|uuid|exists:departments,id',
            'reason' => 'required|string|max:1000',
            'priority' => 'nullable|in:low,normal,high',
        ]);

        // Check doctor is assigned to this ticket
        if ($ticket->assigned_to !== $request->user()->id) {
            return response()->json([
                'message' => 'You can only refer tickets assigned to you.',
                'error_code' => 'not_assigned',
            ], 403);
        }

        // Reject if same department
        if ($validated['to_department_id'] === $ticket->department_id) {
            return response()->json([
                'message' => 'Cannot refer to the same department.',
                'error_code' => 'same_department',
            ], 422);
        }

        // Ensure encounter exists
        $encounterId = $ticket->encounter_id;
        if (!$encounterId) {
            // Create new encounter for this patient
            $encounter = Encounter::create([
                'patient_id' => $ticket->patient_id,
                'source' => 'doctor',
                'status' => 'active',
                'started_at' => now(),
            ]);
            $encounterId = $encounter->id;
            
            // Update from_ticket to use this encounter
            $ticket->update(['encounter_id' => $encounterId]);
        }

        // Create new ticket in target department
        $assignedTo = null;
        $ticketStatus = 'pending';
        $acceptedAt = null;
        $assignmentMessage = 'No doctor available in target department.';
        $assigned = false;

        if (config('masar.auto_assignment_enabled')) {
            $assignmentService = new DoctorAssignmentService();
            $doctor = $assignmentService->assignDoctor($validated['to_department_id']);
            
            if ($doctor) {
                $assignedTo = $doctor->id;
                $ticketStatus = 'in_progress';
                $acceptedAt = now();
                $assignmentMessage = 'Automatically assigned to available doctor.';
                $assigned = true;
            }
        }

        $newTicket = Ticket::create([
            'encounter_id' => $encounterId,
            'patient_id' => $ticket->patient_id,
            'creator_id' => $request->user()->id,
            'department_id' => $validated['to_department_id'],
            'assigned_to' => $assignedTo,
            'type' => 'appointment',
            'subject' => 'Referral',
            'description' => "Referral from ticket #{$ticket->id}\nReason: {$validated['reason']}",
            'priority' => $validated['priority'] ?? 'normal',
            'status' => $ticketStatus,
            'accepted_at' => $acceptedAt,
            'deadline' => now()->addDays(1),
        ]);

        // Create referral record
        $referral = Referral::create([
            'encounter_id' => $encounterId,
            'from_ticket_id' => $ticket->id,
            'to_ticket_id' => $newTicket->id,
            'to_department_id' => $validated['to_department_id'],
            'referred_by' => $request->user()->id,
            'reason' => $validated['reason'],
            'priority' => $validated['priority'] ?? 'normal',
            'status' => 'created',
        ]);

        // Create events
        TicketEvent::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'event_type' => 'referral_created',
            'meta' => [
                'referral_id' => $referral->id,
                'to_department_id' => $validated['to_department_id'],
                'to_ticket_id' => $newTicket->id,
            ],
        ]);

        TicketEvent::create([
            'ticket_id' => $newTicket->id,
            'user_id' => $request->user()->id,
            'event_type' => 'referral_ticket_created',
            'meta' => [
                'referral_id' => $referral->id,
                'from_ticket_id' => $ticket->id,
            ],
        ]);

        if ($assigned) {
            TicketEvent::create([
                'ticket_id' => $newTicket->id,
                'user_id' => $request->user()->id,
                'event_type' => 'referral_assigned',
                'meta' => [
                    'assigned_to' => $assignedTo,
                ],
            ]);
        }

        return response()->json([
            'referral' => $referral->load(['toDepartment', 'referredBy']),
            'new_ticket' => [
                'id' => $newTicket->id,
                'status' => $newTicket->status,
                'department' => $newTicket->department,
                'assigned_to' => $newTicket->assignee,
            ],
            'assignment' => [
                'assigned' => $assigned,
                'doctor_id' => $assignedTo,
                'message' => $assignmentMessage,
            ],
        ], 201);
    }

    /**
     * List referrals with RBAC scoping.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Referral::class);

        $user = $request->user();
        $query = Referral::with(['fromTicket', 'toTicket', 'toDepartment', 'referredBy']);

        // Apply RBAC scoping
        if ($user->hasRole('doctor')) {
            $query->where(function ($q) use ($user) {
                $q->where('referred_by', $user->id)
                  ->orWhereHas('fromTicket', fn($q2) => $q2->where('assigned_to', $user->id))
                  ->orWhereHas('toTicket', fn($q2) => $q2->where('assigned_to', $user->id));
            });
        }
        // Admin/reception see all

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('encounter_id')) {
            $query->where('encounter_id', $request->encounter_id);
        }
        if ($request->filled('department_id')) {
            $query->where('to_department_id', $request->department_id);
        }
        if ($request->filled('from_ticket_id')) {
            $query->where('from_ticket_id', $request->from_ticket_id);
        }

        return response()->json($query->latest()->paginate(15));
    }

    /**
     * Show a specific referral.
     */
    public function show(Referral $referral): JsonResponse
    {
        $this->authorize('view', $referral);

        $referral->load(['fromTicket', 'toTicket', 'toDepartment', 'referredBy', 'encounter']);

        return response()->json($referral);
    }
}
