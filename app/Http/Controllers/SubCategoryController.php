<?php

namespace App\Http\Controllers;

use App\Models\SubCategory;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class SubCategoryController extends Controller
{
    public function index()
    {
        $subCategories = SubCategory::with('category')->orderBy('name')->paginate(10);
        $categories = Category::orderBy('name')->get();
        return view('sub_categories.index', compact('subCategories', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
        ]);

        // Check uniqueness for category_id & name combination
        $exists = SubCategory::where('category_id', $data['category_id'])
            ->where('name', $data['name'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'Sub-kategori ini sudah terdaftar untuk kategori terpilih.'])->withInput();
        }

        SubCategory::create($data);

        return redirect()->route('sub-categories.index')->with('status', 'Sub-kategori berhasil ditambahkan.');
    }

    public function update(Request $request, SubCategory $subCategory): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
        ]);

        $exists = SubCategory::where('category_id', $data['category_id'])
            ->where('name', $data['name'])
            ->where('id', '!=', $subCategory->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'Sub-kategori ini sudah terdaftar untuk kategori terpilih.'])->withInput();
        }

        $subCategory->update($data);

        return redirect()->route('sub-categories.index')->with('status', 'Sub-kategori berhasil diperbarui.');
    }

    public function destroy(SubCategory $subCategory): RedirectResponse
    {
        $subCategory->delete();
        return redirect()->route('sub-categories.index')->with('success', 'Sub-kategori berhasil dihapus.');
    }
}
