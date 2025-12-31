<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    /**
     * Display a listing of departments.
     */
    public function index()
    {
        $departments = Department::where('is_active', true)->get();
        return response()->json($departments);
    }

    /**
     * Store a newly created department.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
            'name_ar' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:10',
        ]);

        $department = Department::create($request->only(['name', 'name_ar', 'description', 'icon']));

        return response()->json($department, 201);
    }

    /**
     * Display the specified department.
     */
    public function show(Department $department)
    {
        return response()->json($department);
    }

    /**
     * Update the specified department.
     */
    public function update(Request $request, Department $department)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255|unique:departments,name,' . $department->id,
            'name_ar' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:10',
            'is_active' => 'nullable|boolean',
        ]);

        $department->update($request->only(['name', 'name_ar', 'description', 'icon', 'is_active']));

        return response()->json($department);
    }

    /**
     * Remove the specified department.
     */
    public function destroy(Department $department)
    {
        $department->delete();
        return response()->json(['message' => 'Department deleted']);
    }
}
