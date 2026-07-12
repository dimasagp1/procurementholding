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
    // ─── Holding roles yang tidak terikat 1 company ───────────────────────────
    private const HOLDING_ROLES = ['superadmin', 'procurement_holding'];

    // ─── Roles yang punya akses kelola users & dept (termasuk company_admin) ──
    private const MANAGER_ROLES = ['superadmin', 'procurement_holding', 'company_admin'];

    // ─── Index ────────────────────────────────────────────────────────────────

    public function index()
    {
        $this->authorize('view users');

        $holdingRoles = self::HOLDING_ROLES;

        // Users holding-level (hanya untuk holding user yang login)
        $holdingUsers = collect();
        if (auth()->user()->hasAnyRole($holdingRoles)) {
            $holdingUsers = User::with(['department', 'roles', 'company'])
                ->whereHas('roles', fn($q) => $q->whereIn('name', $holdingRoles))
                ->orderBy('name')
                ->get();
        }

        // Companies dengan users-nya (exclude holding users)
        $companiesQuery = \App\Models\Company::with(['users' => function ($q) use ($holdingRoles) {
            $q->with(['department', 'roles'])
              ->whereDoesntHave('roles', fn($r) => $r->whereIn('name', $holdingRoles))
              ->orderBy('name');
        }])->where('is_active', true);

        // Non-holding user: hanya lihat company sendiri
        if (!auth()->user()->hasAnyRole(self::HOLDING_ROLES)) {
            $companiesQuery->where('id', auth()->user()->company_id);
        }

        $companies = $companiesQuery->orderBy('name')->get();

        return view('users.index', compact('holdingUsers', 'companies'));
    }

    // ─── Create ───────────────────────────────────────────────────────────────

    public function create()
    {
        $this->authorize('create users');

        $roles           = $this->getAvailableRoles();
        $companies       = $this->getAvailableCompanies();
        $departments     = $this->getDepartmentsForForm();
        $isCompanyLocked = $this->isCompanyLocked();
        $lockedCompanyId = $isCompanyLocked ? auth()->user()->company_id : null;

        return view('users.create', compact('roles', 'companies', 'departments', 'isCompanyLocked', 'lockedCompanyId'));
    }

    // ─── Store ────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $this->authorize('create users');

        $authUser        = auth()->user();
        $isHoldingUser   = $authUser->hasAnyRole(self::HOLDING_ROLES);
        $selectedRole    = $request->role;
        $isHoldingTarget = in_array($selectedRole, self::HOLDING_ROLES);

        // Validasi role yang dipilih harus dalam daftar yang diizinkan
        $allowedRoleNames = $this->getAvailableRoles()->pluck('name')->toArray();

        $rules = [
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'    => ['required', 'confirmed', Rules\Password::defaults()],
            'employee_id' => ['required', 'string', 'unique:users'],
            'role'        => ['required', 'string', 'in:' . implode(',', $allowedRoleNames)],
            'phone'       => ['nullable', 'string'],
            'position'    => ['nullable', 'string'],
        ];

        // Holding-target role (superadmin/procurement_holding) boleh tanpa company & dept
        if ($isHoldingTarget) {
            $rules['company_id']    = ['nullable', 'exists:companies,id'];
            $rules['department_id'] = ['nullable', 'exists:departments,id'];
        } else {
            $rules['company_id']    = ['required', 'exists:companies,id'];
            $rules['department_id'] = ['nullable', 'exists:departments,id'];
        }

        $request->validate($rules);

        // Non-holding user tidak bisa buat user di company lain
        if (!$isHoldingUser && $request->company_id && $request->company_id != $authUser->company_id) {
            abort(403, 'Anda tidak dapat membuat user di perusahaan lain.');
        }

        // Tentukan company_id final
        $companyId = $isHoldingUser ? $request->company_id : $authUser->company_id;

        $user = User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'employee_id'   => $request->employee_id,
            'department_id' => $request->department_id ?: null,
            'company_id'    => $companyId,
            'phone'         => $request->phone,
            'position'      => $request->position,
        ]);

        $user->assignRole($request->role);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    // ─── Edit ─────────────────────────────────────────────────────────────────

    public function edit(User $user)
    {
        $this->authorize('edit users');

        // Non-holding tidak bisa edit user dari company lain
        if (!auth()->user()->hasAnyRole(self::HOLDING_ROLES) &&
            $user->company_id != auth()->user()->company_id) {
            abort(403);
        }

        $roles           = $this->getAvailableRoles();
        $companies       = $this->getAvailableCompanies();
        $isCompanyLocked = $this->isCompanyLocked();
        $lockedCompanyId = $isCompanyLocked ? auth()->user()->company_id : null;

        // Department: load berdasarkan company user yang sedang di-edit
        $departments = $user->company_id
            ? Department::where('company_id', $user->company_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
            : collect();

        return view('users.edit', compact('user', 'roles', 'companies', 'departments', 'isCompanyLocked', 'lockedCompanyId'));
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, User $user)
    {
        $this->authorize('edit users');

        $authUser        = auth()->user();
        $isHoldingUser   = $authUser->hasAnyRole(self::HOLDING_ROLES);
        $selectedRole    = $request->role;
        $isHoldingTarget = in_array($selectedRole, self::HOLDING_ROLES);

        // Scope check
        if (!$isHoldingUser && $user->company_id != $authUser->company_id) {
            abort(403);
        }

        $allowedRoleNames = $this->getAvailableRoles()->pluck('name')->toArray();

        $rules = [
            'name'           => ['required', 'string', 'max:255'],
            'email'          => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'employee_id'    => ['required', 'string', 'unique:users,employee_id,' . $user->id],
            'role'           => ['required', 'string', 'in:' . implode(',', $allowedRoleNames)],
            'phone'          => ['nullable', 'string'],
            'position'       => ['nullable', 'string'],
            'password'       => ['nullable', 'confirmed', Rules\Password::defaults()],
            'signature_file' => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:2048'],
        ];

        if ($isHoldingTarget) {
            $rules['company_id']    = ['nullable', 'exists:companies,id'];
            $rules['department_id'] = ['nullable', 'exists:departments,id'];
        } else {
            $rules['company_id']    = ['required', 'exists:companies,id'];
            $rules['department_id'] = ['nullable', 'exists:departments,id'];
        }

        $request->validate($rules);

        // Non-holding tidak bisa pindah user ke company lain
        if (!$isHoldingUser && $request->company_id && $request->company_id != $authUser->company_id) {
            abort(403, 'Anda tidak dapat memindahkan user ke perusahaan lain.');
        }

        $companyId = $isHoldingUser ? $request->company_id : $authUser->company_id;

        $data = [
            'name'          => $request->name,
            'email'         => $request->email,
            'employee_id'   => $request->employee_id,
            'department_id' => $request->department_id ?: null,
            'company_id'    => $companyId,
            'phone'         => $request->phone,
            'position'      => $request->position,
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

    // ─── Destroy ──────────────────────────────────────────────────────────────

    public function destroy(User $user)
    {
        $this->authorize('delete users');

        if (!auth()->user()->hasAnyRole(self::HOLDING_ROLES) &&
            $user->company_id != auth()->user()->company_id) {
            abort(403);
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Cannot delete yourself.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    // ─── AJAX: Departments by Company ─────────────────────────────────────────

    public function getDepartmentsByCompany($companyId)
    {
        // Hanya holding-level yang bisa query company manapun
        if (!auth()->user()->hasAnyRole(self::HOLDING_ROLES)) {
            if ($companyId != auth()->user()->company_id) {
                abort(403);
            }
        }

        $departments = Department::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json($departments);
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Roles yang boleh di-assign oleh user yang login.
     */
    private function getAvailableRoles()
    {
        $authUser = auth()->user();

        if ($authUser->hasRole('superadmin')) {
            // Superadmin bisa assign semua role
            return Role::orderBy('name')->get();
        }

        if ($authUser->hasRole('procurement_holding')) {
            // Procurement holding tidak bisa buat superadmin
            return Role::where('name', '!=', 'superadmin')->orderBy('name')->get();
        }

        if ($authUser->hasRole('company_admin')) {
            // Company admin tidak bisa buat role holding-level
            return Role::whereNotIn('name', self::HOLDING_ROLES)->orderBy('name')->get();
        }

        // Role lain: hanya role non-holding & non-admin
        return Role::whereNotIn('name', [...self::HOLDING_ROLES, 'company_admin'])->orderBy('name')->get();
    }

    /**
     * Companies yang bisa dipilih oleh user yang login.
     */
    private function getAvailableCompanies()
    {
        $authUser = auth()->user();

        if ($authUser->hasAnyRole(self::HOLDING_ROLES)) {
            return \App\Models\Company::where('is_active', true)->orderBy('name')->get();
        }

        return \App\Models\Company::where('id', $authUser->company_id)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Department awal untuk form (hanya relevan bagi non-holding user).
     */
    private function getDepartmentsForForm()
    {
        $authUser = auth()->user();

        if ($authUser->hasAnyRole(self::HOLDING_ROLES)) {
            // Holding user: department di-load via AJAX setelah pilih company
            return collect();
        }

        return Department::where('company_id', $authUser->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Apakah pilihan company harus dikunci (non-holding user).
     */
    private function isCompanyLocked(): bool
    {
        return !auth()->user()->hasAnyRole(self::HOLDING_ROLES);
    }
}
