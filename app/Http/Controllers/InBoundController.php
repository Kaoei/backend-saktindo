<?php

namespace App\Http\Controllers;

use App\Models\InBound;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use Illuminate\Http\Request;

class InBoundController extends Controller
{
     public function index()
    {
        $inbounds = InBound::with(['supplier', 'supplierProduct'])
            ->latest()
            ->get();

        return view('inbound.index', compact('inbounds'));
    }

    public function create()
    {
        $suppliers = Supplier::where('status', 'active')->get();
        $supplierProducts = SupplierProduct::where('status', 'active')->get();

        return view('inbound.create', compact('suppliers', 'supplierProducts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'supplier_product_id' => 'required|exists:supplier_products,id',
            'qty_received' => 'required|integer|min:1',
            'received_date' => 'required|date',
        ]);

        InBound::create([
            'id' => InBound::generateId(),
            'supplier_id' => $request->supplier_id,
            'supplier_product_id' => $request->supplier_product_id,
            'qty_received' => $request->qty_received,
            'received_date' => $request->received_date,
            'status' => 'pending',
        ]);

        return redirect()
            ->route('inbound.index')
            ->with('success', 'Barang masuk berhasil ditambahkan.');
    }

    public function edit(InBound $inbound)
    {
        $suppliers = Supplier::where('status', 'active')->get();
        $supplierProducts = SupplierProduct::where('status', 'active')->get();

        return view('inbound.edit', compact('inbound', 'suppliers', 'supplierProducts'));
    }

    public function update(Request $request, InBound $inbound)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'supplier_product_id' => 'required|exists:supplier_products,id',
            'qty_received' => 'required|integer|min:1',
            'received_date' => 'required|date',
            'status' => 'required|in:pending,stored,cancelled',
        ]);

        $inbound->update([
            'supplier_id' => $request->supplier_id,
            'supplier_product_id' => $request->supplier_product_id,
            'qty_received' => $request->qty_received,
            'received_date' => $request->received_date,
            'status' => $request->status,
        ]);

        return redirect()
            ->route('inbound.index')
            ->with('success', 'Barang masuk berhasil diperbarui.');
    }

    public function destroy(InBound $inbound)
    {
        $inbound->delete();

        return redirect()
            ->route('inbound.index')
            ->with('success', 'Barang masuk berhasil dihapus.');
    }
}
