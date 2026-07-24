<?php

namespace App\Http\Controllers;

use App\Models\GudangProduct;
use App\Models\InBound;
use App\Models\Rak;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SupplierProduct;
use App\Models\Brand;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

class GudangProductController extends Controller
{
    public function index(Request $request)
    {
        $query = GudangProduct::with(['supplierProduct', 'rack']);

        if ($request->filled('gudang')) {
            $query->whereHas('rack', function ($q) use ($request) {
                $q->where('gudang', $request->gudang);
            });
        }

        $products = $query->latest()->get();

        return view('gudang_product.index', compact('products'));
    }

    public function create()
    {
        $inbounds = InBound::with(['supplier', 'supplierProduct'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        $racks = Rak::orderBy('rak_kode')->get();

        return view('gudang_product.create', compact('inbounds', 'racks'));
    }

    public function store(Request $request){
        $request->validate([
            'in_bound_id' => 'required|exists:in_bounds,id',
            'rack_id' => 'required|exists:raks,rak_kode',
        ]);

        DB::transaction(function () use ($request) {
            $inBound = InBound::with('supplierProduct')->where('id', $request->in_bound_id)
                ->where('status', 'pending')
                ->firstOrFail();

            $existingProduct = GudangProduct::where('supplier_product_id', $inBound->supplier_product_id)
                ->where('rack_id', $request->rack_id)
                ->first();

            if ($existingProduct) {
                $existingProduct->update([
                    'qty' => $existingProduct->qty + $inBound->qty_received,
                    'status' => 'stored',
                ]);
            } else {    

                GudangProduct::create([
                    'id' => GudangProduct::generateId($inBound->supplierProduct->sku ?? null),
                    'supplier_product_id' => $inBound->supplier_product_id,
                    'rack_id' => $request->rack_id,
                    'qty' => $inBound->qty_received,
                    'status' => 'stored',
                ]);
            }

            $inBound->update([
                'status' => 'stored',
            ]);
        });

        return redirect()
            ->route('gudang-product.index')
            ->with('success', 'Barang berhasil disimpan ke rak.');
    }

    public function edit(GudangProduct $gudangProduct)
    {
        $gudangProduct->load(['supplierProduct', 'rack']);
        $racks = Rak::orderBy('rak_kode')->get();
        $brands = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $subCategories = SubCategory::orderBy('name')->get();

        return view('gudang_product.edit', compact('gudangProduct', 'racks', 'brands', 'categories', 'subCategories'));
    }

    public function update(Request $request, GudangProduct $gudangProduct)
    {
        $request->validate([
            'rack_id' => 'required|exists:raks,rak_kode',
            'qty' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'discount' => 'required|numeric|min:0',
            'brand' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'sub_category' => 'nullable|string|max:255',
        ]);

        $gudangProduct->update([
            'rack_id' => $request->rack_id,
            'qty' => $request->qty,
            'price' => $request->price,
            'discount' => $request->discount,
        ]);

        if ($gudangProduct->supplierProduct) {
            $gudangProduct->supplierProduct->update([
                'brand' => $request->brand,
                'category' => $request->category,
                'sub_category' => $request->sub_category,
            ]);
        }

        return redirect()
            ->route('gudang-product.index')
            ->with('status', 'Penempatan barang gudang berhasil diperbarui.');
    }

    public function destroy(GudangProduct $gudangProduct)
    {
        $gudangProduct->delete();

        return redirect()
            ->route('gudang-product.index')
            ->with('success', 'Data barang gudang berhasil dihapus.');
    }

    /**
     * Export warehouse product stock to Excel
     */
    public function export()
    {
        try {
            $products = GudangProduct::with(['supplierProduct', 'rack'])->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Stok Barang');

            // Set Headers
            $headers = [
                'ID',
                'Serial Number',
                'Nama Barang',
                'Brand',
                'Qty',
                'Harga',
                'Kategori 1',
                'Kategori 2',
                'Rak Kode',
                'Lokasi',
                'Status'
            ];
            $sheet->fromArray($headers, null, 'A1');

            // Apply style to header
            $sheet->getStyle('A1:K1')->getFont()->setBold(true);

            // Populate data
            $row = 2;
            foreach ($products as $product) {
                $sheet->setCellValue('A' . $row, $product->id);
                $sheet->setCellValue('B' . $row, $product->supplierProduct->sku ?? '');
                $sheet->setCellValue('C' . $row, $product->supplierProduct->item_name ?? '');
                $sheet->setCellValue('D' . $row, $product->supplierProduct->brand ?? '');
                $sheet->setCellValue('E' . $row, $product->qty);
                $sheet->setCellValue('F' . $row, $product->price);
                $sheet->setCellValue('G' . $row, $product->supplierProduct->category ?? '');
                $sheet->setCellValue('H' . $row, $product->supplierProduct->sub_category ?? '');
                $sheet->setCellValue('I' . $row, $product->rack_id);
                $sheet->setCellValue('J' . $row, $product->rack->location ?? '');
                $sheet->setCellValue('K' . $row, $product->status);
                $row++;
            }

            // Auto-size columns
            foreach (range('A', 'K') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $filename = 'stok_barang_' . date('Ymd_His') . '.xlsx';

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'max-age=0',
            ]);
        } catch (\Exception $e) {
            Log::error('Export Stock Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal melakukan export: ' . $e->getMessage());
        }
    }

    /**
     * Download Excel template for importing stock
     */
    public function downloadTemplate()
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Template Import Stok');

            // Set Headers
            $headers = [
                'ID',
                'Serial Number',
                'Nama Barang',
                'Brand',
                'Qty',
                'Harga',
                'Kategori 1',
                'Kategori 2',
                'Rak Kode',
                'Lokasi',
                'Status'
            ];
            $sheet->fromArray($headers, null, 'A1');

            // Add dummy / example data rows
            $dummyData = [
                ['901', 'SN0011231', 'Lampu LED Hannocs 22 Watt', 'Hannocs', '50', '150000', 'Lamp', 'Bulb', 'RAK-01', 'Gudang A', 'stored'],
                ['902', 'SN0011232', 'Lampu LED Hannocs 18 Watt', 'Hannocs', '100', '250000', 'Lamp', 'Bulb', 'RAK-02', 'Gudang A', 'stored'],
            ];
            $sheet->fromArray($dummyData, null, 'A2');

            // Apply style to header
            $sheet->getStyle('A1:K1')->getFont()->setBold(true);

            // Auto-size columns
            foreach (range('A', 'K') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $filename = 'template_import_stok.xlsx';

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'max-age=0',
            ]);
        } catch (\Exception $e) {
            Log::error('Download Stock Template Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengunduh template: ' . $e->getMessage());
        }
    }

    /**
     * Show import stock page
     */
    public function importPage()
    {
        return view('gudang_product.import');
    }

    /**
     * Import stock data from Excel or CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv,txt',
        ]);

        $file = $request->file('excel_file');

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            if ($highestRow < 2) {
                return redirect()->back()->with('error', 'File Excel/CSV kosong atau tidak memiliki data.');
            }

            $errors = [];
            $importedCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;

            // Default column mapping (Fallback to standard positions)
            $colMap = [
                'id' => 'A',
                'sku' => 'B',
                'item_name' => 'C',
                'brand' => 'D',
                'category' => 'E',
                'qty' => 'F',
                'price' => 'G',
                'sub_category' => 'I',
                'rack_id' => 'J',
                'lokasi' => 'K',
                'status' => 'L',
            ];

            // Auto-detect headers from Row 1
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $cellVal = strtolower(trim((string)$sheet->getCell($colLetter . '1')->getValue()));

                if (in_array($cellVal, ['id', 'id gudang product', 'kode id'])) {
                    $colMap['id'] = $colLetter;
                } elseif (in_array($cellVal, ['sku', 'serial number', 'sn', 'serial_number', 'kode barang', 'kode produk'])) {
                    $colMap['sku'] = $colLetter;
                } elseif (in_array($cellVal, ['nama barang', 'nama produk', 'item name', 'nama', 'item'])) {
                    $colMap['item_name'] = $colLetter;
                } elseif (in_array($cellVal, ['brand', 'merek', 'merk'])) {
                    $colMap['brand'] = $colLetter;
                } elseif (in_array($cellVal, ['qty', 'quantity', 'jumlah', 'stok', 'jumlah stok'])) {
                    $colMap['qty'] = $colLetter;
                } elseif (in_array($cellVal, ['harga', 'price', 'harga beli', 'harga per unit'])) {
                    $colMap['price'] = $colLetter;
                } elseif (in_array($cellVal, ['kategori 1', 'kategori produk', 'kategori', 'category', 'main category'])) {
                    $colMap['category'] = $colLetter;
                } elseif (in_array($cellVal, ['kategori 2', 'sub kategori', 'sub category', 'sub-kategori', 'sub_category'])) {
                    $colMap['sub_category'] = $colLetter;
                } elseif (in_array($cellVal, ['rak kode', 'kode rak', 'rak', 'rack', 'rack id', 'rack_id'])) {
                    $colMap['rack_id'] = $colLetter;
                } elseif (in_array($cellVal, ['lokasi', 'location', 'lokasi rak'])) {
                    $colMap['lokasi'] = $colLetter;
                } elseif (in_array($cellVal, ['status', 'status stok'])) {
                    $colMap['status'] = $colLetter;
                }
            }

            // First pass: validation
            $rowsToProcess = [];
            for ($row = 2; $row <= $highestRow; $row++) {
                $id = trim((string)$sheet->getCell($colMap['id'] . $row)->getValue());
                $sku = trim((string)$sheet->getCell($colMap['sku'] . $row)->getValue());
                $itemName = trim((string)$sheet->getCell($colMap['item_name'] . $row)->getValue());
                $brandVal = trim((string)$sheet->getCell($colMap['brand'] . $row)->getValue());
                $qtyVal = $sheet->getCell($colMap['qty'] . $row)->getValue();
                $hargaVal = $sheet->getCell($colMap['price'] . $row)->getValue();
                $kategori1 = trim((string)$sheet->getCell($colMap['category'] . $row)->getValue());
                $kategori2 = trim((string)$sheet->getCell($colMap['sub_category'] . $row)->getValue());
                $rakKode = trim((string)$sheet->getCell($colMap['rack_id'] . $row)->getValue());
                $lokasiVal = trim((string)$sheet->getCell($colMap['lokasi'] . $row)->getValue());
                $status = trim((string)$sheet->getCell($colMap['status'] . $row)->getValue());

                // Skip category subheaders or completely blank rows
                if (empty($id) && empty($sku) && ($qtyVal === null || $qtyVal === '' || !is_numeric($qtyVal)) && (empty($rakKode) || $rakKode === '-') && empty($brandVal) && empty($hargaVal)) {
                    continue;
                }

                $rowErrors = [];

                if (empty($sku)) {
                    if (!empty($id)) {
                        $sku = $id;
                    } elseif (!empty($itemName)) {
                        $sku = 'SKU-' . strtoupper(substr(md5($itemName), 0, 8));
                    } else {
                        $rowErrors[] = 'SKU / Serial Number wajib diisi.';
                    }
                }

                if ($qtyVal === '' || $qtyVal === null) {
                    $qtyVal = 0;
                } elseif (!is_numeric($qtyVal) || intval($qtyVal) < 0) {
                    $rowErrors[] = "Quantity '{$qtyVal}' harus berupa angka bulat positif.";
                }

                if ($hargaVal === '' || $hargaVal === null) {
                    $hargaVal = 0;
                } elseif (!is_numeric($hargaVal) || floatval($hargaVal) < 0) {
                    $rowErrors[] = "Harga '{$hargaVal}' harus berupa angka positif.";
                }

                if (empty($rakKode) || $rakKode === '-') {
                    $rakKode = 'RAK-01';
                }

                if (!empty($rowErrors)) {
                    $errors[] = "Baris {$row}: " . implode(' ', $rowErrors);
                } else {
                    $rowsToProcess[$row] = [
                        'id' => $id ?: null,
                        'sku' => $sku,
                        'item_name' => $itemName ?: $sku,
                        'qty' => intval($qtyVal),
                        'price' => floatval($hargaVal),
                        'discount' => 0,
                        'rack_id' => $rakKode,
                        'lokasi' => $lokasiVal ?: 'Gudang',
                        'status' => $status ?: 'stored',
                        'brand' => $brandVal ?: null,
                        'category' => $kategori1 ?: null,
                        'sub_category' => $kategori2 ?: null,
                    ];
                }
            }

            if (!empty($errors)) {
                return redirect()->back()
                    ->with('error_list', $errors)
                    ->with('error', 'Import dibatalkan karena ada kesalahan data.');
            }



            // Second pass: database operations in transaction
            DB::beginTransaction();

            foreach ($rowsToProcess as $data) {
                // 1. Process brand case-insensitively
                $brandName = $data['brand'];
                if (!empty($brandName)) {
                    $existingBrand = Brand::whereRaw('LOWER(name) = ?', [strtolower($brandName)])->first();
                    if (!$existingBrand) {
                        $existingBrand = Brand::create([
                            'name' => $brandName,
                            'image' => '',
                            'alt' => ''
                        ]);
                    }
                    $brandName = $existingBrand->name; // Preserve original master casing
                }

                // 2. Process category and sub-category case-insensitively
                $categoryName = $data['category'];
                $subCategoryName = $data['sub_category'];

                if (!empty($categoryName)) {
                    $existingCategory = Category::whereRaw('LOWER(name) = ?', [strtolower($categoryName)])->first();
                    if (!$existingCategory) {
                        $existingCategory = Category::create(['name' => $categoryName]);
                    }
                    $categoryName = $existingCategory->name; // Preserve original master casing

                    if (!empty($subCategoryName)) {
                        $existingSubCategory = SubCategory::where('category_id', $existingCategory->id)
                            ->whereRaw('LOWER(name) = ?', [strtolower($subCategoryName)])
                            ->first();
                        if (!$existingSubCategory) {
                            $existingSubCategory = SubCategory::create([
                                'category_id' => $existingCategory->id,
                                'name' => $subCategoryName
                            ]);
                        }
                        $subCategoryName = $existingSubCategory->name; // Preserve original master casing
                    }
                }

                // 3. Find or auto-create the SupplierProduct record
                $sku = $data['sku'];
                $supplierProduct = SupplierProduct::where('sku', $sku)->first();
                if (!$supplierProduct) {
                    $supplier = Supplier::first();
                    if (!$supplier) {
                        $supplier = Supplier::create([
                            'name' => 'Supplier General',
                            'status' => 'active'
                        ]);
                    }

                    $supplierProduct = SupplierProduct::create([
                        'supplier_id' => $supplier->id,
                        'sku' => $sku,
                        'item_name' => $data['item_name'] ?: $sku,
                        'brand' => $brandName ?: null,
                        'category' => $categoryName ?: null,
                        'sub_category' => $subCategoryName ?: null,
                        'unit' => 'pcs',
                        'status' => 'active'
                    ]);
                } else {
                    // Update metadata if provided
                    $updateData = [];
                    if (!empty($brandName)) {
                        $updateData['brand'] = $brandName;
                    }
                    if (!empty($categoryName)) {
                        $updateData['category'] = $categoryName;
                    }
                    if (!empty($subCategoryName)) {
                        $updateData['sub_category'] = $subCategoryName;
                    }
                    if (!empty($updateData)) {
                        $supplierProduct->update($updateData);
                    }
                }

                // 4. Find or auto-create the Rak record
                $rakKode = $data['rack_id'];
                $rack = Rak::where('rak_kode', $rakKode)->first();
                if (!$rack) {
                    $rack = Rak::create([
                        'rak_kode' => $rakKode,
                        'location' => $data['lokasi'] ?: 'Gudang'
                    ]);
                }

                // 5. Query and create or update GudangProduct
                $existingProduct = null;

                // 1. Try to find by ID if provided
                if (!empty($data['id'])) {
                    $existingProduct = GudangProduct::find($data['id']);
                }

                // 2. Try to find by supplier_product_id and rack_id if not found by ID
                if (!$existingProduct) {
                    $existingProduct = GudangProduct::where('supplier_product_id', $supplierProduct->id)
                        ->where('rack_id', $rakKode)
                        ->first();
                }

                if ($existingProduct) {
                    $incomingStock = [
                        'qty' => $data['qty'],
                        'price' => $data['price'],
                        'discount' => $data['discount'],
                        'rack_id' => $rakKode,
                        'status' => $data['status'],
                    ];

                    $hasChange = false;
                    foreach ($incomingStock as $key => $newVal) {
                        $oldVal = $existingProduct->$key ?? null;
                        if ((string) $oldVal !== (string) $newVal) {
                            $hasChange = true;
                            break;
                        }
                    }

                    if ($hasChange) {
                        $existingProduct->update($incomingStock);
                        $updatedCount++;
                    } else {
                        $skippedCount++;
                    }
                } else {
                    // Create new
                    GudangProduct::create([
                        'id' => $data['id'] ?: GudangProduct::generateId($sku),
                        'supplier_product_id' => $supplierProduct->id,
                        'rack_id' => $rakKode,
                        'qty' => $data['qty'],
                        'price' => $data['price'],
                        'discount' => $data['discount'],
                        'status' => $data['status']
                    ]);
                    $importedCount++;
                }
            }

            DB::commit();

            return redirect()->route('gudang-product.index')
                ->with('status', "Import berhasil! {$importedCount} stok baru ditambahkan, {$updatedCount} diperbarui, dan {$skippedCount} dilewati (tidak ada perubahan).");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import Stock Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal memproses file Excel/CSV: ' . $e->getMessage());
        }
    }
}