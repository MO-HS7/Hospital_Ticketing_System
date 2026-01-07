<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\TicketNote;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TicketController extends Controller
{
    /**
     * Display a listing of tickets with optimized queries.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Ticket::class);

        $user = $request->user();
        
        // Select only required fields for list view
        $query = Ticket::select([
            'id', 'encounter_id', 'patient_id', 'creator_id', 'department_id', 'assigned_to', 'type', 
            'status', 'priority', 'subject', 'description', 'scheduled_at', 'deadline',
            'created_at', 'updated_at', 'accepted_at', 'completed_at'
        ])->with([
            'patient:id,name',
            'department:id,name_en,name_ar',
            'assignee:id,name',
        ]);

        // Filter based on role
        if ($user->hasRole('patient')) {
            // Patients see only their own tickets
            $query->where('patient_id', $user->id);
        } elseif ($user->hasRole('admin')) {
            // Admins see all tickets (no filter)
        } elseif ($user->hasRole('doctor')) {
            // Doctors see:
            // 1) Tickets assigned to them (any status)
            // 2) Pending tickets in their department (for pickup)
            $query->where('type', 'appointment')
                  ->where(function ($q) use ($user) {
                      $q->where(function ($q1) use ($user) {
                          $q1->where('assigned_to', $user->id)
                             ->where('status', '!=', 'awaiting_payment');
                      })
                        ->orWhere(function ($q2) use ($user) {
                            $q2->where('status', 'pending')
                               ->whereNull('assigned_to');
                            if ($user->department_id) {
                                $q2->where('department_id', $user->department_id);
                            }
                        });
                  });
        } elseif ($user->hasRole('maintenance')) {
            // Maintenance sees maintenance tickets assigned or pending in their dept
            $query->where('type', 'maintenance')
                  ->where(function ($q) use ($user) {
                      $q->where('assigned_to', $user->id)
                        ->orWhere(function ($q2) use ($user) {
                            $q2->where('status', 'pending')
                               ->whereNull('assigned_to');
                            if ($user->department_id) {
                                $q2->where('department_id', $user->department_id);
                            }
                        });
                  });
        } elseif ($user->hasRole('reception')) {
            // Reception sees appointment tickets they created
            $query->where('type', 'appointment')
                  ->where(function ($q) use ($user) {
                      $q->where('status', 'awaiting_payment')
                        ->orWhere('creator_id', $user->id);
                  });
        }

        // Apply status filter from request
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Apply department filter from request (for admins)
        if ($request->filled('department_id') && $user->hasRole('admin')) {
            $query->where('department_id', $request->department_id);
        }

        return response()->json($query->latest()->paginate(15));
    }

    /**
     * Store a new ticket.
     */
    public function store(Request $request)
    {
        // Inject patient_id FIRST, before authorization and validation
        // This ensures patients creating their own tickets always have patient_id set
        $user = $request->user();
        $user->loadMissing('roles');
        $staffRoles = ['admin', 'reception', 'doctor', 'maintenance', 'lab_technician', 'radiologist', 'pharmacist'];
        $isStaff = $user->hasAnyRole($staffRoles);

        \Log::info('TicketController@store - ROLE CHECK (BEFORE INJECTION)', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_roles' => $user->roles->pluck('name')->toArray(),
            'isStaff' => $isStaff,
        ]);

        // If user is NOT staff, FORCE patient_id to be their own ID
        if (!$isStaff) {
            $request->merge(['patient_id' => $user->id]);
            \Log::info('TicketController@store - INJECTED patient_id for non-staff', [
                'patient_id' => $user->id,
            ]);
        }

        // Now run authorization
        $this->authorize('create', Ticket::class);

        // Now run validation with ROLE-BASED rules
        $typeRule = 'required|in:appointment,maintenance';
        if ($user->hasRole('reception')) {
            $typeRule = 'required|in:appointment';
        }

        // CRITICAL FIX: Role-based validation rules
        // For STAFF: patient_id is REQUIRED when creating appointments
        // For PATIENTS: patient_id is NOT validated at all (already injected above)
        $validationRules = [
            'department_id' => 'required|exists:departments,id',
            'type' => $typeRule,
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_at' => 'nullable|date|after:now',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'encounter_id' => 'nullable|uuid|exists:encounters,id',
            // Patient Info fields (Step 3 enhancement)
            'patient_age' => 'nullable|integer|min:0|max:150',
            'patient_gender' => 'nullable|in:male,female',
            'contact_method' => 'nullable|in:phone,whatsapp,sms,in_app',
            'contact_phone' => 'nullable|string|max:20',
            'is_emergency' => 'nullable|boolean',
            'medical_conditions' => 'nullable|string|max:1000',
            'additional_notes' => 'nullable|string|max:1000',
        ];

        // Only add patient_id validation for STAFF users
        if ($isStaff) {
            // Staff creating appointments MUST provide patient_id
            if ($request->type === 'appointment' || $request->input('type') === 'appointment') {
                $validationRules['patient_id'] = 'required|exists:users,id';
            } else {
                $validationRules['patient_id'] = 'nullable|exists:users,id';
            }
        }
        // For non-staff (patients), patient_id is NOT in validation rules at all
        // It was already injected above and doesn't need validation

        \Log::info('▶▶▶ VALIDATION RULES BEFORE VALIDATE (CRITICAL DEBUG)', [
            'isStaff' => $isStaff,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_roles' => $user->roles->pluck('name')->toArray(),
            'request_type' => $request->type ?? $request->input('type'),
            'patient_id_in_request' => $request->patient_id,
            'patient_id_rule_exists' => isset($validationRules['patient_id']),
            'patient_id_rule_value' => $validationRules['patient_id'] ?? 'NOT_SET',
            'ALL_VALIDATION_RULES' => $validationRules, // Full dump
        ]);

        // DUMP validation rules to log for absolute confirmation
        \Log::debug('VALIDATION RULES DUMP: ' . var_export($validationRules, true));

        $request->validate($validationRules);
        
        // Use patient_id from request (either user-provided for staff, or injected for patients)
        $patientId = $request->patient_id ?? $user->id;
        // For patients (or any non-staff role), patient_id was already injected by middleware

        // PHASE 2: Payment Gating for Appointments
        if ($request->type === 'appointment' && config('masar.payments_enabled')) {
            // Require encounter_id when payments are enabled
            if (!$request->encounter_id) {
                return response()->json([
                    'message' => 'Encounter is required when payment system is enabled.',
                    'error_code' => 'encounter_required',
                ], 402);
            }

            $encounter = \App\Models\Encounter::find($request->encounter_id);
            
            // Verify encounter belongs to the patient
            if ($encounter->patient_id !== $patientId) {
                return response()->json([
                    'message' => 'Encounter does not belong to this patient.',
                    'error_code' => 'encounter_mismatch',
                ], 403);
            }

            // Check payment status
            // Allow if: paid, waived, OR pay_at_hospital with pending (patient will pay at reception)
            $allowedStatuses = ['paid', 'waived'];
            $isPayAtHospital = $encounter->payment_method === 'pay_at_hospital' && $encounter->payment_status === 'pending';
            
            if (!in_array($encounter->payment_status, $allowedStatuses) && !$isPayAtHospital) {
                return response()->json([
                    'message' => 'Payment is required before creating an appointment.',
                    'error_code' => 'payment_required',
                    'encounter' => [
                        'id' => $encounter->id,
                        'payment_status' => $encounter->payment_status,
                        'payment_method' => $encounter->payment_method,
                        'amount' => $encounter->amount,
                    ],
                ], 402);
            }
        }

        // PHASE 3: Auto-Assignment
        $assignedTo = $request->assigned_to;
        $ticketStatus = 'pending';
        $acceptedAt = null;

        // Check if this is a pay_at_hospital with pending payment
        $encounter = $request->encounter_id ? \App\Models\Encounter::find($request->encounter_id) : null;
        
        // DEBUG LOGGING - REMOVE AFTER FIX
        \Log::info('Ticket Creation - Payment Flow Debug', [
            'encounter_id' => $encounter?->id,
            'payment_method' => $encounter?->payment_method,
            'payment_status' => $encounter?->payment_status,
            'payments_enabled' => config('masar.payments_enabled'),
            'auto_assignment_enabled' => config('masar.auto_assignment_enabled'),
            'request_type' => $request->type,
            'department_id' => $request->department_id,
        ]);
        
        $isPayAtHospitalPending = $encounter && 
            $encounter->payment_method === 'pay_at_hospital' && 
            $encounter->payment_status === 'pending';
        
        \Log::info('Ticket Creation - Status Decision', [
            'isPayAtHospitalPending' => $isPayAtHospitalPending,
            'will_set_awaiting_payment' => ($isPayAtHospitalPending && config('masar.payments_enabled')),
            'will_run_auto_assignment' => (config('masar.auto_assignment_enabled') && $request->type === 'appointment' && !$isPayAtHospitalPending),
        ]);
        
        // If pay_at_hospital with pending payment, set status to awaiting_payment
        // Ticket will NOT be visible to doctors until reception confirms payment
        if ($isPayAtHospitalPending && config('masar.payments_enabled')) {
            $ticketStatus = 'awaiting_payment';
            // Skip auto-assignment - will run after payment is confirmed
            \Log::info('Ticket Creation - Setting awaiting_payment status (Pay at Hospital)');
        } elseif (config('masar.auto_assignment_enabled') && $request->type === 'appointment') {
            // Auto-assign a doctor from the department
            $assignmentService = new \App\Services\DoctorAssignmentService();
            $assignedDoctor = $assignmentService->assignDoctor($request->department_id);
            
            if ($assignedDoctor) {
                $assignedTo = $assignedDoctor->id;
                $ticketStatus = 'assigned'; // Mark as assigned, not in_progress
                \Log::info('Ticket Creation - Auto-assigned to doctor (Pay Now)', [
                    'doctor_id' => $assignedDoctor->id,
                    'doctor_name' => $assignedDoctor->name,
                ]);
            } else {
                \Log::warning('Ticket Creation - No doctor available for auto-assignment', [
                    'department_id' => $request->department_id,
                ]);
            }
            // If no doctor available, leave as pending/unassigned
        }
        
        $ticket = Ticket::create([
            'encounter_id' => $request->encounter_id, // Phase 2
            'patient_id' => $patientId,
            'creator_id' => $user->id,
            'department_id' => $request->department_id,
            'assigned_to' => $assignedTo,
            'type' => $request->type,
            'subject' => $request->subject,
            'description' => $request->description,
            'scheduled_at' => $request->scheduled_at,
            'priority' => $request->priority ?? 'medium',
            'status' => $ticketStatus,
            'accepted_at' => $acceptedAt,
            'deadline' => $request->scheduled_at ? 
                Carbon::parse($request->scheduled_at)->addHours(2) : 
                now()->addDays(1),
            // Patient Info fields (Step 3 enhancement) - CRITICAL: These were missing!
            'patient_age' => $request->patient_age,
            'patient_gender' => $request->patient_gender,
            'contact_method' => $request->contact_method,
            'contact_phone' => $request->contact_phone,
            'is_emergency' => $request->is_emergency ?? false,
            'medical_conditions' => $request->medical_conditions,
            'additional_notes' => $request->additional_notes,
        ]);

        TicketEvent::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'event_type' => 'created',
            'meta' => [
                'type' => $ticket->type,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'auto_assigned' => config('masar.auto_assignment_enabled') ?? false,
                'assigned_to' => $ticket->assigned_to,
            ],
        ]);

        return response()->json($ticket->load(['department', 'assignee']), 201);
    }

    /**
     * Display the specified ticket.
     */
    public function show(Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'patient',
            'department',
            'assignee',
            'notes' => fn($q) => $q->with('user')->latest(),
            'events' => fn($q) => $q->with('user')->latest(),
        ]);

        $ticket->setAttribute('sla', [
            'is_overdue' => $ticket->isOverdue(),
            'minutes_remaining' => $ticket->deadline ? now()->diffInMinutes($ticket->deadline, false) : null,
        ]);

        return response()->json($ticket);
    }

    /**
     * Update the specified ticket.
     */
    public function update(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $request->validate([
            'status' => 'nullable|in:pending,assigned,awaiting_payment,in_progress,completed,overdue,closed_late',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $originalStatus = $ticket->status;
        $originalPriority = $ticket->priority;
        $originalAssignedTo = $ticket->assigned_to;

        $payload = $request->only(['status', 'priority', 'assigned_to']);

        if (array_key_exists('status', $payload)) {
            if ($payload['status'] === 'in_progress' && $ticket->accepted_at === null) {
                $payload['accepted_at'] = now();
            }

            if (in_array($payload['status'], ['completed', 'closed_late'], true) && $ticket->completed_at === null) {
                $payload['completed_at'] = now();
            }
        }

        $ticket->update($payload);

        if ($request->filled('status') && $originalStatus !== $ticket->status) {
            TicketEvent::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'event_type' => 'status_changed',
                'meta' => [
                    'from' => $originalStatus,
                    'to' => $ticket->status,
                ],
            ]);
        }

        if ($request->filled('priority') && $originalPriority !== $ticket->priority) {
            TicketEvent::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'event_type' => 'priority_changed',
                'meta' => [
                    'from' => $originalPriority,
                    'to' => $ticket->priority,
                ],
            ]);
        }

        if ($request->has('assigned_to') && $originalAssignedTo != $ticket->assigned_to) {
            TicketEvent::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'event_type' => 'assigned',
                'meta' => [
                    'from' => $originalAssignedTo,
                    'to' => $ticket->assigned_to,
                ],
            ]);
        }

        return response()->json($ticket->load(['patient', 'department', 'assignee']));
    }

    /**
     * Accept a ticket (for unassigned tickets only).
     * 
     * IMPORTANT DISTINCTION - Accept vs Start:
     * 
     * Accept: Used when a staff member claims an UNASSIGNED ticket.
     *   - Only for tickets with status 'pending' and assigned_to = null
     *   - Changes status to 'in_progress' and assigns ticket to current user
     *   - Records both accepted_at and started_at timestamps
     *   - Used when auto-assignment is disabled
     * 
     * Start: Used when a staff member begins work on an ALREADY ASSIGNED ticket.
     *   - Only for tickets with status 'pending' or 'assigned' and assigned_to = current user
     *   - Changes status to 'in_progress'
     *   - Records started_at timestamp (for time tracking)
     *   - Used when auto-assignment is enabled or ticket was manually assigned
     * 
     * In the current workflow with auto-assignment enabled:
     *   - Pay Now tickets → created with status 'assigned' → Doctor clicks Start
     *   - Pay at Hospital → 'awaiting_payment' → Reception approves → 'assigned' → Doctor clicks Start
     */
    public function accept(Request $request, Ticket $ticket)
    {
        // PHASE 3: Block accept if auto-assignment is enabled
        if (config('masar.auto_assignment_enabled') && $ticket->assigned_to === null) {
            return response()->json([
                'message' => 'Manual ticket acceptance is disabled when auto-assignment is enabled.',
                'error_code' => 'auto_assignment_enabled',
            ], 403);
        }

        $this->authorize('accept', $ticket);

        $originalStatus = $ticket->status;
        $originalAssignedTo = $ticket->assigned_to;

        $ticket->update([
            'assigned_to' => $request->user()->id,
            'status' => 'in_progress',
            'accepted_at' => now(),
            'started_at' => now(),
        ]);

        TicketEvent::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'event_type' => 'accepted',
            'meta' => [
                'status_from' => $originalStatus,
                'status_to' => $ticket->status,
                'assigned_to_from' => $originalAssignedTo,
                'assigned_to_to' => $ticket->assigned_to,
            ],
        ]);

        return response()->json($ticket->load(['patient', 'department', 'assignee']));
    }

    /**
     * Start working on an assigned ticket.
     * 
     * This endpoint is the PRIMARY action for beginning work on tickets in the current workflow.
     * See the 'accept' method documentation for the distinction between Accept and Start.
     * 
     * Workflow:
     *   1. Ticket is created with 'assigned' status (auto-assignment enabled)
     *   2. Doctor/Maintenance staff clicks "Start" button
     *   3. Status changes to 'in_progress' and started_at is recorded
     *   4. Staff completes work and clicks "Complete"
     *   5. System calculates time_spent_minutes from started_at to completed_at
     */
    public function start(Request $request, Ticket $ticket)
    {
        $this->authorize('start', $ticket);

        // Only allow starting tickets that are pending or assigned
        if (!in_array($ticket->status, ['pending', 'assigned'])) {
            return response()->json([
                'message' => 'Ticket must be in pending or assigned status to start.',
                'current_status' => $ticket->status,
            ], 422);
        }

        $originalStatus = $ticket->status;

        $ticket->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        TicketEvent::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'event_type' => 'started',
            'meta' => [
                'status_from' => $originalStatus,
                'status_to' => 'in_progress',
            ],
        ]);

        return response()->json($ticket->load(['patient', 'department', 'assignee']));
    }

    /**
     * Complete a ticket.
     */
    public function complete(Request $request, Ticket $ticket)
    {
        $this->authorize('complete', $ticket);

        $originalStatus = $ticket->status;

        $status = $ticket->deadline && now()->isAfter($ticket->deadline) 
            ? 'closed_late' 
            : 'completed';

        // Calculate time spent in minutes if started_at exists
        $timeSpentMinutes = null;
        if ($ticket->started_at) {
            $timeSpentMinutes = now()->diffInMinutes($ticket->started_at);
        }

        $ticket->update([
            'status' => $status,
            'completed_at' => now(),
            'time_spent_minutes' => $timeSpentMinutes,
        ]);

        TicketEvent::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'event_type' => 'completed',
            'meta' => [
                'status_from' => $originalStatus,
                'status_to' => $status,
                'time_spent_minutes' => $timeSpentMinutes,
            ],
        ]);

        return response()->json($ticket->load(['patient', 'department', 'assignee']));
    }

    public function addNote(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $note = TicketNote::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        TicketEvent::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'event_type' => 'note_added',
            'meta' => [
                'note_id' => $note->id,
            ],
        ]);

        return response()->json($note->load('user'), 201);
    }

    /**
     * Remove the specified ticket.
     */
    public function destroy(Ticket $ticket)
    {
        $this->authorize('delete', $ticket);
        $ticket->delete();

        return response()->json(['message' => 'Ticket deleted']);
    }
}
