<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Department;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * Display a listing of tickets.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Ticket::with(['patient', 'department', 'assignee']);

        // Filter based on role
        if ($user->hasRole('patient')) {
            $query->where('patient_id', $user->id);
        } elseif ($user->hasRole(['doctor', 'maintenance', 'reception'])) {
            // Staff see tickets for their department or assigned to them
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id);
            });
        }
        // Admin sees all

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        return response()->json($query->latest()->paginate(15));
    }

    /**
     * Store a new ticket.
     */
    public function store(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'type' => 'required|in:appointment,maintenance',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_at' => 'nullable|date|after:now',
            'priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        $ticket = Ticket::create([
            'patient_id' => $request->user()->id,
            'department_id' => $request->department_id,
            'type' => $request->type,
            'subject' => $request->subject,
            'description' => $request->description,
            'scheduled_at' => $request->scheduled_at,
            'priority' => $request->priority ?? 'medium',
            'status' => 'pending',
            'deadline' => $request->scheduled_at ? 
                now()->parse($request->scheduled_at)->addHours(2) : 
                now()->addDays(1),
        ]);

        return response()->json($ticket->load(['department']), 201);
    }

    /**
     * Display the specified ticket.
     */
    public function show(Ticket $ticket)
    {
        $this->authorize('view', $ticket);
        return response()->json($ticket->load(['patient', 'department', 'assignee']));
    }

    /**
     * Update the specified ticket.
     */
    public function update(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $request->validate([
            'status' => 'nullable|in:pending,in_progress,completed,overdue,closed_late',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $ticket->update($request->only(['status', 'priority', 'assigned_to']));

        return response()->json($ticket->load(['patient', 'department', 'assignee']));
    }

    /**
     * Accept a ticket.
     */
    public function accept(Request $request, Ticket $ticket)
    {
        $ticket->update([
            'assigned_to' => $request->user()->id,
            'status' => 'in_progress',
            'accepted_at' => now(),
        ]);

        return response()->json($ticket->load(['patient', 'department', 'assignee']));
    }

    /**
     * Complete a ticket.
     */
    public function complete(Request $request, Ticket $ticket)
    {
        $status = $ticket->deadline && now()->isAfter($ticket->deadline) 
            ? 'closed_late' 
            : 'completed';

        $ticket->update([
            'status' => $status,
            'completed_at' => now(),
        ]);

        return response()->json($ticket->load(['patient', 'department', 'assignee']));
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
