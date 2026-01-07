<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Middleware to automatically inject patient_id for authenticated patients.
 * This ensures patients can create tickets without explicitly sending patient_id,
 * while staff must still provide it for appointment tickets created on behalf of patients.
 */
class ForcePatientIdForPatients
{
    /**
     * Staff roles that create tickets on behalf of patients.
     */
    private array $staffRoles = [
        'admin', 'reception', 'doctor', 'maintenance', 
        'lab_technician', 'radiologist', 'pharmacist'
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        // DEBUG: Log for troubleshooting - REMOVE AFTER FIX
        Log::info('ForcePatientIdForPatients MIDDLEWARE', [
            'path' => $request->path(),
            'method' => $request->method(),
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_roles' => $user?->roles?->pluck('name')->toArray() ?? [],
            'has_patient_id' => $request->has('patient_id'),
            'patient_id_value' => $request->input('patient_id'),
            'type' => $request->input('type'),
        ]);
        
        // Only process for authenticated users
        if (!$user) {
            return $next($request);
        }

        // Force-load roles to avoid caching issues
        $user->loadMissing('roles');
        
        // Check if user is staff
        $isStaff = $user->hasAnyRole($this->staffRoles);
        
        // If user is NOT staff (i.e., is a patient or unknown role),
        // automatically inject their user ID as patient_id
        if (!$isStaff && !$request->has('patient_id')) {
            $request->merge(['patient_id' => $user->id]);
            
            Log::info('ForcePatientIdForPatients: Injected patient_id', [
                'user_id' => $user->id,
                'injected_patient_id' => $user->id,
            ]);
        }

        return $next($request);
    }
}
