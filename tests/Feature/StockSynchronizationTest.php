<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\GudangProduct;
use App\Models\InBound;
use App\Models\Product;
use App\Models\Rak;
use App\Models\SubCategory;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\User;
use App\Services\StockSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('inbound creation with direct rack placement syncs gudang stock and product catalog', function () {
    $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $supplier = Supplier::create(['name' => 'Supplier ABC']);
    $rack = Rak::create(['rak_kode' => 'RAK-TEST-01', 'location' => 'Lantai 1', 'gudang' => 'js']);
    $sp = SupplierProduct::create([
        'supplier_id' => $supplier->id,
        'sku' => 'SKU-SYNC-001',
        'item_name' => 'Produk Tes Sync 1',
        'last_purchase_price' => 25000,
        'unit' => 'pcs',
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)->post(route('inbound.store'), [
        'supplier_id' => $supplier->id,
        'supplier_product_id' => $sp->id,
        'qty_received' => 50,
        'hpp' => 25000,
        'received_date' => '2026-09-21',
        'rack_id' => $rack->rak_kode,
    ]);

    $response->assertRedirect(route('inbound.index'));

    // Check InBound
    $this->assertDatabaseHas('in_bounds', [
        'supplier_product_id' => $sp->id,
        'qty_received' => 50,
        'status' => 'stored',
    ]);

    // Check GudangProduct
    $this->assertDatabaseHas('gudang_products', [
        'supplier_product_id' => $sp->id,
        'rack_id' => $rack->rak_kode,
        'qty' => 50,
    ]);

    // Check Product (Catalog)
    $this->assertDatabaseHas('products', [
        'seller_sku' => 'SKU-SYNC-001',
        'product_name' => 'Produk Tes Sync 1',
        'quantity' => 50,
    ]);
});

test('updating inbound quantity adjusts gudang product and catalog quantity', function () {
    $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $supplier = Supplier::create(['name' => 'Supplier ABC']);
    $rack = Rak::create(['rak_kode' => 'RAK-TEST-01', 'location' => 'Lantai 1', 'gudang' => 'js']);
    $sp = SupplierProduct::create([
        'supplier_id' => $supplier->id,
        'sku' => 'SKU-SYNC-002',
        'item_name' => 'Produk Tes Sync 2',
        'last_purchase_price' => 30000,
        'unit' => 'pcs',
        'status' => 'active',
    ]);

    $inbound = InBound::create([
        'id' => InBound::generateId(),
        'supplier_id' => $supplier->id,
        'supplier_product_id' => $sp->id,
        'qty_received' => 30,
        'received_date' => '2026-09-21',
        'status' => 'stored',
    ]);

    GudangProduct::create([
        'id' => GudangProduct::generateId($sp->sku),
        'supplier_product_id' => $sp->id,
        'rack_id' => $rack->rak_kode,
        'gudang_type' => 'JS',
        'qty' => 30,
        'price' => 30000,
        'status' => 'stored',
    ]);

    StockSyncService::syncProductCatalog($sp);

    // Update qty to 45 (+15)
    $this->actingAs($user)->put(route('inbound.update', $inbound->id), [
        'supplier_id' => $supplier->id,
        'supplier_product_id' => $sp->id,
        'qty_received' => 45,
        'received_date' => '2026-09-21',
    ]);

    $this->assertDatabaseHas('gudang_products', [
        'supplier_product_id' => $sp->id,
        'qty' => 45,
    ]);

    $this->assertDatabaseHas('products', [
        'seller_sku' => 'SKU-SYNC-002',
        'quantity' => 45,
    ]);
});

test('cancelling or deleting stored inbound deducts stock from gudang and catalog', function () {
    $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $supplier = Supplier::create(['name' => 'Supplier ABC']);
    $rack = Rak::create(['rak_kode' => 'RAK-TEST-01', 'location' => 'Lantai 1', 'gudang' => 'js']);
    $sp = SupplierProduct::create([
        'supplier_id' => $supplier->id,
        'sku' => 'SKU-SYNC-003',
        'item_name' => 'Produk Tes Sync 3',
        'last_purchase_price' => 10000,
        'unit' => 'pcs',
        'status' => 'active',
    ]);

    $inbound = InBound::create([
        'id' => InBound::generateId(),
        'supplier_id' => $supplier->id,
        'supplier_product_id' => $sp->id,
        'qty_received' => 20,
        'received_date' => '2026-09-21',
        'status' => 'stored',
    ]);

    GudangProduct::create([
        'id' => GudangProduct::generateId($sp->sku),
        'supplier_product_id' => $sp->id,
        'rack_id' => $rack->rak_kode,
        'gudang_type' => 'JS',
        'qty' => 20,
        'price' => 10000,
        'status' => 'stored',
    ]);

    StockSyncService::syncProductCatalog($sp);

    // Cancel inbound
    $this->actingAs($user)->patch(route('inbound.cancel', $inbound->id));

    $this->assertDatabaseHas('gudang_products', [
        'supplier_product_id' => $sp->id,
        'qty' => 0,
    ]);

    $this->assertDatabaseHas('products', [
        'seller_sku' => 'SKU-SYNC-003',
        'quantity' => 0,
    ]);
});

test('manual stock input in gudang creates inbound audit record and syncs catalog', function () {
    $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $rack = Rak::create(['rak_kode' => 'RAK-TEST-02', 'location' => 'Lantai 2', 'gudang' => 'js']);

    $response = $this->actingAs($user)->post(route('gudang-product.storeManual'), [
        'item_name' => 'Lampu LED Manual Test',
        'sku' => 'SKU-MANUAL-001',
        'brand' => 'Hannochs',
        'category' => 'Lighting',
        'sub_category' => 'LED',
        'price' => 45000,
        'qty' => 100,
        'rack_id' => $rack->rak_kode,
        'gudang_type' => 'JS',
    ]);

    $response->assertRedirect(route('gudang-product.index'));

    $sp = SupplierProduct::where('sku', 'SKU-MANUAL-001')->first();
    expect($sp)->not->toBeNull();

    // Check GudangProduct
    $this->assertDatabaseHas('gudang_products', [
        'supplier_product_id' => $sp->id,
        'rack_id' => $rack->rak_kode,
        'qty' => 100,
    ]);

    // Check InBound audit log created
    $this->assertDatabaseHas('in_bounds', [
        'supplier_product_id' => $sp->id,
        'qty_received' => 100,
        'status' => 'stored',
    ]);

    // Check Product catalog
    $this->assertDatabaseHas('products', [
        'seller_sku' => 'SKU-MANUAL-001',
        'product_name' => 'Lampu LED Manual Test',
        'quantity' => 100,
    ]);
});

test('master product creation with initial stock creates gudang product and syncs catalog', function () {
    $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    $rack = Rak::create(['rak_kode' => 'RAK-TEST-03', 'location' => 'Lantai 3', 'gudang' => 'js']);

    $response = $this->actingAs($user)->post(route('products.store'), [
        'item_name' => 'Kabel Power NYM 2x1.5',
        'sku' => 'SKU-KABEL-001',
        'brand' => 'Supreme',
        'category' => 'Kabel',
        'sub_category' => 'NYM',
        'price' => 150000,
        'unit' => 'roll',
        'initial_qty' => 25,
        'rack_id' => $rack->rak_kode,
        'gudang_type' => 'JS',
    ]);

    $response->assertRedirect(route('products.index'));

    $sp = SupplierProduct::where('sku', 'SKU-KABEL-001')->first();
    expect($sp)->not->toBeNull();

    // Check GudangProduct
    $this->assertDatabaseHas('gudang_products', [
        'supplier_product_id' => $sp->id,
        'rack_id' => $rack->rak_kode,
        'qty' => 25,
    ]);

    // Check InBound log
    $this->assertDatabaseHas('in_bounds', [
        'supplier_product_id' => $sp->id,
        'qty_received' => 25,
        'status' => 'stored',
    ]);

    // Check Catalog
    $this->assertDatabaseHas('products', [
        'seller_sku' => 'SKU-KABEL-001',
        'quantity' => 25,
    ]);
});

test('reconciliation command synchronizes orphan data across all 3 modules', function () {
    $supplier = Supplier::create(['name' => 'Supplier General']);
    $rack = Rak::create(['rak_kode' => 'RAK-01', 'location' => 'Gudang Utama', 'gudang' => 'js']);

    // Create a product only in Product (catalog) with qty 75
    Product::create([
        'seller_sku' => 'SKU-ORPHAN-01',
        'product_name' => 'Orphan Product Test',
        'brand' => 'TestBrand',
        'category' => 'TestCat',
        'price' => 80000,
        'quantity' => 75,
    ]);

    // Run reconciliation
    $report = StockSyncService::reconcileAllStock();

    expect($report['total_supplier_products'])->toBeGreaterThanOrEqual(1);

    $sp = SupplierProduct::where('sku', 'SKU-ORPHAN-01')->first();
    expect($sp)->not->toBeNull();

    $this->assertDatabaseHas('gudang_products', [
        'supplier_product_id' => $sp->id,
        'qty' => 75,
    ]);

    $this->assertDatabaseHas('in_bounds', [
        'supplier_product_id' => $sp->id,
        'qty_received' => 75,
    ]);
});
