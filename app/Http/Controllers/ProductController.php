<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource (Grouped by Parent Product).
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        // Group variations of the same product name to show only one parent row
        $products = Product::select(
                'product_name', 
                'product_id', 
                'category', 
                'main_image',
                DB::raw('MIN(id) as id'), // representative ID for actions
                DB::raw('COUNT(*) as variations_count'),
                DB::raw('MIN(price) as min_price'),
                DB::raw('MAX(price) as max_price'),
                DB::raw('SUM(quantity) as total_quantity')
            )
            ->when($search, function ($query, $search) {
                $query->where('product_name', 'like', "%{$search}%")
                    ->orWhere('product_id', 'like', "%{$search}%")
                    ->orWhere('sku_id', 'like', "%{$search}%")
                    ->orWhere('seller_sku', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            })
            ->groupBy('product_name', 'product_id', 'category', 'main_image')
            ->latest(DB::raw('MIN(created_at)'))
            ->paginate(15)
            ->withQueryString();

        return view('products.index', compact('products', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('products.create');
    }

    /**
     * Store a newly created resource in storage (with multiple variations).
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
            'parcel_weight' => 'required|integer|min:0',
            'cod' => 'required|string|in:Y,N',
            'main_image' => 'required|url',
            'variations' => 'required|array|min:1',
            'variations.*.price' => 'required|numeric|min:0',
            'variations.*.quantity' => 'required|integer|min:0',
        ]);

        $parentData = $request->except(['_token', 'variations']);

        // Generate temporary product ID if empty
        if (empty($parentData['product_id'])) {
            $parentData['product_id'] = 'TEMP_' . time() . rand(10, 99);
        }

        DB::beginTransaction();
        try {
            foreach ($request->input('variations') as $var) {
                $skuId = empty($var['sku_id']) ? ('TEMP_SKU_' . time() . rand(100, 999)) : $var['sku_id'];
                
                Product::create(array_merge($parentData, [
                    'variation_value' => $var['variation_value'] ?? 'Default',
                    'price' => floatval($var['price']),
                    'quantity' => intval($var['quantity']),
                    'sku_id' => $skuId,
                    'seller_sku' => $var['seller_sku'] ?? null,
                ]));
            }
            DB::commit();
            return redirect()->route('products.index')->with('success', 'Product and variations created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Error creating product: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource (fetches all variations).
     */
    public function edit(Product $product)
    {
        // Fetch all variations sharing the same product name
        $variations = Product::where('product_name', $product->product_name)->get();
        return view('products.edit', compact('product', 'variations'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
            'parcel_weight' => 'required|integer|min:0',
            'cod' => 'required|string|in:Y,N',
            'main_image' => 'required|url',
            'variations' => 'required|array|min:1',
            'variations.*.price' => 'required|numeric|min:0',
            'variations.*.quantity' => 'required|integer|min:0',
        ]);

        $originalName = $product->product_name;
        $parentData = $request->except(['_token', '_method', 'variations']);

        if (empty($parentData['product_id'])) {
            $parentData['product_id'] = $product->product_id ?: ('TEMP_' . time() . rand(10, 99));
        }

        DB::beginTransaction();
        try {
            // Delete old variation records first to recreate them cleanly
            Product::where('product_name', $originalName)->delete();

            foreach ($request->input('variations') as $var) {
                $skuId = empty($var['sku_id']) ? ('TEMP_SKU_' . time() . rand(100, 999)) : $var['sku_id'];

                Product::create(array_merge($parentData, [
                    'variation_value' => $var['variation_value'] ?? 'Default',
                    'price' => floatval($var['price']),
                    'quantity' => intval($var['quantity']),
                    'sku_id' => $skuId,
                    'seller_sku' => $var['seller_sku'] ?? null,
                ]));
            }

            DB::commit();
            return redirect()->route('products.index')->with('success', 'Product and variations updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Error updating product: ' . $e->getMessage());
        }
    }

    /**
     * Remove all variations of this product.
     */
    public function destroy(Product $product)
    {
        Product::where('product_name', $product->product_name)->delete();
        return redirect()->route('products.index')->with('success', 'Product and all its variations deleted successfully.');
    }

    /**
     * Import products from uploaded TikTok batch edit Excel template
     */
    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls',
        ]);

        $file = $request->file('excel_file');
        
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getSheetByName('Template') ?: $spreadsheet->getActiveSheet();
            
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $colsCount = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            if ($highestRow < 6) {
                return redirect()->back()->with('error', 'The Excel sheet is empty or contains no product records starting at row 6.');
            }

            $headerKeys = [];
            for ($col = 1; $col <= $colsCount; $col++) {
                $cellVal = trim((string)$sheet->getCell([$col, 1])->getValue());
                if ($cellVal !== '') {
                    $headerKeys[$col] = $cellVal;
                }
            }

            $modelMap = Product::getHeaderMap();
            $importedCount = 0;
            $updatedCount = 0;

            DB::beginTransaction();

            for ($row = 6; $row <= $highestRow; $row++) {
                $productData = [];
                foreach ($headerKeys as $colIndex => $key) {
                    $cellVal = $sheet->getCell([$colIndex, $row])->getValue();
                    $cellValString = $cellVal === null ? null : trim((string)$cellVal);

                    if (isset($modelMap[$key])) {
                        $dbField = $modelMap[$key];
                        $productData[$dbField] = $cellValString;
                    }
                }

                if (empty(array_filter($productData))) {
                    continue;
                }

                if (empty($productData['product_name'])) {
                    continue;
                }

                $productData['price'] = floatval($productData['price'] ?? 0);
                $productData['quantity'] = intval($productData['quantity'] ?? 0);
                $productData['parcel_weight'] = !empty($productData['parcel_weight']) ? intval($productData['parcel_weight']) : null;
                $productData['parcel_length'] = !empty($productData['parcel_length']) ? intval($productData['parcel_length']) : null;
                $productData['parcel_width'] = !empty($productData['parcel_width']) ? intval($productData['parcel_width']) : null;
                $productData['parcel_height'] = !empty($productData['parcel_height']) ? intval($productData['parcel_height']) : null;
                $productData['minimum_order_quantity'] = !empty($productData['minimum_order_quantity']) ? intval($productData['minimum_order_quantity']) : 1;
                $productData['pre_order_time'] = !empty($productData['pre_order_time']) ? intval($productData['pre_order_time']) : null;
                $productData['cod'] = !empty($productData['cod']) ? strtoupper($productData['cod']) : 'Y';

                $existingProduct = null;
                if (!empty($productData['product_id']) && !empty($productData['sku_id'])) {
                    $existingProduct = Product::where('product_id', $productData['product_id'])
                        ->where('sku_id', $productData['sku_id'])
                        ->first();
                }

                if (!$existingProduct && !empty($productData['seller_sku'])) {
                    $existingProduct = Product::where('seller_sku', $productData['seller_sku'])->first();
                }

                if ($existingProduct) {
                    $existingProduct->update($productData);
                    $updatedCount++;
                } else {
                    Product::create($productData);
                    $importedCount++;
                }
            }

            DB::commit();
            return redirect()->route('products.index')->with('success', "Import completed! Imported {$importedCount} variations and updated {$updatedCount} variations.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TikTok Product Import Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error reading Excel file: ' . $e->getMessage());
        }
    }

    /**
     * Export products into TikTok batch edit Excel template
     */
    public function export()
    {
        $templatePath = base_path('Tiktoksellercenter_batchedit_20260520_all_information_template_7.xlsx');

        if (!file_exists($templatePath)) {
            return redirect()->back()->with('error', 'TikTok Seller Center template Excel file not found in the root directory.');
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

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $filename = 'saktindo_produk---' . date('Ymd_His') . '.xlsx';
            
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
