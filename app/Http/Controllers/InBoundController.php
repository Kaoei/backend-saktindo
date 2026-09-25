<?php

namespace App\Http\Controllers;

use App\Models\InBound;
use App\Models\Rak;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\SupplierPO;
use App\Services\StockSyncService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InBoundController extends Controller
{
    public function index()
    {
        $inbounds = InBound::with(['supplier', 'supplierProduct.gudangProducts.rack', 'supplierPo'])
            ->latest()
            ->get();

        return view('inbound.index', compact('inbounds'));
    }

    public function create()
    {
        $suppliers = Supplier::all();
        $supplierProducts = SupplierProduct::all();
        $supplierPos = SupplierPO::with(['items.supplierProduct', 'supplier'])->where('status', 'pending')->get();
        $racks = Rak::orderBy('rak_kode')->get();

        return view('inbound.create', compact('suppliers', 'supplierProducts', 'supplierPos', 'racks'));
    }

    public function store(Request $request)
    {
        if ($request->has('items') && is_array($request->items) && count($request->items) > 0) {
            $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'supplier_po_id' => 'nullable|exists:supplier_pos,id',
                'invoice_number' => 'nullable|string|max:255',
                'received_date' => 'required|date',
                'direct_rack_id' => 'nullable|exists:raks,rak_kode',
                'items' => 'required|array|min:1',
                'items.*.supplier_product_id' => 'required|exists:supplier_products,id',
                'items.*.qty_received' => 'required|integer|min:0',
                'items.*.qty_damaged' => 'nullable|integer|min:0',
                'items.*.qty_missing' => 'nullable|integer|min:0',
                'items.*.hpp' => 'nullable|numeric|min:0',
                'items.*.rack_id' => 'nullable|exists:raks,rak_kode',
                'items.*.notes' => 'nullable|string',
            ]);

            $savedCount = 0;
            DB::transaction(function () use ($request, &$savedCount) {
                foreach ($request->items as $item) {
                    if (isset($item['is_checked']) && ($item['is_checked'] == '0' || $item['is_checked'] === false)) {
                        continue;
                    }

                    $inbound = InBound::create([
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

                    $targetRack = $item['rack_id'] ?? $request->direct_rack_id ?? null;
                    if (!empty($targetRack)) {
                        StockSyncService::storeInboundToRack($inbound, $targetRack);
                    }

                    $savedCount++;
                }

                if ($request->supplier_po_id && $savedCount > 0) {
                    SupplierPO::where('id', $request->supplier_po_id)->update(['status' => 'received']);
                }
            });

            return redirect()
                ->route('inbound.index')
                ->with('success', "Barang masuk ($savedCount item) berhasil ditambahkan dan data stok disinkronkan.");
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
            'rack_id' => 'nullable|exists:raks,rak_kode',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            $inbound = InBound::create([
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

            if ($request->filled('rack_id')) {
                StockSyncService::storeInboundToRack($inbound, $request->rack_id);
            }

            if ($request->supplier_po_id) {
                SupplierPO::where('id', $request->supplier_po_id)->update(['status' => 'received']);
            }
        });

        return redirect()
            ->route('inbound.index')
            ->with('success', 'Barang masuk berhasil ditambahkan dan stok disinkronkan.');
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

        $oldQty = (int) $inbound->qty_received;
        $newQty = (int) $request->qty_received;

        DB::transaction(function () use ($request, $inbound, $oldQty, $newQty) {
            $inbound->update([
                'supplier_id' => $request->supplier_id,
                'supplier_product_id' => $request->supplier_product_id,
                'qty_received' => $newQty,
                'qty_damaged' => $request->qty_damaged ?? 0,
                'qty_missing' => $request->qty_missing ?? 0,
                'hpp' => $request->hpp ?: 0,
                'supplier_po_id' => $request->supplier_po_id,
                'invoice_number' => $request->invoice_number,
                'received_date' => $request->received_date,
                'notes' => $request->notes,
            ]);

            StockSyncService::handleInboundQtyUpdate($inbound, $oldQty, $newQty);
        });

        return redirect()
            ->route('inbound.index')
            ->with('success', 'Barang masuk berhasil diperbarui dan stok gudang disesuaikan.');
    }

    public function cancel(InBound $inbound)
    {
        DB::transaction(function () use ($inbound) {
            StockSyncService::handleInboundCancelOrDelete($inbound);
            $inbound->update(['status' => 'cancelled']);
        });

        return redirect()
            ->route('inbound.index')
            ->with('success', 'Barang masuk berhasil dibatalkan dan stok gudang dikoreksi.');
    }

    public function printInbound(InBound $inbound)
    {
        $inbound->load(['supplier', 'supplierProduct', 'supplierPo']);
        return view('inbound.print', compact('inbound'));
    }

    public function destroy(InBound $inbound)
    {
        DB::transaction(function () use ($inbound) {
            StockSyncService::handleInboundCancelOrDelete($inbound);
            $inbound->delete();
        });

        return redirect()
            ->route('inbound.index')
            ->with('success', 'Barang masuk berhasil dihapus dan stok gudang dikoreksi.');
    }

    public function downloadPdf(InBound $inbound)
    {
        $inbound->load([
            'supplier',
            'supplierProduct',
            'supplierPo'
        ]);

        $pdf = Pdf::loadView('inbound.print', compact('inbound'));

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download(
            'Tanda-Terima-Barang-Masuk-' . $inbound->id . '.pdf'
        );
    }

    /**
     * Trigger full stock synchronization from Inbound UI.
     */
    public function sync()
    {
        $report = StockSyncService::reconcileAllStock();

        return redirect()
            ->route('inbound.index')
            ->with('success', "Sinkronisasi berhasil! {$report['synced_count']} data produk, stok gudang, dan master produk telah diselaraskan.")
            ->with('status', "Sinkronisasi berhasil! {$report['synced_count']} data produk, stok gudang, dan master produk telah diselaraskan.");
    }
}
