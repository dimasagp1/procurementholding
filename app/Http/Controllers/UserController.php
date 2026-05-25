<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $this->authorize('view users');
        $users = User::with(['department', 'roles'])->paginate(10);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $this->authorize('create users');
        $departments = Department::where('is_active', true)->get();
        $roles = Role::all();
        return view('users.create', compact('departments', 'roles'));
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
        return view('users.edit', compact('user', 'departments', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('edit users');

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'employee_id' => ['required', 'string', 'unique:users,employee_id,' . $user->id],
            'department_id' => ['required', 'exists:departments,id'],
            'role' => ['required', 'exists:roles,name'],
            'phone' => ['nullable', 'string'],
            'position' => ['nullable', 'string'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'employee_id' => $request->employee_id,
            'department_id' => $request->department_id,
            'phone' => $request->phone,
            'position' => $request->position,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
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
