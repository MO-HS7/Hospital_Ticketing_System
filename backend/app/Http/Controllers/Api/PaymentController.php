<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Encounter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Initiate a payment for an encounter (stub implementation).
     */
    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'encounter_id' => 'required|uuid|exists:encounters,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:online,pay_at_hospital',
        ]);

        $encounter = Encounter::findOrFail($validated['encounter_id']);

        // Check authorization
        if ($encounter->patient_id !== $request->user()->id && !$request->user()->hasRole(['reception', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // For pay_at_hospital, just mark as pending and return
        if ($validated['payment_method'] === 'pay_at_hospital') {
            $encounter->update([
                'payment_method' => 'pay_at_hospital',
                'payment_status' => 'pending',
                'payment_gateway' => 'none',
                'amount' => $validated['amount'],
                'initiated_at' => now(),
            ]);

            return response()->json([
                'payment_method' => 'pay_at_hospital',
                'amount' => $validated['amount'],
                'status' => 'pending',
                'message' => 'Payment will be collected at the hospital.',
                'requires_confirmation' => true,
            ], 201);
        }

        // For online payment (stub implementation)
        $paymentReference = 'PAY-' . strtoupper(Str::random(12));
        
        $encounter->update([
            'payment_status' => 'pending',
            'payment_reference' => $paymentReference,
            'payment_method' => $validated['payment_method'],
            'payment_gateway' => config('masar.payment_gateway', 'stub'),
            'amount' => $validated['amount'],
            'initiated_at' => now(),
        ]);

        // STUB: Return mock payment URL/instructions
        return response()->json([
            'payment_reference' => $paymentReference,
            'payment_url' => null, // Real gateway would provide URL
            'payment_method' => $validated['payment_method'],
            'amount' => $validated['amount'],
            'status' => 'pending',
            'message' => 'Payment initiated. In production, redirect user to payment gateway.',
            // For stub, frontend should immediately call confirm endpoint
            'stub_mode' => config('masar.payment_gateway') === 'stub',
        ], 201);
    }

    /**
     * Confirm a payment (stub auto-confirms, real gateway uses webhook).
     */
    public function confirm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_reference' => 'sometimes|string',
            'encounter_id' => 'sometimes|uuid|exists:encounters,id',
            'status' => 'required|in:paid,failed',
        ]);

        // Find encounter by reference or ID
        if (isset($validated['payment_reference'])) {
            $encounter = Encounter::where('payment_reference', $validated['payment_reference'])->firstOrFail();
        } else {
            $encounter = Encounter::findOrFail($validated['encounter_id']);
        }

        // Check authorization
        if ($encounter->patient_id !== $request->user()->id && !$request->user()->hasRole(['reception', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // STUB: Auto-confirm payment
        if ($validated['status'] === 'paid') {
            $encounter->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);

            return response()->json([
                'message' => 'Payment confirmed',
                'encounter' => $encounter,
            ]);
        } else {
            $encounter->update([
                'payment_status' => 'pending',
            ]);

            return response()->json([
                'message' => 'Payment failed',
                'encounter' => $encounter,
            ], 402);
        }
    }

    /**
     * Confirm a "pay_at_hospital" payment (reception/admin only).
     * Transitions associated tickets from awaiting_payment to assigned/pending.
     */
    public function confirmAtHospital(Request $request, string $encounterId): JsonResponse
    {
        $encounter = Encounter::findOrFail($encounterId);

        // Only reception or admin can confirm
        if (!$request->user()->hasRole(['reception', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Verify it's a pay_at_hospital encounter
        if ($encounter->payment_method !== 'pay_at_hospital') {
            return response()->json([
                'message' => 'This encounter is not set for pay at hospital.',
            ], 400);
        }

        // Mark encounter as paid
        $encounter->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        // Transition associated tickets from awaiting_payment → assigned/pending
        $transitionedTickets = [];
        $assignmentService = new \App\Services\DoctorAssignmentService();
        
        foreach ($encounter->tickets()->where('status', 'awaiting_payment')->get() as $ticket) {
            $newStatus = 'pending';
            $assignedTo = $ticket->assigned_to;
            
            // Run auto-assignment if enabled and ticket is an appointment
            if (config('masar.auto_assignment_enabled') && $ticket->type === 'appointment') {
                $assignedDoctor = $assignmentService->assignDoctor($ticket->department_id);
                
                if ($assignedDoctor) {
                    $assignedTo = $assignedDoctor->id;
                    $newStatus = 'assigned';
                }
            }
            
            $ticket->update([
                'status' => $newStatus,
                'assigned_to' => $assignedTo,
            ]);
            
            // Log the transition event
            \App\Models\TicketEvent::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'event_type' => 'payment_confirmed',
                'meta' => [
                    'previous_status' => 'awaiting_payment',
                    'new_status' => $newStatus,
                    'assigned_to' => $assignedTo,
                    'confirmed_by' => $request->user()->email,
                ],
            ]);
            
            $transitionedTickets[] = $ticket->fresh();
        }

        return response()->json([
            'message' => 'Payment confirmed at hospital',
            'encounter' => $encounter->load(['patient']),
            'tickets' => $transitionedTickets,
            'tickets_transitioned' => count($transitionedTickets),
        ]);
    }

    /**
     * Webhook endpoint for real payment gateways (placeholder).
     */
    public function webhook(Request $request): JsonResponse
    {
        // TODO: Implement webhook verification and parsing for real gateway
        // This endpoint should NOT require auth (gateway calls it)
        // Must verify webhook signature before processing
        
        return response()->json([
            'message' => 'Webhook endpoint placeholder',
            'status' => 'not_implemented',
        ], 501);
    }
}
