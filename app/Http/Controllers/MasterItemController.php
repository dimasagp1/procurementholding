<?php

namespace App\Http\Controllers;

use App\Models\MasterItem;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MasterItemImport;
use App\Exports\MasterItemTemplateExport;

class MasterItemController extends Controller
{
    public function index()
    {
        $search = request('search');

        $items = MasterItem::query()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('settings.master_items.index', compact('items'));
    }

    public function create()
    {
        return view('settings.master_items.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:master_items',
        ]);

        MasterItem::create($request->all());

        return redirect()->route('master-items.index')->with('success', 'Master Item created successfully.');
    }

    public function edit(MasterItem $masterItem)
    {
        return view('settings.master_items.edit', compact('masterItem'));
    }

    public function update(Request $request, MasterItem $masterItem)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:master_items,name,' . $masterItem->id,
        ]);

        $masterItem->update($request->all());

        return redirect()->route('master-items.index')->with('success', 'Master Item updated successfully.');
    }

    public function destroy(MasterItem $masterItem)
    {
        $masterItem->delete();
        return redirect()->route('master-items.index')->with('success', 'Master Item deleted successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
        ]);

        try {
            Excel::import(new MasterItemImport, $request->file('file'));
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
