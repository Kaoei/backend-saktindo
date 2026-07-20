<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::orderBy('name')->paginate(10);
        return view('brands.index', compact('brands'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:brands,name',
        ]);

        Brand::create(array_merge($data, [
            'image' => '',
            'alt' => ''
        ]));

        return redirect()->route('brands.index')->with('status', 'Brand berhasil ditambahkan.');
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:brands,name,' . $brand->id,
        ]);

        $brand->update($data);

        return redirect()->route('brands.index')->with('status', 'Brand berhasil diperbarui.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();
        return redirect()->route('brands.index')->with('success', 'Brand berhasil dihapus.');
    }
}
