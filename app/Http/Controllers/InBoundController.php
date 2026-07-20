<?php

namespace App\Http\Controllers;

use App\Models\InBound;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\SupplierPO;
use Illuminate\Http\Request;

class InBoundController extends Controller
{
     public function index()
    {
        $inbounds = InBound::with(['supplier', 'supplierProduct', 'supplierPo'])
            ->latest()
            ->get();

        return view('inbound.index', compact('inbounds'));
    }

    public function create()
    {
        $suppliers = Supplier::where('status', 'active')->get();
        $supplierProducts = SupplierProduct::where('status', 'active')->get();
        $supplierPos = SupplierPO::with('items')->where('status', 'pending')->get();

        return view('inbound.create', compact('suppliers', 'supplierProducts', 'supplierPos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'supplier_product_id' => 'required|exists:supplier_products,id',
            'qty_received' => 'required|integer|min:1',
            'hpp' => 'nullable|numeric|min:0',
            'supplier_po_id' => 'nullable|exists:supplier_pos,id',
            'received_date' => 'required|date',
        ]);

        InBound::create([
            'id' => InBound::generateId(),
            'supplier_id' => $request->supplier_id,
            'supplier_product_id' => $request->supplier_product_id,
            'qty_received' => $request->qty_received,
            'hpp' => $request->hpp ?: 0,
            'supplier_po_id' => $request->supplier_po_id,
            'received_date' => $request->received_date,
            'status' => 'pending',
        ]);

        if ($request->supplier_po_id) {
            SupplierPO::where('id', $request->supplier_po_id)->update(['status' => 'received']);
        }

        return redirect()
            ->route('inbound.index')
            ->with('success', 'Barang masuk berhasil ditambahkan.');
    }

    public function edit(InBound $inbound)
    {
        $suppliers = Supplier::where('status', 'active')->get();
        $supplierProducts = SupplierProduct::where('status', 'active')->get();
        $supplierPos = SupplierPO::where('status', 'pending')
            ->orWhere('id', $inbound->supplier_po_id)
            ->get();

        return view('inbound.edit', compact('inbound', 'suppliers', 'supplierProducts', 'supplierPos'));
    }

    public function update(Request $request, InBound $inbound)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'supplier_product_id' => 'required|exists:supplier_products,id',
            'qty_received' => 'required|integer|min:1',
            'hpp' => 'nullable|numeric|min:0',
            'supplier_po_id' => 'nullable|exists:supplier_pos,id',
            'received_date' => 'required|date',
            'status' => 'required|in:pending,stored,cancelled',
        ]);

        $inbound->update([
            'supplier_id' => $request->supplier_id,
            'supplier_product_id' => $request->supplier_product_id,
            'qty_received' => $request->qty_received,
            'hpp' => $request->hpp ?: 0,
            'supplier_po_id' => $request->supplier_po_id,
            'received_date' => $request->received_date,
            'status' => $request->status,
        ]);

        if ($request->supplier_po_id && $request->status === 'stored') {
            SupplierPO::where('id', $request->supplier_po_id)->update(['status' => 'received']);
        }

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
