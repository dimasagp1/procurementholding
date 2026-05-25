<?php

namespace App\Http\Controllers;

use App\Models\Uom;
use Illuminate\Http\Request;

class UomController extends Controller
{
    public function index()
    {
        $search = request('search');

        $uoms = Uom::query()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('settings.uom.index', compact('uoms'));
    }

    public function create()
    {
        return view('settings.uom.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:uoms',
            'description' => 'nullable|string|max:255',
        ]);

        Uom::create($request->all());

        return redirect()->route('uoms.index')->with('success', 'UOM created successfully.');
    }

    public function edit(Uom $uom)
    {
        return view('settings.uom.edit', compact('uom'));
    }

    public function update(Request $request, Uom $uom)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:uoms,name,' . $uom->id,
            'description' => 'nullable|string|max:255',
        ]);

        $uom->update($request->all());

        return redirect()->route('uoms.index')->with('success', 'UOM updated successfully.');
    }

    public function destroy(Uom $uom)
    {
        $uom->delete();
        return redirect()->route('uoms.index')->with('success', 'UOM deleted successfully.');
    }
}
