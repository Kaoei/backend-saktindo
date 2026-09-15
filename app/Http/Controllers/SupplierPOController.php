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
        $suppliers = Supplier::all();
        $products = SupplierProduct::all();
        return view('supplier_po.create', compact('suppliers', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:supplier_products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_1' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_2' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_3' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_4' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            $poId = SupplierPO::generateId();

            // Format: P-YY/MM/XXXX, e.g. P-26/08/0001
            $year = now()->format('y');
            $month = now()->format('m');
            $count = SupplierPO::whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->where('po_number', 'like', "P-{$year}/{$month}/%")
                ->count();
            $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            $poNumber = "P-{$year}/{$month}/{$sequence}";
            
            $totalAmount = 0;
            $itemsToInsert = [];

            foreach ($request->items as $item) {
                $d1 = (float) ($item['discount_1'] ?? $item['discount'] ?? 0);
                $d2 = (float) ($item['discount_2'] ?? 0);
                $d3 = (float) ($item['discount_3'] ?? 0);
                $d4 = (float) ($item['discount_4'] ?? 0);
                $price = (float) $item['price'];
                $qty = (int) $item['qty'];

                $lineNetUnitPrice = $price * (1 - $d1 / 100) * (1 - $d2 / 100) * (1 - $d3 / 100) * (1 - $d4 / 100);
                $lineTotal = $lineNetUnitPrice * $qty;
                $totalAmount += $lineTotal;

                $itemsToInsert[] = [
                    'supplier_po_id' => $poId,
                    'supplier_product_id' => $item['product_id'],
                    'qty' => $qty,
                    'price' => $price,
                    'discount' => $d1,
                    'discount_1' => $d1,
                    'discount_2' => $d2,
                    'discount_3' => $d3,
                    'discount_4' => $d4,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $supplierPo = SupplierPO::create([
                'id' => $poId,
                'supplier_id' => $request->supplier_id,
                'po_number' => $poNumber,
                'reference_number' => $request->reference_number,
                'order_date' => $request->order_date,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'notes' => $request->notes,
            ]);

            SupplierPOItem::insert($itemsToInsert);

            DB::commit();
            return redirect()->route('supplier-po.index')->with('status', 'Supplier PO berhasil disimpan.');

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

        $suppliers = Supplier::all();
        $defaultSupplierId = $suppliers->first()?->id;

        $prefillItems = collect($request->shortage_items)->map(function ($item) use ($defaultSupplierId) {
            $sp = null;
            if (!empty($item['product_code'])) {
                $sp = SupplierProduct::find($item['product_code'])
                    ?: SupplierProduct::where('sku', $item['product_code'])->first();
            }
            if (!$sp && !empty($item['product_name'])) {
                $sp = SupplierProduct::where('item_name', $item['product_name'])->first();
            }

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
                'discount_1' => 0,
                'discount_2' => 0,
                'discount_3' => 0,
                'discount_4' => 0,
            ];
        });

        $products = SupplierProduct::all();

        return view('supplier_po.create', compact('suppliers', 'products', 'prefillItems'));
    }
}
