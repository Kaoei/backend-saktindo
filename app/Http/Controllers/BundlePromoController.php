<?php

namespace App\Http\Controllers;

use App\Models\BundlePromo;
use App\Models\BundlePromoItem;
use App\Models\SupplierProduct;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BundlePromoController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $query = BundlePromo::with(['items.supplierProduct'])->latest();

        if ($status && in_array($status, ['active', 'inactive'])) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('bundle_code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $bundlePromos = $query->paginate(15)->withQueryString();

        $totalBundles = BundlePromo::count();
        $activeBundles = BundlePromo::where('status', 'active')->count();

        return view('bundle_promos.index', compact('bundlePromos', 'totalBundles', 'activeBundles', 'status', 'search'));
    }

    public function create()
    {
        $products = SupplierProduct::orderBy('item_name', 'asc')->get();
        $autoCode = BundlePromo::generateCode();

        return view('bundle_promos.create', compact('products', 'autoCode'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'bundle_code' => 'required|string|max:50|unique:bundle_promos,bundle_code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:fixed,percentage',
            'discount_value' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:supplier_products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $bundleId = BundlePromo::generateId();

            // Calculate original price from items
            $originalPrice = 0;
            foreach ($request->items as $item) {
                $originalPrice += (float) $item['unit_price'] * (int) $item['qty'];
            }

            // Calculate bundle price
            $discountValue = (float) $request->discount_value;
            if ($request->discount_type === 'percentage') {
                $discountAmount = ($originalPrice * $discountValue) / 100;
                $bundlePrice = max(0, $originalPrice - $discountAmount);
            } else {
                $bundlePrice = max(0, $originalPrice - $discountValue);
            }

            $bundlePromo = BundlePromo::create([
                'id' => $bundleId,
                'bundle_code' => $request->bundle_code,
                'name' => $request->name,
                'description' => $request->description,
                'original_price' => $originalPrice,
                'bundle_price' => $bundlePrice,
                'discount_type' => $request->discount_type,
                'discount_value' => $discountValue,
                'status' => $request->status,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

            foreach ($request->items as $item) {
                BundlePromoItem::create([
                    'bundle_promo_id' => $bundleId,
                    'supplier_product_id' => $item['product_id'],
                    'qty' => (int) $item['qty'],
                    'unit_price' => (float) $item['unit_price'],
                ]);
            }

            ActivityLogger::log('create', 'bundle_promo', $bundlePromo);

            DB::commit();

            return redirect()->route('bundle-promos.index')->with('status', "Paket Promo Bundling '{$bundlePromo->name}' berhasil dibuat.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal membuat paket promo bundling: ' . $e->getMessage());
        }
    }

    public function show(BundlePromo $bundlePromo)
    {
        $bundlePromo->load(['items.supplierProduct']);
        return view('bundle_promos.show', compact('bundlePromo'));
    }

    public function edit(BundlePromo $bundlePromo)
    {
        $bundlePromo->load(['items.supplierProduct']);
        $products = SupplierProduct::orderBy('item_name', 'asc')->get();

        return view('bundle_promos.edit', compact('bundlePromo', 'products'));
    }

    public function update(Request $request, BundlePromo $bundlePromo): RedirectResponse
    {
        $request->validate([
            'bundle_code' => 'required|string|max:50|unique:bundle_promos,bundle_code,' . $bundlePromo->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:fixed,percentage',
            'discount_value' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:supplier_products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Calculate original price from items
            $originalPrice = 0;
            foreach ($request->items as $item) {
                $originalPrice += (float) $item['unit_price'] * (int) $item['qty'];
            }

            // Calculate bundle price
            $discountValue = (float) $request->discount_value;
            if ($request->discount_type === 'percentage') {
                $discountAmount = ($originalPrice * $discountValue) / 100;
                $bundlePrice = max(0, $originalPrice - $discountAmount);
            } else {
                $bundlePrice = max(0, $originalPrice - $discountValue);
            }

            $bundlePromo->update([
                'bundle_code' => $request->bundle_code,
                'name' => $request->name,
                'description' => $request->description,
                'original_price' => $originalPrice,
                'bundle_price' => $bundlePrice,
                'discount_type' => $request->discount_type,
                'discount_value' => $discountValue,
                'status' => $request->status,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

            // Re-sync items
            $bundlePromo->items()->delete();

            foreach ($request->items as $item) {
                BundlePromoItem::create([
                    'bundle_promo_id' => $bundlePromo->id,
                    'supplier_product_id' => $item['product_id'],
                    'qty' => (int) $item['qty'],
                    'unit_price' => (float) $item['unit_price'],
                ]);
            }

            ActivityLogger::log('update', 'bundle_promo', $bundlePromo);

            DB::commit();

            return redirect()->route('bundle-promos.index')->with('status', "Paket Promo Bundling '{$bundlePromo->name}' berhasil diperbarui.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal memperbarui paket promo: ' . $e->getMessage());
        }
    }

    public function destroy(BundlePromo $bundlePromo): RedirectResponse
    {
        try {
            $name = $bundlePromo->name;
            $bundlePromo->delete();

            return redirect()->route('bundle-promos.index')->with('status', "Paket Promo '{$name}' berhasil dihapus.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus paket promo: ' . $e->getMessage());
        }
    }

    /**
     * API JSON endpoint to retrieve active bundles for modal selection in PO / Sales.
     */
    public function apiActiveList(): JsonResponse
    {
        $today = now()->toDateString();
        $bundles = BundlePromo::with(['items.supplierProduct'])
            ->where('status', 'active')
            ->where(function ($q) use ($today) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bundles->map(function ($b) {
                return [
                    'id' => $b->id,
                    'bundle_code' => $b->bundle_code,
                    'name' => $b->name,
                    'description' => $b->description,
                    'original_price' => (float) $b->original_price,
                    'bundle_price' => (float) $b->bundle_price,
                    'savings_amount' => (float) $b->savings_amount,
                    'savings_percentage' => (float) $b->savings_percentage,
                    'items_count' => $b->items->count(),
                    'items' => $b->items->map(function ($item) use ($b) {
                        // Calculate effective discounted price per item in the bundle
                        $ratio = (float) $b->original_price > 0 
                            ? ((float) $b->bundle_price / (float) $b->original_price) 
                            : 1;
                        $discountedUnitPrice = round((float) $item->unit_price * $ratio, 2);

                        return [
                            'product_id' => $item->supplier_product_id,
                            'product_name' => $item->supplierProduct?->item_name ?? '-',
                            'sku' => $item->supplierProduct?->sku ?? '',
                            'unit' => $item->supplierProduct?->unit ?? 'pcs',
                            'qty' => (int) $item->qty,
                            'original_unit_price' => (float) $item->unit_price,
                            'bundle_unit_price' => $discountedUnitPrice,
                        ];
                    }),
                ];
            }),
        ]);
    }
}
