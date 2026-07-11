<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $this->authorize('view users');

        $holdingRoles = ['superadmin', 'procurement_holding'];

        // Users with holding-level roles (no company scoping)
        $holdingUsers = User::with(['department', 'roles', 'company'])
            ->whereHas('roles', fn($q) => $q->whereIn('name', $holdingRoles))
            ->orderBy('name')
            ->get();

        // All active companies with their users (excluding holding users)
        $companies = \App\Models\Company::with(['users' => function ($q) use ($holdingRoles) {
            $q->with(['department', 'roles'])
              ->whereDoesntHave('roles', fn($r) => $r->whereIn('name', $holdingRoles))
              ->orderBy('name');
        }])
        ->where('is_active', true)
        ->orderBy('name')
        ->get();

        return view('users.index', compact('holdingUsers', 'companies'));
    }

    public function create()
    {
        $this->authorize('create users');
        $departments = Department::where('is_active', true)->get();
        $roles = Role::all();
        $companies = \App\Models\Company::where('is_active', true)->get();
        return view('users.create', compact('departments', 'roles', 'companies'));
    }

    public function store(Request $request)
    {
        $this->authorize('create users');

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'employee_id' => ['required', 'string', 'unique:users'],
            'department_id' => ['required', 'exists:departments,id'],
            'company_id' => ['required', 'exists:companies,id'],
            'role' => ['required', 'exists:roles,name'],
            'phone' => ['nullable', 'string'],
            'position' => ['nullable', 'string'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'employee_id' => $request->employee_id,
            'department_id' => $request->department_id,
            'company_id' => $request->company_id,
            'phone' => $request->phone,
            'position' => $request->position,
        ]);

        $user->assignRole($request->role);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $this->authorize('edit users');
        $departments = Department::where('is_active', true)->get();
        $roles = Role::all();
        $companies = \App\Models\Company::where('is_active', true)->get();
        return view('users.edit', compact('user', 'departments', 'roles', 'companies'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('edit users');

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'employee_id' => ['required', 'string', 'unique:users,employee_id,' . $user->id],
            'department_id' => ['required', 'exists:departments,id'],
            'company_id' => ['required', 'exists:companies,id'],
            'role' => ['required', 'exists:roles,name'],
            'phone' => ['nullable', 'string'],
            'position' => ['nullable', 'string'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'signature_file' => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'employee_id' => $request->employee_id,
            'department_id' => $request->department_id,
            'company_id' => $request->company_id,
            'phone' => $request->phone,
            'position' => $request->position,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        // Handle signature: upload new or clear existing
        if ($request->hasFile('signature_file')) {
            if ($user->signature_path && Storage::disk('public')->exists($user->signature_path)) {
                Storage::disk('public')->delete($user->signature_path);
            }
            $data['signature_path'] = $request->file('signature_file')->store('signatures', 'public');
        } elseif ($request->boolean('clear_signature') && $user->signature_path) {
            if (Storage::disk('public')->exists($user->signature_path)) {
                Storage::disk('public')->delete($user->signature_path);
            }
            $data['signature_path'] = null;
        }

        $user->update($data);

        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete users');
        
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Cannot delete yourself.');
        }
        
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}
