<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Encounter;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Create an order for a ticket.
     */
    public function store(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('create', Order::class);

        // Validate request
        $validated = $request->validate([
            'type' => 'required|in:lab,radiology,pharmacy',
            'items' => 'required|array|min:1',
            'items.*' => 'required|string|max:255',
            'instructions' => 'nullable|string|max:2000',
        ]);

        // Check doctor is assigned to this ticket
        if ($ticket->assigned_to !== $request->user()->id) {
            return response()->json([
                'message' => 'You can only create orders for tickets assigned to you.',
                'error_code' => 'not_assigned',
            ], 403);
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
            
            // Update ticket to use this encounter
            $ticket->update(['encounter_id' => $encounterId]);
        }

        // Create order with UUID
        $order = Order::create([
            'id' => Str::uuid()->toString(),
            'encounter_id' => $encounterId,
            'ticket_id' => $ticket->id,
            'type' => $validated['type'],
            'status' => 'pending',
            'items' => $validated['items'],
            'instructions' => $validated['instructions'] ?? null,
            'ordered_by' => $request->user()->id,
        ]);

        // Create ticket event
        TicketEvent::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'event_type' => 'order_created',
            'meta' => [
                'order_id' => $order->id,
                'order_type' => $validated['type'],
                'items_count' => count($validated['items']),
            ],
        ]);

        return response()->json([
            'order' => $order->load(['orderedBy', 'ticket']),
            'message' => 'Order created successfully.',
        ], 201);
    }

    /**
     * List orders with staff scoping.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $user = $request->user();
        $query = Order::with(['orderedBy', 'processedBy', 'ticket', 'encounter']);

        // Apply role-based scoping
        if ($user->hasRole('lab_technician')) {
            $query->where('type', 'lab');
        } elseif ($user->hasRole('radiologist')) {
            $query->where('type', 'radiology');
        } elseif ($user->hasRole('pharmacist')) {
            $query->where('type', 'pharmacy');
        } elseif ($user->hasRole('doctor')) {
            $query->where('ordered_by', $user->id);
        } elseif ($user->hasRole('patient')) {
            $query->whereHas('encounter', fn($q) => $q->where('patient_id', $user->id));
        }
        // Admin/reception see all

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('encounter_id')) {
            $query->where('encounter_id', $request->encounter_id);
        }
        if ($request->filled('ticket_id')) {
            $query->where('ticket_id', $request->ticket_id);
        }

        return response()->json($query->latest()->paginate(15));
    }

    /**
     * Get a specific order.
     */
    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->load(['orderedBy', 'processedBy', 'ticket', 'encounter']);

        return response()->json($order);
    }

    /**
     * Update order status/results.
     */
    public function update(Request $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'status' => 'sometimes|in:processing,completed,cancelled',
            'results' => 'nullable|array',
        ]);

        // Validate status transitions
        $currentStatus = $order->status;
        $newStatus = $validated['status'] ?? $currentStatus;

        $validTransitions = [
            'pending' => ['processing', 'cancelled'],
            'processing' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];

        if ($newStatus !== $currentStatus && !in_array($newStatus, $validTransitions[$currentStatus])) {
            return response()->json([
                'message' => "Invalid status transition from {$currentStatus} to {$newStatus}.",
                'error_code' => 'invalid_transition',
            ], 422);
        }

        // Update order
        $updateData = [];
        
        if (isset($validated['status'])) {
            $updateData['status'] = $validated['status'];
            $updateData['processed_by'] = $request->user()->id;

            if ($validated['status'] === 'completed') {
                $updateData['completed_at'] = now();
            }
        }

        if (isset($validated['results'])) {
            $updateData['results'] = $validated['results'];
        }

        $order->update($updateData);

        // Create ticket event
        $eventType = match($newStatus) {
            'processing' => 'order_processing',
            'completed' => 'order_completed',
            'cancelled' => 'order_cancelled',
            default => null,
        };

        if ($eventType && $newStatus !== $currentStatus) {
            TicketEvent::create([
                'ticket_id' => $order->ticket_id,
                'user_id' => $request->user()->id,
                'event_type' => $eventType,
                'meta' => [
                    'order_id' => $order->id,
                    'order_type' => $order->type,
                    'status_from' => $currentStatus,
                    'status_to' => $newStatus,
                ],
            ]);
        }

        return response()->json([
            'order' => $order->fresh()->load(['orderedBy', 'processedBy', 'ticket']),
            'message' => 'Order updated successfully.',
        ]);
    }

    /**
     * Get orders for an encounter.
     */
    public function encounterOrders(Request $request, Encounter $encounter): JsonResponse
    {
        $user = $request->user();

        // Check access
        if ($user->hasRole('patient') && $encounter->patient_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->hasRole('doctor')) {
            // Doctor must be assigned to at least one ticket in the encounter
            $hasAccess = $encounter->tickets()->where('assigned_to', $user->id)->exists();
            if (!$hasAccess) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $orders = Order::with(['orderedBy', 'processedBy', 'ticket'])
            ->where('encounter_id', $encounter->id)
            ->latest()
            ->get();

        return response()->json($orders);
    }
}
