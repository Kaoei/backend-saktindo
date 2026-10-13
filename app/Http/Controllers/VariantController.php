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

        try {
            Variant::create($data);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'produk_id')) {
                try {
                    \Illuminate\Support\Facades\DB::statement('ALTER TABLE `variants` MODIFY `produk_id` BIGINT UNSIGNED NULL DEFAULT NULL');
                    Variant::create($data);
                } catch (\Throwable $ex) {
                    Variant::unguarded(function () use ($data) {
                        Variant::create(array_merge($data, ['produk_id' => 0]));
                    });
                }
            } else {
                throw $e;
            }
        }

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
