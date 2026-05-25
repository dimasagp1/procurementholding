<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $this->authorize('view departments');
        $departments = Department::paginate(10);
        return view('departments.index', compact('departments'));
    }

    public function create()
    {
        $this->authorize('create departments');
        return view('departments.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create departments');
        
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:departments,code',
            'name' => 'required|string|max:255',
            'manager' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        Department::create($validated);

        return redirect()->route('departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function edit(Department $department)
    {
        $this->authorize('edit departments');
        return view('departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department)
    {
        $this->authorize('edit departments');

        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:departments,code,' . $department->id,
            'name' => 'required|string|max:255',
            'manager' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $department->update($validated);

        return redirect()->route('departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        $this->authorize('delete departments');
        
        $department->delete();

        return redirect()->route('departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}
