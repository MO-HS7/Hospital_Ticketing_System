<?php

namespace App\Policies;

use App\Models\Encounter;
use App\Models\User;

class EncounterPolicy
{
    /**
     * Determine whether the user can view any encounters.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view encounters (scoped in controller)
        return true;
    }

    /**
     * Determine whether the user can view the encounter.
     */
    public function view(User $user, Encounter $encounter): bool
    {
        // Patient can view their own encounters
        if ($user->id === $encounter->patient_id) {
            return true;
        }

        // Doctor/maintenance can view if they have a ticket in this encounter
        if ($user->hasRole(['doctor', 'maintenance'])) {
            return $encounter->tickets()->where('assigned_to', $user->id)->exists();
        }

        // Reception and admin can view all
        if ($user->hasRole(['reception', 'admin'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create encounters.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create encounters
        // (Controller will handle role-specific logic for patient_id)
        return true;
    }

    /**
     * Determine whether the user can update the encounter.
     */
    public function update(User $user, Encounter $encounter): bool
    {
        // Only reception and admin can update encounters
        // (Patients cannot modify after creation)
        return $user->hasRole(['reception', 'admin']);
    }

    /**
     * Determine whether the user can delete the encounter.
     */
    public function delete(User $user, Encounter $encounter): bool
    {
        // Only admin can delete encounters
        return $user->hasRole('admin');
    }
}
