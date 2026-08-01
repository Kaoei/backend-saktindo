<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\GudangProduct;
use App\Models\Rak;
use App\Models\SubCategory;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of unified master products.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $category = $request->input('category');
        $brand = $request->input('brand');
        $stockStatus = $request->input('stock_status');

        $categoriesList = SupplierProduct::select('category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');

        $brandsList = SupplierProduct::select('brand')
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->pluck('brand');

        $query = SupplierProduct::with(['gudangProducts.rack'])
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('item_name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('sub_category', 'like', "%{$search}%");
                });
            })
            ->when($category, function ($q, $category) {
                $q->where('category', $category);
            })
            ->when($brand, function ($q, $brand) {
                $q->where('brand', $brand);
            });

        $products = $query->latest()->paginate(15)->withQueryString();

        return view('products.index', compact(
            'products',
            'search',
            'category',
            'brand',
            'stockStatus',
            'categoriesList',
            'brandsList'
        ));
    }

    /**
     * Show form to create a new master product.
     */
    public function create()
    {
        $brands = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $subCategories = SubCategory::orderBy('name')->get();
        $racks = Rak::orderBy('rak_kode')->get();

        return view('products.create', compact('brands', 'categories', 'subCategories', 'racks'));
    }

    /**
     * Store new master product in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'item_name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'sub_category' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'initial_qty' => 'nullable|integer|min:0',
            'rack_id' => 'nullable|exists:raks,rak_kode',
            'gudang_type' => 'nullable|in:JS,SJB',
        ]);

        DB::transaction(function () use ($request) {
            $brandName = trim($request->brand ?? '');
            if ($brandName !== '') {
                $existingBrand = Brand::whereRaw('LOWER(name) = ?', [strtolower($brandName)])->first();
                if (!$existingBrand) {
                    $existingBrand = Brand::create(['name' => $brandName, 'image' => '', 'alt' => '']);
                }
                $brandName = $existingBrand->name;
            } else {
                $brandName = null;
            }

            $categoryName = trim($request->category ?? '');
            $subCategoryName = trim($request->sub_category ?? '');
            if ($categoryName !== '') {
                $existingCategory = Category::whereRaw('LOWER(name) = ?', [strtolower($categoryName)])->first();
                if (!$existingCategory) {
                    $existingCategory = Category::create(['name' => $categoryName]);
                }
                $categoryName = $existingCategory->name;

                if ($subCategoryName !== '') {
                    $existingSub = SubCategory::where('category_id', $existingCategory->id)
                        ->whereRaw('LOWER(name) = ?', [strtolower($subCategoryName)])
                        ->first();
                    if (!$existingSub) {
                        SubCategory::create([
                            'category_id' => $existingCategory->id,
                            'name' => $subCategoryName
                        ]);
                    }
                }
            }

            $sku = trim($request->sku ?? '');
            if ($sku === '') {
                $sku = 'SKU-' . strtoupper(substr(md5($request->item_name), 0, 8));
            }

            $supplier = Supplier::first();
            if (!$supplier) {
                $supplier = Supplier::create(['name' => 'Supplier General', 'status' => 'active']);
            }

            $product = SupplierProduct::create([
                'supplier_id' => $supplier->id,
                'sku' => $sku,
                'item_name' => $request->item_name,
                'brand' => $brandName,
                'category' => $categoryName ?: null,
                'sub_category' => $subCategoryName ?: null,
                'last_purchase_price' => $request->price ?? 0,
                'unit' => $request->unit ?: 'pcs',
                'status' => 'active'
            ]);

            if ($request->filled('initial_qty') && (int)$request->initial_qty > 0 && $request->filled('rack_id')) {
                GudangProduct::create([
                    'id' => GudangProduct::generateId($sku),
                    'supplier_product_id' => $product->id,
                    'rack_id' => $request->rack_id,
                    'gudang_type' => $request->gudang_type ?: 'JS',
                    'qty' => (int)$request->initial_qty,
                    'price' => $request->price ?? 0,
                    'discount' => 0,
                    'status' => 'stored',
                ]);
            }
        });

        return redirect()->route('products.index')->with('success', 'Master Produk berhasil ditambahkan.');
    }

    /**
     * Show edit form.
     */
    public function edit($id)
    {
        $product = SupplierProduct::with('gudangProducts')->findOrFail($id);
        $brands = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $subCategories = SubCategory::orderBy('name')->get();

        return view('products.edit', compact('product', 'brands', 'categories', 'subCategories'));
    }

    /**
     * Update master product.
     */
    public function update(Request $request, $id)
    {
        $product = SupplierProduct::findOrFail($id);

        $request->validate([
            'item_name' => 'required|string|max:255',
            'sku' => 'required|string|max:100',
            'brand' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'sub_category' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
        ]);

        $product->update([
            'item_name' => $request->item_name,
            'sku' => $request->sku,
            'brand' => $request->brand,
            'category' => $request->category,
            'sub_category' => $request->sub_category,
            'last_purchase_price' => $request->price ?? $product->last_purchase_price,
            'unit' => $request->unit ?: $product->unit,
        ]);

        return redirect()->route('products.index')->with('success', 'Master Produk berhasil diperbarui.');
    }

    /**
     * Delete master product and its stock records.
     */
    public function destroy($id)
    {
        $product = SupplierProduct::findOrFail($id);
        $product->gudangProducts()->delete();
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Master Produk dan stoknya berhasil dihapus.');
    }

    /**
     * Import products via Excel.
     */
    public function import(Request $request)
    {
        return app(GudangProductController::class)->import($request);
    }

    /**
     * Export products to Excel.
     */
    public function export()
    {
        return app(GudangProductController::class)->export();
    }
}
