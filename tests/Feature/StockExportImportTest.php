<?php

use App\Models\User;
use App\Models\Rak;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\GudangProduct;
use App\Models\Brand;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('super admin can export stock', function () {
    $superAdmin = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $response = $this->actingAs($superAdmin)
        ->get(route('gudang-product.export'));

    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('super admin can download template', function () {
    $superAdmin = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $response = $this->actingAs($superAdmin)
        ->get(route('gudang-product.download-template'));

    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('super admin can import valid stock excel', function () {
    $superAdmin = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    // Create supplier, supplier product and rack
    $supplier = Supplier::create([
        'name' => 'Supplier Test',
        'code' => 'SUP-TEST',
    ]);

    $product = SupplierProduct::create([
        'id' => 'SPR-000001',
        'supplier_id' => $supplier->id,
        'sku' => 'SKU-IMPORT-TEST-1',
        'item_name' => 'Test Item 1',
        'category' => 'Electronics',
        'status' => 'active',
    ]);

    $rack = Rak::create([
        'rak_kode' => 'RAK-IMPORT-1',
        'location' => 'Zone X',
    ]);

    // Generate Excel file: ID, Serial Number, Nama Barang, Brand, Qty, Harga, Kategori 1, Kategori 2, Rak Kode, Lokasi, Status
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'ID');
    $sheet->setCellValue('B1', 'Serial Number');
    $sheet->setCellValue('C1', 'Nama Barang');
    $sheet->setCellValue('D1', 'Brand');
    $sheet->setCellValue('E1', 'Qty');
    $sheet->setCellValue('F1', 'Harga');
    $sheet->setCellValue('G1', 'Kategori 1');
    $sheet->setCellValue('H1', 'Kategori 2');
    $sheet->setCellValue('I1', 'Rak Kode');
    $sheet->setCellValue('J1', 'Lokasi');
    $sheet->setCellValue('K1', 'Status');

    $sheet->setCellValue('A2', '');
    $sheet->setCellValue('B2', 'SKU-IMPORT-TEST-1');
    $sheet->setCellValue('C2', 'Test Item 1');
    $sheet->setCellValue('D2', 'Brand X');
    $sheet->setCellValue('E2', '55');
    $sheet->setCellValue('F2', '125000');
    $sheet->setCellValue('G2', 'Lamp');
    $sheet->setCellValue('H2', 'Bulb');
    $sheet->setCellValue('I2', 'RAK-IMPORT-1');
    $sheet->setCellValue('J2', 'Zone X');
    $sheet->setCellValue('K2', 'stored');

    $tempFilePath = tempnam(sys_get_temp_dir(), 'excel_import_');
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save($tempFilePath);

    $uploadedFile = new UploadedFile(
        $tempFilePath,
        'stok_import.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );

    $response = $this->actingAs($superAdmin)
        ->post(route('gudang-product.import'), [
            'excel_file' => $uploadedFile,
        ]);

    $response->assertRedirect(route('gudang-product.index'));
    $response->assertSessionHas('status');

    // Assert DB has record with correct price
    $this->assertDatabaseHas('gudang_products', [
        'supplier_product_id' => $product->id,
        'rack_id' => $rack->rak_kode,
        'qty' => 55,
        'price' => 125000.00,
        'status' => 'stored',
    ]);

    $this->assertDatabaseHas('supplier_products', [
        'id' => $product->id,
        'category' => 'Lamp',
        'sub_category' => 'Bulb',
    ]);

    @unlink($tempFilePath);
});

test('super admin can import valid stock csv', function () {
    $superAdmin = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $supplier = Supplier::create([
        'name' => 'Supplier Test 2',
        'code' => 'SUP-TEST-2',
    ]);

    $product = SupplierProduct::create([
        'id' => 'SPR-000002',
        'supplier_id' => $supplier->id,
        'sku' => 'SKU-IMPORT-CSV-1',
        'item_name' => 'Test Item 2',
        'category' => 'Electronics',
        'status' => 'active',
    ]);

    $rack = Rak::create([
        'rak_kode' => 'RAK-IMPORT-2',
        'location' => 'Zone Y',
    ]);

    // Create CSV content
    $csvContent = "ID,Serial Number,Nama Barang,Brand,Qty,Harga,Kategori 1,Kategori 2,Rak Kode,Lokasi,Status\n";
    $csvContent .= ",SKU-IMPORT-CSV-1,Test Item 2,Brand Y,80,245000,Lamp,Bulb,RAK-IMPORT-2,Zone Y,stored\n";

    $tempFilePath = tempnam(sys_get_temp_dir(), 'csv_import_');
    file_put_contents($tempFilePath, $csvContent);

    $uploadedFile = new UploadedFile(
        $tempFilePath,
        'stok_import.csv',
        'text/csv',
        null,
        true
    );

    $response = $this->actingAs($superAdmin)
        ->post(route('gudang-product.import'), [
            'excel_file' => $uploadedFile,
        ]);

    $response->assertRedirect(route('gudang-product.index'));
    $response->assertSessionHas('status');

    $this->assertDatabaseHas('gudang_products', [
        'supplier_product_id' => $product->id,
        'rack_id' => $rack->rak_kode,
        'qty' => 80,
        'price' => 245000.00,
        'status' => 'stored',
    ]);

    $this->assertDatabaseHas('supplier_products', [
        'id' => $product->id,
        'category' => 'Lamp',
        'sub_category' => 'Bulb',
    ]);

    @unlink($tempFilePath);
});

test('import validates missing or invalid data', function () {
    $superAdmin = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    // Generate Excel file with invalid rows
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'ID');
    $sheet->setCellValue('B1', 'Serial Number');
    $sheet->setCellValue('C1', 'Nama Barang');
    $sheet->setCellValue('D1', 'Brand');
    $sheet->setCellValue('E1', 'Qty');
    $sheet->setCellValue('F1', 'Harga');
    $sheet->setCellValue('G1', 'Kategori 1');
    $sheet->setCellValue('H1', 'Kategori 2');
    $sheet->setCellValue('I1', 'Rak Kode');
    $sheet->setCellValue('J1', 'Lokasi');
    $sheet->setCellValue('K1', 'Status');

    // Row 2: Invalid SKU, invalid Rak, negative Qty, negative Harga
    $sheet->setCellValue('A2', '');
    $sheet->setCellValue('B2', 'INVALID-SKU-999');
    $sheet->setCellValue('C2', 'Invalid Item');
    $sheet->setCellValue('D2', 'No Brand');
    $sheet->setCellValue('E2', '-10');
    $sheet->setCellValue('F2', '-5000');
    $sheet->setCellValue('G2', 'Lamp');
    $sheet->setCellValue('H2', 'Bulb');
    $sheet->setCellValue('I2', 'NON-EXISTENT-RACK');
    $sheet->setCellValue('J2', 'No Where');
    $sheet->setCellValue('K2', 'stored');

    $tempFilePath = tempnam(sys_get_temp_dir(), 'excel_import_invalid_');
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save($tempFilePath);

    $uploadedFile = new UploadedFile(
        $tempFilePath,
        'stok_import_invalid.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );

    $response = $this->actingAs($superAdmin)
        ->from(route('gudang-product.index'))
        ->post(route('gudang-product.import'), [
            'excel_file' => $uploadedFile,
        ]);

    $response->assertRedirect(route('gudang-product.index'));
    $response->assertSessionHas('error');
    $response->assertSessionHas('error_list');

    $errors = session('error_list');
    expect($errors)->toBeArray();
    expect($errors[0])->toContain("Baris 2:");
    expect($errors[0])->toContain("Quantity '-10' harus berupa angka bulat positif.");
    expect($errors[0])->toContain("Harga '-5000' harus berupa angka positif.");

    @unlink($tempFilePath);
});

test('sales finance create form retrieves stock price and discount percentage', function () {
    $superAdmin = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    // Create supplier, product and rack
    $supplier = Supplier::create([
        'name' => 'Supplier Test PO',
        'code' => 'SUP-PO',
    ]);

    $product = SupplierProduct::create([
        'id' => 'SPR-999',
        'supplier_id' => $supplier->id,
        'sku' => 'SKU-PO-TEST',
        'item_name' => 'Item PO Test',
        'category' => 'Electronics',
        'last_purchase_price' => 100000,
        'status' => 'active',
    ]);

    $rack = Rak::create([
        'rak_kode' => 'RAK-PO-1',
        'location' => 'Zone PO',
    ]);

    // Create warehouse product (GudangProduct) with custom price & discount percentage
    GudangProduct::create([
        'id' => 'GPROD-999999',
        'supplier_product_id' => $product->id,
        'rack_id' => $rack->rak_kode,
        'qty' => 10,
        'price' => 150000,
        'discount' => 15, // 15%
        'status' => 'stored',
    ]);

    $response = $this->actingAs($superAdmin)
        ->get(route('sales-finance.create'));

    $response->assertOk();
    $productOptions = $response->viewData('productOptions');
    
    $mappedProduct = $productOptions->firstWhere('id', $product->id);
    expect($mappedProduct)->not->toBeNull();
    expect($mappedProduct->custom_price)->toEqual(150000);
    expect($mappedProduct->discount_percent)->toEqual(15);
});

test('super admin can access stock import page', function () {
    $superAdmin = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $response = $this->actingAs($superAdmin)
        ->get(route('gudang-product.importPage'));

    $response->assertOk();
    $response->assertSee('Panduan Pengisian Data Import');
    $response->assertSee('Download Template Excel');
});

test('import dynamically creates brand, category and subcategory case-insensitively', function () {
    $superAdmin = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $supplier = Supplier::create([
        'name' => 'Supplier Test PO',
        'code' => 'SUP-PO',
    ]);

    $product = SupplierProduct::create([
        'id' => 'SPR-998',
        'supplier_id' => $supplier->id,
        'sku' => 'SKU-CASE-TEST',
        'item_name' => 'Item Case Test',
        'status' => 'active',
    ]);

    $rack = Rak::create([
        'rak_kode' => 'RAK-CASE-1',
        'location' => 'Zone Case',
    ]);

    // Create a brand and category in database beforehand to check case-insensitive match
    Brand::create(['name' => 'Hannocs']);
    Category::create(['name' => 'Lighting']);

    // Create CSV content with mixed-casing brand/category/subcategory
    $csvContent = "ID,Serial Number,Nama Barang,Brand,Qty,Harga,Kategori 1,Kategori 2,Rak Kode,Lokasi,Status\n";
    $csvContent .= ",SKU-CASE-TEST,Item Case Test,hAnNoCs,10,100000,liGhTiNg,Led,RAK-CASE-1,Zone Case,stored\n";

    $tempFilePath = tempnam(sys_get_temp_dir(), 'csv_import_case_');
    file_put_contents($tempFilePath, $csvContent);

    $uploadedFile = new UploadedFile(
        $tempFilePath,
        'stok_import.csv',
        'text/csv',
        null,
        true
    );

    $response = $this->actingAs($superAdmin)
        ->post(route('gudang-product.import'), [
            'excel_file' => $uploadedFile,
        ]);

    $response->assertRedirect(route('gudang-product.index'));
    $response->assertSessionHas('status');

    // Assert that the existing brand & category casing were preserved and not duplicated
    $this->assertEquals(1, Brand::whereRaw('LOWER(name) = ?', ['hannocs'])->count());
    $this->assertEquals('Hannocs', Brand::first()->name);

    $this->assertEquals(1, Category::whereRaw('LOWER(name) = ?', ['lighting'])->count());
    $this->assertEquals('Lighting', Category::first()->name);

    // Assert that the subcategory 'Led' was auto-created
    $this->assertDatabaseHas('sub_categories', [
        'name' => 'Led',
    ]);

    // Assert product was updated to existing casing
    $this->assertDatabaseHas('supplier_products', [
        'id' => $product->id,
        'brand' => 'Hannocs',
        'category' => 'Lighting',
        'sub_category' => 'Led',
    ]);

    @unlink($tempFilePath);
});

