<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Encounter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EncounterController extends Controller
{
    /**
     * Display a listing of encounters (scoped by role).
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $query = Encounter::with(['patient', 'tickets.department', 'tickets.assignee']);

        // Scope by role
        if ($user->hasRole('patient')) {
            // Patients see only their own encounters
            $query->where('patient_id', $user->id);
        } elseif ($user->hasRole(['doctor', 'maintenance'])) {
            // Doctors/maintenance see encounters with tickets assigned to them
            $query->whereHas('tickets', function ($q) use ($user) {
                $q->where('assigned_to', $user->id);
            });
        } elseif ($user->hasRole('reception')) {
            // Reception can see all encounters (for patient check-in)
            // No additional filter
        } elseif ($user->hasRole('admin')) {
            // Admin sees everything
            // No additional filter
        } else {
            // Unknown role - restrict to none
            $query->whereRaw('1 = 0');
        }

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $encounters = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($encounters);
    }

    /**
     * Store a newly created encounter.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Encounter::class);

        $user = $request->user();
        $user->loadMissing('roles');
        
        // Staff roles that create encounters on behalf of patients
        $staffRoles = ['admin', 'reception', 'doctor', 'maintenance', 'lab_technician', 'radiologist', 'pharmacist'];
        $isStaff = $user->hasAnyRole($staffRoles);

        // CRITICAL: Log at entry point
        \Log::info('>>> EncounterController@store ENTRY', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_roles' => $user->roles->pluck('name')->toArray(),
            'isStaff' => $isStaff,
        ]);

        // CRITICAL FIX: For non-staff (patients), inject patient_id BEFORE any validation
        if (!$isStaff) {
            $request->merge(['patient_id' => $user->id]);
            \Log::info('EncounterController@store: Injected patient_id for non-staff', [
                'patient_id' => $user->id,
            ]);
        }

        // Build validation rules - only require patient_id for STAFF
        $validationRules = [
            'source' => 'sometimes|in:patient,reception,doctor,system',
            'is_emergency' => 'sometimes|boolean',
            'amount' => 'sometimes|numeric|min:0',
        ];

        // Only add patient_id validation for STAFF users
        if ($isStaff) {
            $validationRules['patient_id'] = 'required|exists:users,id';
        }
        // For non-staff, patient_id is NOT in validation rules (already injected above)

        \Log::info('EncounterController@store: Validation rules', [
            'isStaff' => $isStaff,
            'patient_id_rule' => $validationRules['patient_id'] ?? 'NOT_VALIDATED',
        ]);

        $validated = $request->validate($validationRules);
        
        // Use the injected patient_id for non-staff
        $validated['patient_id'] = $request->patient_id;


        // Set defaults
        $validated['id'] = Str::uuid();
        $validated['status'] = 'active';
        $validated['payment_status'] = $validated['is_emergency'] ?? false ? 'waived' : 'pending';
        $validated['source'] = $validated['source'] ?? ($isStaff ? 'reception' : 'patient');
        $validated['started_at'] = now();

        $encounter = Encounter::create($validated);
        $encounter->load(['patient', 'tickets']);

        return response()->json($encounter, 201);
    }

    /**
     * Display the specified encounter.
     */
    public function show(string $id): JsonResponse
    {
        $encounter = Encounter::with([
            'patient',
            'tickets.department',
            'tickets.assignee',
            'tickets.notes.user',
            'tickets.events'
        ])->findOrFail($id);

        $this->authorize('view', $encounter);

        return response()->json($encounter);
    }

    /**
     * Update the specified encounter.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $encounter = Encounter::findOrFail($id);
        $this->authorize('update', $encounter);

        $validated = $request->validate([
            'status' => 'sometimes|in:active,completed,cancelled',
            'payment_status' => 'sometimes|in:pending,paid,waived,refunded',
            'payment_reference' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0',
            'ended_at' => 'sometimes|date',
        ]);

        $encounter->update($validated);
        $encounter->load(['patient', 'tickets']);

        return response()->json($encounter);
    }
}
