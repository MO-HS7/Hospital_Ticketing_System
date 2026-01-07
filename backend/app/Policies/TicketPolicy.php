<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketPolicy
{
    use HandlesAuthorization;

    private function roleAllowsTicketType(User $user, Ticket $ticket): bool
    {
        if ($user->hasRole('doctor')) {
            return $ticket->type === 'appointment';
        }

        if ($user->hasRole('maintenance')) {
            return $ticket->type === 'maintenance';
        }

        if ($user->hasRole('reception')) {
            return $ticket->type === 'appointment';
        }

        return true;
    }

    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('tickets.view') || $user->can('tickets.view_all');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if (!$this->roleAllowsTicketType($user, $ticket)) {
            return false;
        }

        if ($user->hasRole('doctor') && $ticket->status === 'awaiting_payment') {
            return false;
        }

        if ($user->hasRole('reception')) {
            if ($ticket->status === 'awaiting_payment') {
                return true;
            }

            return (int) $ticket->creator_id === (int) $user->id;
        }

        if ($user->hasRole('patient')) {
            return $user->can('tickets.view') && (int) $ticket->patient_id === (int) $user->id;
        }

        if ($user->can('tickets.view_all')) {
            return true;
        }

        return (int) $ticket->assigned_to === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('tickets.create');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if (!$this->roleAllowsTicketType($user, $ticket)) {
            return false;
        }

        if (!$user->can('tickets.update')) {
            return false;
        }

        if ($user->hasRole('patient')) {
            return false;
        }

        if ($user->can('tickets.view_all')) {
            return true;
        }

        return (int) $ticket->assigned_to === (int) $user->id;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        if (!$this->roleAllowsTicketType($user, $ticket)) {
            return false;
        }

        if (!$user->can('tickets.delete')) {
            return false;
        }

        if ($user->hasRole('patient')) {
            return false;
        }

        return true;
    }

    public function accept(User $user, Ticket $ticket): bool
    {
        if (!$this->roleAllowsTicketType($user, $ticket)) {
            return false;
        }

        if (!$user->can('tickets.accept')) {
            return false;
        }

        if (!in_array($ticket->status, ['pending', 'assigned'], true)) {
            return false;
        }

        if ($ticket->assigned_to !== null && (int) $ticket->assigned_to !== (int) $user->id) {
            return false;
        }

        return true;
    }

    public function complete(User $user, Ticket $ticket): bool
    {
        if (!$this->roleAllowsTicketType($user, $ticket)) {
            return false;
        }

        if (!$user->can('tickets.complete')) {
            return false;
        }

        if ($ticket->status !== 'in_progress') {
            return false;
        }

        return (int) $ticket->assigned_to === (int) $user->id;
    }

    /**
     * Determine if the user can start working on the ticket.
     * 
     * Start is used when a staff member begins work on an ALREADY ASSIGNED ticket.
     * The ticket must be in 'pending' or 'assigned' status and assigned to the current user.
     */
    public function start(User $user, Ticket $ticket): bool
    {
        if (!$this->roleAllowsTicketType($user, $ticket)) {
            return false;
        }

        if (!$user->can('tickets.accept')) {
            return false;
        }

        // Only allow starting tickets that are pending or assigned
        if (!in_array($ticket->status, ['pending', 'assigned'], true)) {
            return false;
        }

        // Ticket must be assigned to the current user
        return (int) $ticket->assigned_to === (int) $user->id;
    }
}
