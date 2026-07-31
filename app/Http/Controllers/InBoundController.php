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
        $suppliers = Supplier::all();
        $supplierProducts = SupplierProduct::all();
        $supplierPos = SupplierPO::with(['items.supplierProduct', 'supplier'])->where('status', 'pending')->get();

        return view('inbound.create', compact('suppliers', 'supplierProducts', 'supplierPos'));
    }

    public function store(Request $request)
    {
        if ($request->has('items') && is_array($request->items) && count($request->items) > 0) {
            $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'supplier_po_id' => 'nullable|exists:supplier_pos,id',
                'invoice_number' => 'nullable|string|max:255',
                'received_date' => 'required|date',
                'items' => 'required|array|min:1',
                'items.*.supplier_product_id' => 'required|exists:supplier_products,id',
                'items.*.qty_received' => 'required|integer|min:0',
                'items.*.qty_damaged' => 'nullable|integer|min:0',
                'items.*.qty_missing' => 'nullable|integer|min:0',
                'items.*.hpp' => 'nullable|numeric|min:0',
                'items.*.notes' => 'nullable|string',
            ]);

            $savedCount = 0;
            foreach ($request->items as $item) {
                if (isset($item['is_checked']) && ($item['is_checked'] == '0' || $item['is_checked'] === false)) {
                    continue;
                }

                InBound::create([
                    'id' => InBound::generateId(),
                    'supplier_id' => $request->supplier_id,
                    'supplier_product_id' => $item['supplier_product_id'],
                    'qty_received' => (int) ($item['qty_received'] ?? 0),
                    'qty_damaged' => (int) ($item['qty_damaged'] ?? 0),
                    'qty_missing' => (int) ($item['qty_missing'] ?? 0),
                    'hpp' => $item['hpp'] ?? 0,
                    'supplier_po_id' => $request->supplier_po_id,
                    'invoice_number' => $request->invoice_number,
                    'received_date' => $request->received_date,
                    'status' => 'pending',
                    'notes' => $item['notes'] ?? null,
                ]);
                $savedCount++;
            }

            if ($request->supplier_po_id && $savedCount > 0) {
                SupplierPO::where('id', $request->supplier_po_id)->update(['status' => 'received']);
            }

            return redirect()
                ->route('inbound.index')
                ->with('success', "Barang masuk ($savedCount item) berhasil ditambahkan.");
        }

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'supplier_product_id' => 'required|exists:supplier_products,id',
            'qty_received' => 'required|integer|min:0',
            'qty_damaged' => 'nullable|integer|min:0',
            'qty_missing' => 'nullable|integer|min:0',
            'hpp' => 'nullable|numeric|min:0',
            'supplier_po_id' => 'nullable|exists:supplier_pos,id',
            'invoice_number' => 'nullable|string|max:255',
            'received_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        InBound::create([
            'id' => InBound::generateId(),
            'supplier_id' => $request->supplier_id,
            'supplier_product_id' => $request->supplier_product_id,
            'qty_received' => (int) $request->qty_received,
            'qty_damaged' => (int) ($request->qty_damaged ?? 0),
            'qty_missing' => (int) ($request->qty_missing ?? 0),
            'hpp' => $request->hpp ?: 0,
            'supplier_po_id' => $request->supplier_po_id,
            'invoice_number' => $request->invoice_number,
            'received_date' => $request->received_date,
            'status' => 'pending',
            'notes' => $request->notes,
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
        $suppliers = Supplier::all();
        $supplierProducts = SupplierProduct::all();
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
            'qty_received' => 'required|integer|min:0',
            'qty_damaged' => 'nullable|integer|min:0',
            'qty_missing' => 'nullable|integer|min:0',
            'hpp' => 'nullable|numeric|min:0',
            'supplier_po_id' => 'nullable|exists:supplier_pos,id',
            'invoice_number' => 'nullable|string|max:255',
            'received_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $inbound->update([
            'supplier_id' => $request->supplier_id,
            'supplier_product_id' => $request->supplier_product_id,
            'qty_received' => $request->qty_received,
            'qty_damaged' => $request->qty_damaged ?? 0,
            'qty_missing' => $request->qty_missing ?? 0,
            'hpp' => $request->hpp ?: 0,
            'supplier_po_id' => $request->supplier_po_id,
            'invoice_number' => $request->invoice_number,
            'received_date' => $request->received_date,
            'notes' => $request->notes,
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
