<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\GudangProduct;
use App\Models\InBound;
use App\Models\Master_customer;
use App\Models\Product;
use App\Models\Rak;
use App\Models\SalesOrder;
use App\Models\SubCategory;
use App\Models\Supplier;
use App\Models\SupplierPO;
use App\Models\SupplierProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

uses(RefreshDatabase::class);

test('point 1: button sync works across inbound, gudang, and product modules', function () {
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $supplier = Supplier::create(['name' => 'Supplier Sync Test']);
    $rack = Rak::create(['rak_kode' => 'RAK-SYNC-1', 'location' => 'Zona 1', 'gudang' => 'js']);

    $sp = SupplierProduct::create([
        'supplier_id' => $supplier->id,
        'sku' => 'SKU-SYNC-P1',
        'item_name' => 'Produk Sync Test 1',
        'last_purchase_price' => 50000,
        'status' => 'active',
    ]);

    GudangProduct::create([
        'id' => 'GP-SYNC-001',
        'supplier_product_id' => $sp->id,
        'rack_id' => $rack->rak_kode,
        'gudang_type' => 'JS',
        'qty' => 20,
        'price' => 50000,
        'status' => 'stored',
    ]);

    // Test Inbound Sync
    $responseInbound = $this->actingAs($superAdmin)->post(route('inbound.sync'));
    $responseInbound->assertRedirect(route('inbound.index'));
    $responseInbound->assertSessionHas('success');
    $responseInbound->assertSessionHas('status');

    // Test Gudang Product Sync
    $responseGudang = $this->actingAs($superAdmin)->post(route('gudang-product.sync'));
    $responseGudang->assertRedirect(route('gudang-product.index'));
    $responseGudang->assertSessionHas('status');
    $responseGudang->assertSessionHas('success');

    // Test Product Catalog Sync
    $responseProduct = $this->actingAs($superAdmin)->post(route('products.sync'));
    $responseProduct->assertRedirect(route('products.index'));
    $responseProduct->assertSessionHas('success');
    $responseProduct->assertSessionHas('status');

    // Verify product catalog updated
    $this->assertDatabaseHas('products', [
        'seller_sku' => 'SKU-SYNC-P1',
        'quantity' => 20,
    ]);
});

test('point 2: input barang masuk with manual invoice number is saved, displayed in index, and editable', function () {
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $supplier = Supplier::create(['name' => 'Supplier Manual No Test']);
    $rack = Rak::create(['rak_kode' => 'RAK-MN-1', 'location' => 'Zona M', 'gudang' => 'js']);

    $sp = SupplierProduct::create([
        'supplier_id' => $supplier->id,
        'sku' => 'SKU-MANUAL-NO-01',
        'item_name' => 'Barang Manual No 1',
        'last_purchase_price' => 35000,
        'status' => 'active',
    ]);

    $manualInvoiceNumber = 'SJ-MANUAL-2026-999';

    // Store inbound with manual invoice number
    $response = $this->actingAs($superAdmin)->post(route('inbound.store'), [
        'supplier_id' => $supplier->id,
        'supplier_product_id' => $sp->id,
        'qty_received' => 15,
        'hpp' => 35000,
        'received_date' => '2026-09-25',
        'invoice_number' => $manualInvoiceNumber,
        'rack_id' => $rack->rak_kode,
    ]);

    $response->assertRedirect(route('inbound.index'));

    $inbound = InBound::where('invoice_number', $manualInvoiceNumber)->first();
    expect($inbound)->not->toBeNull();
    expect($inbound->qty_received)->toBe(15);

    // Verify index page displays the manual invoice number
    $indexResponse = $this->actingAs($superAdmin)->get(route('inbound.index'));
    $indexResponse->assertOk();
    $indexResponse->assertSee($manualInvoiceNumber);
    $indexResponse->assertSee('No. Manual (Invoice / SJ)');

    // Verify edit page contains the manual invoice number
    $editResponse = $this->actingAs($superAdmin)->get(route('inbound.edit', $inbound->id));
    $editResponse->assertOk();
    $editResponse->assertSee($manualInvoiceNumber);

    // Update manual number
    $updatedManualNumber = 'SJ-MANUAL-REVISED-888';
    $updateResponse = $this->actingAs($superAdmin)->put(route('inbound.update', $inbound->id), [
        'supplier_id' => $supplier->id,
        'supplier_product_id' => $sp->id,
        'qty_received' => 15,
        'received_date' => '2026-09-25',
        'invoice_number' => $updatedManualNumber,
    ]);

    $updateResponse->assertRedirect(route('inbound.index'));
    $this->assertDatabaseHas('in_bounds', [
        'id' => $inbound->id,
        'invoice_number' => $updatedManualNumber,
    ]);
});

test('point 3: supplier po process to invoice faktur generates pdf without crash', function () {
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $supplier = Supplier::create(['name' => 'Supplier PO Faktur Test', 'code' => 'SUP-FAKTUR']);

    $sp = SupplierProduct::create([
        'supplier_id' => $supplier->id,
        'sku' => 'SKU-PO-FAKTUR-01',
        'item_name' => 'Item Faktur Test',
        'last_purchase_price' => 75000,
        'status' => 'active',
    ]);

    $po = SupplierPO::create([
        'id' => 'PO-FAKTUR-001',
        'supplier_id' => $supplier->id,
        'po_number' => 'PO-20260925-001',
        'order_date' => '2026-09-25',
        'status' => 'received',
        'total_amount' => 150000,
    ]);

    $po->items()->create([
        'supplier_product_id' => $sp->id,
        'qty' => 2,
        'unit_price' => 75000,
        'total_price' => 150000,
    ]);

    // Request the invoice faktur PDF
    $response = $this->actingAs($superAdmin)->get(route('supplier-po.invoice', $po->id));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->getContent())->not->toBeEmpty();
});

test('point 4: excel stock import does not create dummy inbound records', function () {
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $supplier = Supplier::create(['name' => 'Supplier Excel Clean Flow', 'code' => 'SUP-CLEAN']);
    $rack = Rak::create(['rak_kode' => 'RAK-CLEAN-1', 'location' => 'Zona Clean', 'gudang' => 'js']);

    $sp = SupplierProduct::create([
        'id' => 'SPR-CLEAN-01',
        'supplier_id' => $supplier->id,
        'sku' => 'SKU-CLEAN-01',
        'item_name' => 'Produk Clean Import',
        'category' => 'Bahan',
        'status' => 'active',
    ]);

    $inboundCountBefore = InBound::count();

    // Generate Excel file
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
    $sheet->setCellValue('B2', 'SKU-CLEAN-01');
    $sheet->setCellValue('C2', 'Produk Clean Import');
    $sheet->setCellValue('D2', 'CleanBrand');
    $sheet->setCellValue('E2', '40');
    $sheet->setCellValue('F2', '80000');
    $sheet->setCellValue('G2', 'Bahan');
    $sheet->setCellValue('H2', 'SubBahan');
    $sheet->setCellValue('I2', 'RAK-CLEAN-1');
    $sheet->setCellValue('J2', 'Zona Clean');
    $sheet->setCellValue('K2', 'stored');

    $tempFilePath = tempnam(sys_get_temp_dir(), 'clean_import_');
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save($tempFilePath);

    $uploadedFile = new UploadedFile($tempFilePath, 'stok_clean.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

    $response = $this->actingAs($superAdmin)->post(route('gudang-product.import'), [
        'excel_file' => $uploadedFile,
    ]);

    $response->assertRedirect(route('gudang-product.index'));

    // Assert GudangProduct was created with stock
    $this->assertDatabaseHas('gudang_products', [
        'supplier_product_id' => $sp->id,
        'rack_id' => 'RAK-CLEAN-1',
        'qty' => 40,
    ]);

    // Assert NO dummy InBound records were created by the Excel import
    $inboundCountAfter = InBound::count();
    expect($inboundCountAfter)->toBe($inboundCountBefore);

    @unlink($tempFilePath);
});

test('point 5: sales order submit handles nearby_store, missing customer_name, and missing po_number gracefully', function () {
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $customer = Master_customer::create([
        'id' => 'CUST-SO-FIX-01',
        'nama_customer' => 'PT Toko Mitra Mandiri',
        'nama_pic' => 'Budi Santoso',
        'nomor_hp' => '08123456789',
        'alamat' => 'Jl. Merdeka No. 12',
        'kota' => 'Jakarta',
    ]);

    $supplier = Supplier::create(['name' => 'Supplier SO Fix']);
    $sp = SupplierProduct::create([
        'supplier_id' => $supplier->id,
        'sku' => 'SKU-SO-FIX-01',
        'item_name' => 'Barang SO Fix',
        'last_purchase_price' => 50000,
        'status' => 'active',
    ]);

    // Submit SO with customer_id but EMPTY customer_name and EMPTY customer_po_number
    // and sales_type = 'nearby_store' and order_status = 'ready_to_invoice'
    $response = $this->actingAs($superAdmin)->post(route('sales-finance.store'), [
        'customer_id' => $customer->id,
        'customer_name' => '', // left empty by user
        'customer_po_number' => '', // left empty by user
        'sales_type' => 'nearby_store',
        'toko' => 'js',
        'jenis_invoice' => 'normal',
        'order_date' => '2026-09-25',
        'order_status' => 'ready_to_invoice',
        'items' => [
            [
                'product_code' => $sp->id,
                'product_name' => $sp->item_name,
                'quantity' => 5,
                'unit_price' => 60000,
                'unit' => 'pcs',
                'discount_1' => 0,
                'discount_2' => 0,
                'discount_3' => 0,
                'discount_4' => 0,
            ]
        ]
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    // Assert SO created with auto-resolved customer_name and generated customer_po_number
    $createdSO = SalesOrder::where('customer_id', $customer->id)->first();
    expect($createdSO)->not->toBeNull();
    expect($createdSO->customer_name)->toBe('PT Toko Mitra Mandiri');
    expect($createdSO->customer_po_number)->not->toBeEmpty();
    expect($createdSO->sales_type)->toBe('nearby_store');
});

test('point 6: inbound index and gudang stock views show receipt date along with po number', function () {
    $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $supplier = Supplier::create(['name' => 'PT Sumber Makmur']);
    $rack = Rak::create(['rak_kode' => 'RAK-P6-01', 'location' => 'Lantai 2', 'gudang' => 'js']);

    $sp = SupplierProduct::create([
        'supplier_id' => $supplier->id,
        'sku' => 'SKU-P6-001',
        'item_name' => 'Monitor LED Saktindo 24 Inch',
        'last_purchase_price' => 1200000,
        'status' => 'active',
    ]);

    $po = SupplierPO::create([
        'id' => 'PO-P6-001',
        'supplier_id' => $supplier->id,
        'po_number' => 'PO-SUP-2026-8888',
        'order_date' => '2026-09-20',
        'status' => 'received',
        'total_amount' => 12000000,
    ]);

    $inbound = InBound::create([
        'id' => 'INB-P6-001',
        'supplier_id' => $supplier->id,
        'supplier_product_id' => $sp->id,
        'supplier_po_id' => $po->id,
        'invoice_number' => 'SJ-P6-777',
        'received_date' => '2026-09-22',
        'qty_received' => 10,
        'status' => 'stored',
    ]);

    GudangProduct::create([
        'id' => 'GP-P6-001',
        'supplier_product_id' => $sp->id,
        'rack_id' => $rack->rak_kode,
        'gudang_type' => 'JS',
        'qty' => 10,
        'price' => 1200000,
        'status' => 'stored',
    ]);

    // 1. Inbound Index Page shows date and PO number
    $inboundResponse = $this->actingAs($superAdmin)->get(route('inbound.index'));
    $inboundResponse->assertOk();
    $inboundResponse->assertSee('PO-SUP-2026-8888');
    $inboundResponse->assertSee('22/09/2026');
    $inboundResponse->assertSee('SJ-P6-777');

    // 2. Gudang Product Stock Page shows receipt date and PO number
    $gudangResponse = $this->actingAs($superAdmin)->get(route('gudang-product.index'));
    $gudangResponse->assertOk();
    $gudangResponse->assertSee('Barang Masuk (Tgl / PO)');
    $gudangResponse->assertSee('PO-SUP-2026-8888');
    $gudangResponse->assertSee('22/09/2026');
    $gudangResponse->assertSee('SJ-P6-777');
});
