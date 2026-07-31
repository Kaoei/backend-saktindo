<?php

use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);


test('user can access Input SO form and see product dropdown options', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $supplier = Supplier::create([
        'name' => 'PT Supplier Test',
        'status' => 'active',
    ]);

    $product = SupplierProduct::create([
        'supplier_id' => $supplier->id,
        'item_name' => 'Produk Test Dropdown SO',
        'sku' => 'SKU-SO-001',
        'unit' => 'pcs',
        'last_purchase_price' => 100000,
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)->get(route('sales-finance.create'));

    $response->assertOk();
    $response->assertSee('Form Input SO');
    $response->assertSee('Produk Test Dropdown SO');
});

test('user can submit sales order with 4-tier discounts and correct compounded total calculation', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $supplier = Supplier::create([
        'name' => 'PT Supplier Test 2',
        'status' => 'active',
    ]);

    $product = SupplierProduct::create([
        'supplier_id' => $supplier->id,
        'item_name' => 'Kabel Listrik 100M',
        'sku' => 'KBL-100',
        'unit' => 'roll',
        'last_purchase_price' => 200000,
        'status' => 'active',
    ]);

    // Price: 100,000 | Qty: 2
    // D1: 10%, D2: 5%, D3: 0%, D4: 0%
    // Net Unit Price = 100,000 * 0.9 * 0.95 = 85,500
    // Line Total = 2 * 85,500 = 171,000
    $poNumber = 'PO-TEST-' . uniqid();
    $response = $this->actingAs($user)->post(route('sales-finance.store'), [
        'customer_name' => 'Customer PT Abadi',
        'customer_po_number' => $poNumber,
        'order_date' => now()->toDateString(),
        'sales_type' => 'js',
        'order_status' => 'draft',
        'items' => [
            [
                'product_code' => $product->id,
                'product_name' => $product->item_name,
                'unit' => 'roll',
                'quantity' => 2,
                'unit_price' => 100000,
                'discount_1' => 10,
                'discount_2' => 5,
                'discount_3' => 0,
                'discount_4' => 0,
            ],
        ],
    ]);

    $response->assertRedirect();

    $so = SalesOrder::where('customer_po_number', $poNumber)->firstOrFail();
    $this->assertEquals(171000, (float) $so->subtotal);

    $this->assertDatabaseHas('sales_order_items', [
        'sales_order_id' => $so->id,
        'product_code' => $product->id,
        'quantity' => 2,
        'unit_price' => 100000,
        'discount_1' => 10,
        'discount_2' => 5,
        'line_total' => 171000,
    ]);
});
