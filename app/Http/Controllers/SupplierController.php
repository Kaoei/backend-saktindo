<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
  public function index()
    {
        $suppliers = Supplier::latest()->get();

        return view('supplier.index', compact('suppliers'));
    }

    public function create()
    {
        return view('supplier.create');
    }

    public function store(Request $request)
    {
    $request->validate([
        'name'   => 'required|string|max:255',
        'alamat' => 'nullable|string',
        'no_tlp' => 'nullable|string|max:20',
        'email'  => 'nullable|email|max:255',
    ]);

    // Ambil supplier terakhir
    $lastSupplier = Supplier::orderBy('id_supplier', 'desc')->first();

    if (!$lastSupplier) {
        $newId = 'SUP_000001';
    } else {
        $lastNumber = (int) substr($lastSupplier->id_supplier, 4);
        $newId = 'SUP_' . str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
    }

    Supplier::create([
        'id_supplier' => $newId,
        'name'        => $request->name,
        'alamat'      => $request->alamat,
        'no_tlp'      => $request->no_tlp,
        'email'       => $request->email,
    ]);

    return redirect()
        ->route('supplier.index')
        ->with('status', 'Supplier berhasil ditambahkan');
}

    public function edit(string $id)
    {
        $supplier = Supplier::where('id_supplier', $id)->firstOrFail();

        return view('supplier.edit', compact('supplier'));
    }

    public function update(Request $request, string $id)
    {
        $supplier = Supplier::where('id_supplier', $id)->firstOrFail();

        $request->validate([
            'name'   => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'no_tlp' => 'nullable|string|max:20',
            'email'  => 'nullable|email|max:255',
        ]);

        $supplier->update([
            'name'   => $request->name,
            'alamat' => $request->alamat,
            'no_tlp' => $request->no_tlp,
            'email'  => $request->email,
        ]);

        return redirect()
            ->route('supplier.index')
            ->with('status', 'Supplier berhasil diupdate');
    }

    public function destroy(string $id)
    {
        $supplier = Supplier::where('id_supplier', $id)->firstOrFail();

        $supplier->delete();

        return redirect()
            ->route('supplier.index')
            ->with('status', 'Supplier berhasil dihapus');
    }
}
