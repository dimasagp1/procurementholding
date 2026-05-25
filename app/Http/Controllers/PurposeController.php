<?php

namespace App\Http\Controllers;

use App\Models\Purpose;
use Illuminate\Http\Request;

class PurposeController extends Controller
{
    public function index(Request $request)
    {
        $purposes = Purpose::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('purposes.index', compact('purposes'));
    }

    public function create()
    {
        return view('purposes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:purposes,name',
        ]);

        Purpose::create($request->all());

        return redirect()->route('purposes.index')->with('success', 'Purpose created successfully.');
    }

    public function edit(Purpose $purpose)
    {
        return view('purposes.edit', compact('purpose'));
    }

    public function update(Request $request, Purpose $purpose)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:purposes,name,' . $purpose->id,
        ]);

        $purpose->update($request->all());

        return redirect()->route('purposes.index')->with('success', 'Purpose updated successfully.');
    }

    public function destroy(Purpose $purpose)
    {
        $purpose->delete();
        return redirect()->route('purposes.index')->with('success', 'Purpose deleted successfully.');
    }
}
