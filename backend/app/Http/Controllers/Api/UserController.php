<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function doctors(Request $request)
    {
        $query = User::role('doctor')->select(['id', 'name', 'email', 'phone', 'department_id']);

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function patients(Request $request)
    {
        $query = User::role('patient')->select(['id', 'name', 'email', 'phone']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return response()->json($query->orderBy('name')->limit(20)->get());
    }

    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::with(['roles', 'department']);

        if ($request->filled('role')) {
            $query->role($request->role);
        }

        return response()->json($query->paginate(15));
    }

    /**
     * Store a newly created user (staff account).
     */
    public function store(Request $request)
    {
        // Staff roles that require a department
        $staffRoles = ['doctor', 'maintenance', 'reception', 'lab_technician', 'radiologist', 'pharmacist'];
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:' . implode(',', $staffRoles),
            'department_id' => 'required|exists:departments,id',
        ]);

        $activationToken = Str::random(64);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make(Str::random(16)), // Temporary password
            'activation_token' => $activationToken,
            'is_active' => false,
            'department_id' => $request->department_id,
        ]);

        $user->assignRole($request->role);

        // TODO: Send activation email with token

        return response()->json([
            'user' => $user->load(['roles', 'department']),
            'activation_link' => config('app.frontend_url', 'http://localhost:3000') . '/staff/activate?token=' . $activationToken,
        ], 201);
    }


    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return response()->json($user->load('roles', 'permissions'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $user->update($request->only(['name', 'phone', 'is_active', 'department_id']));

        return response()->json($user->load('roles'));
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'User deleted']);
    }

    /**
     * Activate a user account.
     */
    public function activate(User $user)
    {
        $user->update(['is_active' => true]);
        return response()->json($user->load('roles'));
    }
}
