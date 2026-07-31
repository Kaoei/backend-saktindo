<?php

namespace App\Http\Controllers;

use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class VariantController extends Controller
{
    public function index()
    {
        $variants = Variant::orderBy('name')->paginate(10);
        return view('variants.index', compact('variants'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:variants,name',
        ]);

        Variant::create($data);

        return redirect()->route('variants.index')->with('status', 'Varian berhasil ditambahkan.');
    }

    public function update(Request $request, Variant $variant): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:variants,name,' . $variant->id,
        ]);

        $variant->update($data);

        return redirect()->route('variants.index')->with('status', 'Varian berhasil diperbarui.');
    }

    public function destroy(Variant $variant): RedirectResponse
    {
        $variant->delete();
        return redirect()->route('variants.index')->with('success', 'Varian berhasil dihapus.');
    }
}
