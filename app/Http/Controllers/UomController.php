<?php

namespace App\Http\Controllers;

use App\Models\Uom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UomController extends Controller
{
    private function getActiveCompanyId()
    {
        $user = Auth::user();
        $activeCompanyId = session('active_company_id');
        
        if (!$user->hasAnyRole(['superadmin', 'procurement_holding'])) {
            return $user->company_id;
        }
        
        if ($activeCompanyId) {
            return $activeCompanyId;
        }
        
        return $user->company_id;
    }

    public function index()
    {
        $search = request('search');
        $companyId = $this->getActiveCompanyId();

        $uoms = Uom::query()
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $company = $companyId ? \App\Models\Company::find($companyId) : null;

        return view('settings.uom.index', compact('uoms', 'company'));
    }

    public function create()
    {
        $companyId = $this->getActiveCompanyId();
        $company = $companyId ? \App\Models\Company::find($companyId) : null;
        return view('settings.uom.create', compact('company'));
    }

    public function store(Request $request)
    {
        $companyId = $this->getActiveCompanyId();

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('uoms')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                })
            ],
            'description' => 'nullable|string|max:255',
        ]);

        $data = $request->all();
        $data['company_id'] = $companyId;
        
        Uom::create($data);

        return redirect()->route('uoms.index')->with('success', 'UOM created successfully.');
    }

    public function edit(Uom $uom)
    {
        $companyId = $this->getActiveCompanyId();
        if ($uom->company_id !== $companyId && !Auth::user()->hasAnyRole(['superadmin', 'procurement_holding'])) {
            abort(403, 'Unauthorized action.');
        }

        $company = $uom->company_id ? \App\Models\Company::find($uom->company_id) : null;
        return view('settings.uom.edit', compact('uom', 'company'));
    }

    public function update(Request $request, Uom $uom)
    {
        $companyId = $uom->company_id;
        if ($companyId !== $this->getActiveCompanyId() && !Auth::user()->hasAnyRole(['superadmin', 'procurement_holding'])) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('uoms')->ignore($uom->id)->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                })
            ],
            'description' => 'nullable|string|max:255',
        ]);

        $uom->update($request->all());

        return redirect()->route('uoms.index')->with('success', 'UOM updated successfully.');
    }

    public function destroy(Uom $uom)
    {
        if ($uom->company_id !== $this->getActiveCompanyId() && !Auth::user()->hasAnyRole(['superadmin', 'procurement_holding'])) {
            abort(403, 'Unauthorized action.');
        }

        $uom->delete();
        return redirect()->route('uoms.index')->with('success', 'UOM deleted successfully.');
    }
}
