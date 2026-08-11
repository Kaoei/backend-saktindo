<?php

use App\Models\User;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\SupplierPO;
use App\Models\SalesOrder;
use App\Models\Invoice;
use App\Models\SupplierPurchaseHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('super admin can create a supplier po template', function () {
    $superAdmin = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $supplier = Supplier::create([
        'name' => 'Supplier PO Test',
        'code' => 'SUP-PO-TEST',
    ]);

    $product = SupplierProduct::create([
        'supplier_id' => $supplier->id,
        'sku' => 'SKU-PO-F1',
        'item_name' => 'Item PO F1',
        'category' => 'General',
        'last_purchase_price' => 50000,
        'status' => 'active',
    ]);

    $response = $this->actingAs($superAdmin)
        ->post(route('supplier-po.store'), [
            'supplier_id' => $supplier->id,
            'order_date' => '2026-07-01',
            'notes' => 'Testing supplier PO creation',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 10,
                    'price' => 50000,
                    'discount' => 10, // 10%
                ]
            ]
        ]);

    $response->assertRedirect(route('supplier-po.index'));
    $response->assertSessionHas('status');

    $this->assertDatabaseHas('supplier_pos', [
        'supplier_id' => $supplier->id,
        'total_amount' => 450000, // (10 * 50000) * 0.90
        'status' => 'pending',
    ]);
});

test('super admin can merge multiple sales orders into a consolidated invoice', function () {
    $superAdmin = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $so1 = SalesOrder::create([
        'id' => 'SO-TEST-01',
        'customer_name' => 'Customer Merged',
        'customer_po_number' => 'CUST-PO-01',
        'toko' => 'js',
        'jenis_invoice' => 'normal',
        'order_date' => '2026-07-01',
        'order_status' => 'stock_check',
        'subtotal' => 200000,
        'tax_amount' => 0,
        'grand_total' => 200000,
    ]);

    $so2 = SalesOrder::create([
        'id' => 'SO-TEST-02',
        'customer_name' => 'Customer Merged',
        'customer_po_number' => 'CUST-PO-02',
        'toko' => 'js',
        'jenis_invoice' => 'normal',
        'order_date' => '2026-07-01',
        'order_status' => 'stock_check',
        'subtotal' => 300000,
        'tax_amount' => 0,
        'grand_total' => 300000,
    ]);

    $response = $this->actingAs($superAdmin)
        ->from(route('sales-finance.index'))
        ->post(route('sales-finance.merge'), [
            'sales_order_ids' => [$so1->id, $so2->id],
            'tax_type' => 'sjb_non_pajak',
            'invoice_date' => '2026-07-01',
        ]);

    $response->assertRedirect(route('sales-finance.index'));
    $response->assertSessionHas('status');

    $this->assertDatabaseHas('invoices', [
        'subtotal' => 500000,
        'grand_total' => 500000,
        'outstanding_amount' => 500000,
    ]);
});

test('finance controller overview retrieves correct ap and ar summaries', function () {
    $superAdmin = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $so = SalesOrder::create([
        'id' => 'SO-FIN-01',
        'customer_name' => 'Customer Fin',
        'customer_po_number' => 'CUST-PO-FIN',
        'toko' => 'js',
        'jenis_invoice' => 'normal',
        'order_date' => '2026-07-01',
        'order_status' => 'invoiced',
        'subtotal' => 100000,
        'tax_amount' => 0,
        'grand_total' => 100000,
    ]);

    Invoice::create([
        'sales_order_id' => $so->id,
        'invoice_number' => 'INV-FIN-01',
        'tax_type' => 'js',
        'faktur_number' => 'FKT-FIN-01',
        'invoice_date' => '2026-07-01',
        'status' => 'outstanding',
        'subtotal' => 100000,
        'tax_amount' => 0,
        'grand_total' => 100000,
        'paid_amount' => 0,
        'outstanding_amount' => 100000,
    ]);

    $supplier = Supplier::create([
        'name' => 'Supplier Test SPH',
        'code' => 'SUP-SPH',
    ]);

    SupplierPurchaseHistory::create([
        'id' => 'SPH-FIN-01',
        'supplier_id' => $supplier->id,
        'purchase_date' => '2026-07-01',
        'invoice_number' => 'INV-SUP-01',
        'item_name' => 'Raw Materials',
        'quantity' => 1,
        'unit_price' => 75000,
        'total_amount' => 75000,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($superAdmin)
        ->get(route('finance.index'));

    $response->assertOk();
    $response->assertViewHas('totalAR', 100000.00);
    $response->assertViewHas('totalAP', 75000.00);
});
