<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class DepartmentController extends Controller
{
    /**
     * Public: List active departments for authenticated users
     * Cached for 5 minutes to reduce DB load
     */
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $cacheKey = 'departments:active:' . md5($search);
        
        // Cache for 5 minutes (300 seconds)
        $result = Cache::remember($cacheKey, 300, function () use ($search) {
            $query = Department::active()->ordered()
                ->select(['id', 'name_en', 'name_ar', 'slug', 'icon_key', 'sort_order']);
            
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name_en', 'like', "%{$search}%")
                      ->orWhere('name_ar', 'like', "%{$search}%");
                });
            }

            $departments = $query->get();
            
            return [
                'data' => $departments,
                'total' => $departments->count(),
            ];
        });
        
        return response()->json($result);
    }

    /**
     * Admin: List all departments with filters
     */
    public function adminIndex(Request $request)
    {
        $query = Department::query()
            ->select(['id', 'name_en', 'name_ar', 'slug', 'description_en', 'description_ar', 'icon_key', 'is_active', 'sort_order', 'created_at', 'updated_at']);

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search
        if ($request->filled('search')) {
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

        // Clear cache
        Cache::forget('departments:active:' . md5(''));
        Cache::forget('admin:metrics');

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

        // Clear cache
        Cache::forget('departments:active:' . md5(''));

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

        // Clear cache
        Cache::forget('departments:active:' . md5(''));
        Cache::forget('admin:metrics');

        return response()->json([
            'message' => 'Department deleted successfully',
        ]);
    }

    /**
     * Admin: Get department statistics (cached)
     */
    public function stats()
    {
        $stats = Cache::remember('departments:stats', 60, function () {
            return [
                'total' => Department::count(),
                'active' => Department::active()->count(),
                'inactive' => Department::where('is_active', false)->count(),
                'with_tickets' => Department::whereHas('tickets')->count(),
            ];
        });

        return response()->json(['data' => $stats]);
    }
}
