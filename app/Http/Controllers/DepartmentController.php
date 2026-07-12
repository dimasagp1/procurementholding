<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Company;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    // Roles yang tidak terikat 1 company
    private const HOLDING_ROLES = ['superadmin', 'procurement_holding'];

    public function index()
    {
        $this->authorize('view departments');

        $authUser = auth()->user();
        $query    = Department::with('company');

        if ($authUser->hasAnyRole(self::HOLDING_ROLES)) {
            // Holding: bisa filter via session active_company_id
            $activeCompanyId = session('active_company_id');
            if ($activeCompanyId) {
                $query->where('company_id', $activeCompanyId);
            }
        } else {
            // Company-level (termasuk company_admin): hanya company sendiri
            $query->where('company_id', $authUser->company_id);
        }

        $departments = $query->orderBy('name')->paginate(10);
        return view('departments.index', compact('departments'));
    }

    public function create()
    {
        $this->authorize('create departments');

        $authUser     = auth()->user();
        $companies    = $this->getAvailableCompanies($authUser);
        $isLocked     = !$authUser->hasAnyRole(self::HOLDING_ROLES);
        $lockedCompanyId = $isLocked ? $authUser->company_id : null;

        return view('departments.create', compact('companies', 'isLocked', 'lockedCompanyId'));
    }

    public function store(Request $request)
    {
        $this->authorize('create departments');

        $authUser = auth()->user();
        $isHolding = $authUser->hasAnyRole(self::HOLDING_ROLES);

        $validated = $request->validate([
            'company_id'  => 'required|exists:companies,id',
            'code'        => 'required|string|max:10|unique:departments,code',
            'name'        => 'required|string|max:255',
            'manager'     => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        // Non-holding tidak bisa buat dept di company lain
        if (!$isHolding && $validated['company_id'] != $authUser->company_id) {
            abort(403, 'Anda tidak dapat membuat department di perusahaan lain.');
        }

        // Paksa company_id untuk non-holding
        if (!$isHolding) {
            $validated['company_id'] = $authUser->company_id;
        }

        Department::create($validated);

        return redirect()->route('departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function edit(Department $department)
    {
        $this->authorize('edit departments');

        $authUser  = auth()->user();
        $isHolding = $authUser->hasAnyRole(self::HOLDING_ROLES);

        // Non-holding tidak bisa edit dept dari company lain
        if (!$isHolding && $department->company_id != $authUser->company_id) {
            abort(403);
        }

        $companies    = $this->getAvailableCompanies($authUser);
        $isLocked     = !$isHolding;
        $lockedCompanyId = $isLocked ? $authUser->company_id : null;

        return view('departments.edit', compact('department', 'companies', 'isLocked', 'lockedCompanyId'));
    }

    public function update(Request $request, Department $department)
    {
        $this->authorize('edit departments');

        $authUser  = auth()->user();
        $isHolding = $authUser->hasAnyRole(self::HOLDING_ROLES);

        // Non-holding tidak bisa update dept dari company lain
        if (!$isHolding && $department->company_id != $authUser->company_id) {
            abort(403);
        }

        $validated = $request->validate([
            'company_id'  => 'required|exists:companies,id',
            'code'        => 'required|string|max:10|unique:departments,code,' . $department->id,
            'name'        => 'required|string|max:255',
            'manager'     => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        // Paksa company_id untuk non-holding
        if (!$isHolding) {
            $validated['company_id'] = $authUser->company_id;
        }

        $department->update($validated);

        return redirect()->route('departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        $this->authorize('delete departments');

        $authUser  = auth()->user();
        $isHolding = $authUser->hasAnyRole(self::HOLDING_ROLES);

        if (!$isHolding && $department->company_id != $authUser->company_id) {
            abort(403);
        }

        $department->delete();

        return redirect()->route('departments.index')
            ->with('success', 'Department deleted successfully.');
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    private function getAvailableCompanies($authUser)
    {
        if ($authUser->hasAnyRole(self::HOLDING_ROLES)) {
            return Company::where('is_active', true)->orderBy('name')->get();
        }

        // company_admin & role lain: hanya company sendiri
        return Company::where('id', $authUser->company_id)
            ->where('is_active', true)
            ->get();
    }
}
