<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\GudangProduct;
use App\Models\Product;
use App\Models\Rak;
use App\Models\SubCategory;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

use App\Services\StockSyncService;

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
        try {
            $masterVariants = \App\Models\Variant::orderBy('name')->get();
        } catch (\Throwable $e) {
            $masterVariants = collect();
        }
        $gudangProducts = GudangProduct::with('supplierProduct')->get();

        return view('products.create', compact('brands', 'categories', 'subCategories', 'racks', 'masterVariants', 'gudangProducts'));
    }

    /**
     * Store new master product in storage.
     */
    public function store(Request $request)
    {
        // Support both item_name and product_name
        $itemName = $request->filled('item_name') ? $request->item_name : $request->input('product_name');
        $request->merge(['item_name' => $itemName]);

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

        DB::transaction(function () use ($request, $itemName) {
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
                $sku = 'SKU-' . strtoupper(substr(md5($itemName), 0, 8));
            }

            $supplier = Supplier::first();
            if (!$supplier) {
                $supplier = Supplier::create(['name' => 'Supplier General', 'status' => 'active']);
            }

            $product = SupplierProduct::create([
                'supplier_id' => $supplier->id,
                'sku' => $sku,
                'item_name' => $itemName,
                'brand' => $brandName,
                'category' => $categoryName ?: null,
                'sub_category' => $subCategoryName ?: null,
                'last_purchase_price' => $request->price ?? 0,
                'unit' => $request->unit ?: 'pcs',
                'status' => 'active'
            ]);

            $rackId = $request->rack_id ?: Rak::value('rak_kode');
            $initialQty = (int) ($request->initial_qty ?? 0);

            if ($rackId) {
                $gp = GudangProduct::create([
                    'id' => GudangProduct::generateId($sku),
                    'supplier_product_id' => $product->id,
                    'rack_id' => $rackId,
                    'gudang_type' => $request->gudang_type ?: 'JS',
                    'qty' => $initialQty,
                    'price' => $request->price ?? 0,
                    'discount' => 0,
                    'status' => 'stored',
                ]);

                if ($initialQty > 0) {
                    StockSyncService::syncGudangStock($gp, true, $supplier->id, 'Saldo Awal Master Produk');
                }
            }

            StockSyncService::syncProductCatalog($product);
        });

        return redirect()->route('products.index')->with('success', 'Master Produk berhasil ditambahkan dan stok disinkronkan.');
    }

    /**
     * Show edit form.
     */
    public function edit($id)
    {
        $product = SupplierProduct::with('gudangProducts.rack')->findOrFail($id);
        $brands = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $subCategories = SubCategory::orderBy('name')->get();
        $racks = Rak::orderBy('rak_kode')->get();
        try {
            $masterVariants = \App\Models\Variant::orderBy('name')->get();
        } catch (\Throwable $e) {
            $masterVariants = collect();
        }
        $gudangProducts = GudangProduct::with('supplierProduct')->get();

        return view('products.edit', compact('product', 'brands', 'categories', 'subCategories', 'racks', 'masterVariants', 'gudangProducts'));
    }

    /**
     * Update master product.
     */
    public function update(Request $request, $id)
    {
        $product = SupplierProduct::findOrFail($id);

        $itemName = $request->filled('item_name') ? $request->item_name : $request->input('product_name');
        $request->merge(['item_name' => $itemName]);

        $request->validate([
            'item_name' => 'required|string|max:255',
            'sku' => 'required|string|max:100',
            'brand' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'sub_category' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'stock_allocations' => 'nullable|array',
            'stock_allocations.*.rack_id' => 'required_with:stock_allocations|exists:raks,rak_kode',
            'stock_allocations.*.qty' => 'required_with:stock_allocations|integer|min:0',
        ]);

        DB::transaction(function () use ($request, $product, $itemName) {
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

            $product->update([
                'item_name' => $itemName,
                'sku' => $request->sku,
                'brand' => $brandName,
                'category' => $categoryName ?: null,
                'sub_category' => $subCategoryName ?: null,
                'last_purchase_price' => $request->price ?? $product->last_purchase_price,
                'unit' => $request->unit ?: $product->unit,
            ]);

            // If stock allocations were provided from edit form
            if ($request->has('stock_allocations') && is_array($request->stock_allocations)) {
                foreach ($request->stock_allocations as $alloc) {
                    $rackId = $alloc['rack_id'] ?? null;
                    $qty = (int) ($alloc['qty'] ?? 0);
                    if (!$rackId) continue;

                    $gp = GudangProduct::where('supplier_product_id', $product->id)
                        ->where('rack_id', $rackId)
                        ->first();

                    if ($gp) {
                        $gp->update([
                            'qty' => $qty,
                            'price' => $request->price ?? $gp->price,
                        ]);
                    } else {
                        GudangProduct::create([
                            'id' => GudangProduct::generateId($product->sku),
                            'supplier_product_id' => $product->id,
                            'rack_id' => $rackId,
                            'gudang_type' => 'JS',
                            'qty' => $qty,
                            'price' => $request->price ?? 0,
                            'discount' => 0,
                            'status' => 'stored',
                        ]);
                    }
                }
            }

            StockSyncService::syncProductCatalog($product);
        });

        return redirect()->route('products.index')->with('success', 'Master Produk berhasil diperbarui dan disinkronkan.');
    }

    /**
     * Delete master product and its stock records.
     */
    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $product = SupplierProduct::findOrFail($id);
            Product::where('seller_sku', $product->sku)->orWhere('product_name', $product->item_name)->delete();
            $product->gudangProducts()->delete();
            $product->delete();
        });

        return redirect()->route('products.index')->with('success', 'Master Produk dan stoknya berhasil dihapus.');
    }

    /**
     * Trigger full stock synchronization from Master Products UI.
     */
    public function sync()
    {
        $report = StockSyncService::reconcileAllStock();

        return redirect()
            ->route('products.index')
            ->with('success', "Sinkronisasi berhasil! {$report['synced_count']} data produk, stok gudang, dan katalog ekspor telah diselaraskan.")
            ->with('status', "Sinkronisasi berhasil! {$report['synced_count']} data produk, stok gudang, dan katalog ekspor telah diselaraskan.");
    }

    /**
     * Import products via Excel.
     */
    public function import(Request $request)
    {
        return app(GudangProductController::class)->import($request);
    }

    /**
     * Download Excel template for product import
     */
    public function downloadTemplate()
    {
        return app(GudangProductController::class)->downloadTemplate();
    }

    /**
     * Export products into TikTok batch edit Excel template with clean layout styling
     */
    public function export()
    {
        $templatePath = base_path('Tiktoksellercenter_batchedit_20260520_all_information_template_7.xlsx');

        if (!file_exists($templatePath)) {
            return app(GudangProductController::class)->export();
        }

        try {
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getSheetByName('Template') ?: $spreadsheet->getActiveSheet();
            
            $highestColumn = $sheet->getHighestColumn();
            $colsCount = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            $headerKeys = [];
            for ($col = 1; $col <= $colsCount; $col++) {
                $cellVal = trim((string)$sheet->getCell([$col, 1])->getValue());
                if ($cellVal !== '') {
                    $headerKeys[$cellVal] = $col;
                }
            }

            $products = Product::all();
            if ($products->isEmpty()) {
                return app(GudangProductController::class)->export();
            }

            $modelMap = Product::getHeaderMap();
            $dbToTikTokMap = array_flip($modelMap);

            $currentRow = 6;
            foreach ($products as $product) {
                foreach ($dbToTikTokMap as $dbField => $tikTokKey) {
                    if (isset($headerKeys[$tikTokKey])) {
                        $colIndex = $headerKeys[$tikTokKey];
                        $val = $product->$dbField;

                        if ($dbField === 'price') {
                            $val = floatval($val);
                        } elseif (in_array($dbField, ['quantity', 'parcel_weight', 'parcel_length', 'parcel_width', 'parcel_height', 'minimum_order_quantity', 'pre_order_time'])) {
                            $val = $val !== null ? intval($val) : null;
                        }

                        $sheet->setCellValue([$colIndex, $currentRow], $val);
                    }
                }
                $currentRow++;
            }

            // Apply professional styling
            $lastRow = max($currentRow - 1, 6);
            if ($lastRow >= 6) {
                $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => '1E293B']],
                    'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]
                ]);

                // Auto-fit column widths up to first 30 columns for performance
                for ($c = 1; $c <= min($colsCount, 30); $c++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
                }
            }

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $filename = 'Export_Produk_Saktindo_' . date('Ymd_His') . '.xlsx';
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . urlencode($filename) . '"');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;
        } catch (\Exception $e) {
            Log::error('TikTok Product Export Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error generating Excel export: ' . $e->getMessage());
        }
    }
}
