<?php

namespace App\Http\Controllers;

use App\Models\MasterItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MasterItemImport;
use App\Exports\MasterItemTemplateExport;

class MasterItemController extends Controller
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

        $items = MasterItem::query()
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $company = $companyId ? \App\Models\Company::find($companyId) : null;

        return view('settings.master_items.index', compact('items', 'company'));
    }

    public function create()
    {
        $companyId = $this->getActiveCompanyId();
        $company = $companyId ? \App\Models\Company::find($companyId) : null;
        return view('settings.master_items.create', compact('company'));
    }

    public function store(Request $request)
    {
        $companyId = $this->getActiveCompanyId();

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('master_items')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                })
            ],
        ]);

        $data = $request->all();
        $data['company_id'] = $companyId;

        MasterItem::create($data);

        return redirect()->route('master-items.index')->with('success', 'Master Item created successfully.');
    }

    public function edit(MasterItem $masterItem)
    {
        $companyId = $this->getActiveCompanyId();
        if ($masterItem->company_id !== $companyId && !Auth::user()->hasAnyRole(['superadmin', 'procurement_holding'])) {
            abort(403, 'Unauthorized action.');
        }

        $company = $masterItem->company_id ? \App\Models\Company::find($masterItem->company_id) : null;
        return view('settings.master_items.edit', compact('masterItem', 'company'));
    }

    public function update(Request $request, MasterItem $masterItem)
    {
        $companyId = $masterItem->company_id;
        if ($companyId !== $this->getActiveCompanyId() && !Auth::user()->hasAnyRole(['superadmin', 'procurement_holding'])) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('master_items')->ignore($masterItem->id)->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId);
                })
            ],
        ]);

        $masterItem->update($request->all());

        return redirect()->route('master-items.index')->with('success', 'Master Item updated successfully.');
    }

    public function destroy(MasterItem $masterItem)
    {
        if ($masterItem->company_id !== $this->getActiveCompanyId() && !Auth::user()->hasAnyRole(['superadmin', 'procurement_holding'])) {
            abort(403, 'Unauthorized action.');
        }

        $masterItem->delete();
        return redirect()->route('master-items.index')->with('success', 'Master Item deleted successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
        ]);

        $companyId = $this->getActiveCompanyId();

        try {
            Excel::import(new MasterItemImport($companyId), $request->file('file'));
            return redirect()->route('master-items.index')->with('success', 'Master Items imported successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error importing items: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        return Excel::download(new MasterItemTemplateExport, 'Template_Master_Item.xlsx');
    }
}
