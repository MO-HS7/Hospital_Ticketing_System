<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    /**
     * Public: List active departments for authenticated users
     */
    public function index(Request $request)
    {
        $query = Department::active()->ordered();
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%{$search}%")
                  ->orWhere('name_ar', 'like', "%{$search}%");
            });
        }

        $departments = $query->get();
        
        return response()->json([
            'data' => $departments,
            'total' => $departments->count(),
        ]);
    }

    /**
     * Admin: List all departments with filters
     */
    public function adminIndex(Request $request)
    {
        $query = Department::query();

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%{$search}%")
                  ->orWhere('name_ar', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->get('sort_by', 'sort_order');
        $sortDir = $request->get('sort_dir', 'asc');
        $allowedSorts = ['name_en', 'name_ar', 'sort_order', 'created_at', 'updated_at', 'is_active'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        // Pagination
        $perPage = min($request->get('per_page', 20), 100);
        $departments = $query->paginate($perPage);

        return response()->json($departments);
    }

    /**
     * Admin: Create a new department
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_en' => 'required|string|min:2|max:255',
            'name_ar' => 'required|string|min:2|max:255',
            'description_en' => 'nullable|string|max:1000',
            'description_ar' => 'nullable|string|max:1000',
            'icon_key' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        // Generate slug from name_en
        $slug = Str::slug($validated['name_en']);
        $originalSlug = $slug;
        $counter = 1;
        while (Department::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        $department = Department::create([
            'name_en' => $validated['name_en'],
            'name_ar' => $validated['name_ar'],
            'slug' => $slug,
            'description_en' => $validated['description_en'] ?? null,
            'description_ar' => $validated['description_ar'] ?? null,
            'icon_key' => $validated['icon_key'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json([
            'message' => 'Department created successfully',
            'data' => $department,
        ], 201);
    }

    /**
     * Admin: Show a single department
     */
    public function show(Department $department)
    {
        return response()->json(['data' => $department]);
    }

    /**
     * Admin: Update a department
     */
    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name_en' => 'sometimes|required|string|min:2|max:255',
            'name_ar' => 'sometimes|required|string|min:2|max:255',
            'description_en' => 'nullable|string|max:1000',
            'description_ar' => 'nullable|string|max:1000',
            'icon_key' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        // Update slug if name_en changed
        if (isset($validated['name_en']) && $validated['name_en'] !== $department->name_en) {
            $slug = Str::slug($validated['name_en']);
            $originalSlug = $slug;
            $counter = 1;
            while (Department::where('slug', $slug)->where('id', '!=', $department->id)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }
            $validated['slug'] = $slug;
        }

        $department->update($validated);

        return response()->json([
            'message' => 'Department updated successfully',
            'data' => $department->fresh(),
        ]);
    }

    /**
     * Admin: Delete a department (prevent if has tickets)
     */
    public function destroy(Department $department)
    {
        // Check if department has tickets
        if ($department->tickets()->exists()) {
            return response()->json([
                'message' => 'Cannot delete department with existing tickets. Deactivate it instead.',
                'error' => 'has_tickets',
            ], 422);
        }

        $department->delete();

        return response()->json([
            'message' => 'Department deleted successfully',
        ]);
    }

    /**
     * Admin: Get department statistics
     */
    public function stats()
    {
        $stats = [
            'total' => Department::count(),
            'active' => Department::active()->count(),
            'inactive' => Department::where('is_active', false)->count(),
            'with_tickets' => Department::whereHas('tickets')->count(),
        ];

        return response()->json(['data' => $stats]);
    }
}
