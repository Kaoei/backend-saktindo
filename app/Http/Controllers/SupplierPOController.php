<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierPO;
use App\Models\SupplierPOItem;
use App\Models\SupplierProduct;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class SupplierPOController extends Controller
{
    public function index()
    {
        $supplierPos = SupplierPO::with('supplier')->latest()->paginate(15);
        return view('supplier_po.index', compact('supplierPos'));
    }

    public function create()
    {
        $suppliers = Supplier::where('status', 'active')->get();
        $products = SupplierProduct::where('status', 'active')->get();
        return view('supplier_po.create', compact('suppliers', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:supplier_products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.discount' => 'required|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            $poId = SupplierPO::generateId();
            $poNumber = 'SPO-' . now()->format('Ymd') . '-' . random_int(100, 999);
            
            $totalAmount = 0;
            $itemsToInsert = [];

            foreach ($request->items as $item) {
                $lineTotal = $item['qty'] * $item['price'] * (1 - $item['discount'] / 100);
                $totalAmount += $lineTotal;

                $itemsToInsert[] = [
                    'supplier_po_id' => $poId,
                    'supplier_product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'discount' => $item['discount'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $supplierPo = SupplierPO::create([
                'id' => $poId,
                'supplier_id' => $request->supplier_id,
                'po_number' => $poNumber,
                'order_date' => $request->order_date,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'notes' => $request->notes,
            ]);

            SupplierPOItem::insert($itemsToInsert);

            DB::commit();
            return redirect()->route('supplier-po.index')->with('status', 'Supplier PO Template berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membuat Supplier PO: ' . $e->getMessage());
        }
    }

    public function show(SupplierPO $supplierPo)
    {
        $supplierPo->load(['supplier', 'items.supplierProduct']);
        return view('supplier_po.show', compact('supplierPo'));
    }

    /**
     * Render PO create form with pre-filled items from dashboard shortage data.
     */
    public function createFromShortage(Request $request)
    {
        $request->validate([
            'shortage_items' => 'required|array|min:1',
            'shortage_items.*.product_code' => 'nullable|string',
            'shortage_items.*.product_name' => 'required|string',
            'shortage_items.*.qty' => 'required|numeric|min:1',
            'shortage_items.*.unit' => 'nullable|string',
            'shortage_items.*.price' => 'nullable|numeric|min:0',
        ]);

        $suppliers = Supplier::where('status', 'active')->get();
        $defaultSupplierId = $suppliers->first()?->id ?: Supplier::first()?->id;

        // Build prefill items by matching product_code/name to SupplierProduct or auto-creating master item
        $prefillItems = collect($request->shortage_items)->map(function ($item) use ($defaultSupplierId) {
            $sp = null;
            if (!empty($item['product_code'])) {
                $sp = SupplierProduct::find($item['product_code'])
                    ?: SupplierProduct::where('sku', $item['product_code'])->first();
            }
            if (!$sp && !empty($item['product_name'])) {
                $sp = SupplierProduct::where('item_name', $item['product_name'])->first();
            }

            // If product does not exist in supplier_products master table, auto-create it
            if (!$sp && !empty($item['product_name'])) {
                $spId = SupplierProduct::generateId();
                $spData = [
                    'id' => $spId,
                    'item_name' => $item['product_name'],
                    'sku' => !empty($item['product_code']) ? $item['product_code'] : 'SKU-' . strtoupper(substr(md5($item['product_name']), 0, 8)),
                    'unit' => $item['unit'] ?? 'pcs',
                    'last_purchase_price' => (float) ($item['price'] ?? 0),
                    'status' => 'active',
                ];
                if ($defaultSupplierId) {
                    $spData['supplier_id'] = $defaultSupplierId;
                }
                $sp = SupplierProduct::create($spData);
            }

            return [
                'product_id' => $sp ? $sp->id : null,
                'product_name' => $sp ? $sp->item_name : $item['product_name'],
                'qty' => (int) ceil($item['qty']),
                'price' => $sp ? (float) $sp->last_purchase_price : (float) ($item['price'] ?? 0),
                'unit' => $sp ? ($sp->unit ?: 'pcs') : ($item['unit'] ?? 'pcs'),
                'discount' => 0,
            ];
        });

        $products = SupplierProduct::where(function($q) {
            $q->where('status', 'active')->orWhereNull('status')->orWhere('status', 'stored');
        })->get();

        return view('supplier_po.create', compact('suppliers', 'products', 'prefillItems'));
    }
}
