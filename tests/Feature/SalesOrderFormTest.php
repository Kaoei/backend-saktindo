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

test('user can submit sales order with multiple products in a single order', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $supplier = Supplier::create([
        'name' => 'PT Multi Supplier',
        'status' => 'active',
    ]);

    $product1 = SupplierProduct::create([
        'supplier_id' => $supplier->id,
        'item_name' => 'Lampu LED 10W',
        'sku' => 'SKU-MP-001',
        'unit' => 'pcs',
        'last_purchase_price' => 50000,
        'status' => 'active',
    ]);

    $product2 = SupplierProduct::create([
        'supplier_id' => $supplier->id,
        'item_name' => 'Saklar Engkel',
        'sku' => 'SKU-MP-002',
        'unit' => 'pcs',
        'last_purchase_price' => 25000,
        'status' => 'active',
    ]);

    $product3 = SupplierProduct::create([
        'supplier_id' => $supplier->id,
        'item_name' => 'Stop Kontak 4 Lubang',
        'sku' => 'SKU-MP-003',
        'unit' => 'pcs',
        'last_purchase_price' => 75000,
        'status' => 'active',
    ]);

    $poNumber = 'PO-MULTI-' . uniqid();
    $response = $this->actingAs($user)->post(route('sales-finance.store'), [
        'customer_name' => 'Customer Multi Produk',
        'customer_po_number' => $poNumber,
        'order_date' => now()->toDateString(),
        'sales_type' => 'js',
        'order_status' => 'draft',
        'items' => [
            [
                'product_code' => $product1->id,
                'product_name' => $product1->item_name,
                'unit' => 'pcs',
                'quantity' => 10,
                'unit_price' => 50000,
                'discount_1' => 0,
                'discount_2' => 0,
                'discount_3' => 0,
                'discount_4' => 0,
            ],
            [
                'product_code' => $product2->id,
                'product_name' => $product2->item_name,
                'unit' => 'pcs',
                'quantity' => 20,
                'unit_price' => 25000,
                'discount_1' => 10,
                'discount_2' => 0,
                'discount_3' => 0,
                'discount_4' => 0,
            ],
            [
                'product_code' => $product3->id,
                'product_name' => $product3->item_name,
                'unit' => 'pcs',
                'quantity' => 5,
                'unit_price' => 80000,
                'discount_1' => 5,
                'discount_2' => 5,
                'discount_3' => 0,
                'discount_4' => 0,
            ],
        ],
    ]);

    $response->assertRedirect();

    $so = SalesOrder::where('customer_po_number', $poNumber)->firstOrFail();
    $this->assertCount(3, $so->items);

    // Item 1: 10 * 50,000 = 500,000
    // Item 2: 20 * (25,000 * 0.9) = 450,000
    // Item 3: 5 * (80,000 * 0.95 * 0.95) = 361,000
    // Total Subtotal = 500,000 + 450,000 + 361,000 = 1,311,000
    $this->assertEquals(1311000, (float) $so->subtotal);

    $this->assertDatabaseHas('sales_order_items', ['sales_order_id' => $so->id, 'product_code' => $product1->id, 'quantity' => 10]);
    $this->assertDatabaseHas('sales_order_items', ['sales_order_id' => $so->id, 'product_code' => $product2->id, 'quantity' => 20]);
    $this->assertDatabaseHas('sales_order_items', ['sales_order_id' => $so->id, 'product_code' => $product3->id, 'quantity' => 5]);
});

test('user can submit pre-order with eta and dp amount', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $supplier = Supplier::create([
        'name' => 'PT Indent Supplier',
        'status' => 'active',
    ]);

    $product = SupplierProduct::create([
        'supplier_id' => $supplier->id,
        'item_name' => 'Mesin Pabrik Custom',
        'sku' => 'INDENT-001',
        'unit' => 'unit',
        'last_purchase_price' => 15000000,
        'status' => 'active',
    ]);

    $poNumber = 'PO-PREORDER-' . uniqid();
    $eta = now()->addDays(30)->toDateString();

    $response = $this->actingAs($user)->post(route('sales-finance.store'), [
        'customer_name' => 'PT Pelanggan PreOrder',
        'customer_po_number' => $poNumber,
        'order_date' => now()->toDateString(),
        'sales_type' => 'js',
        'order_status' => 'pending_stock',
        'is_pre_order' => '1',
        'pre_order_eta' => $eta,
        'dp_amount' => 5000000,
        'pre_order_notes' => 'Indent pabrik 30 hari',
        'items' => [
            [
                'product_code' => $product->id,
                'product_name' => $product->item_name,
                'unit' => 'unit',
                'quantity' => 1,
                'unit_price' => 20000000,
                'discount_1' => 0,
                'discount_2' => 0,
                'discount_3' => 0,
                'discount_4' => 0,
            ],
        ],
    ]);

    $response->assertRedirect();

    $so = SalesOrder::where('customer_po_number', $poNumber)->firstOrFail();
    $this->assertTrue($so->is_pre_order);
    $this->assertEquals($eta, $so->pre_order_eta->toDateString());
    $this->assertEquals(5000000, (float) $so->dp_amount);
    $this->assertEquals(0, (float) $so->dp_paid);
    $this->assertEquals('unpaid', $so->dp_status);
});

test('user can view pre-orders list page and record dp payment', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $so = SalesOrder::create([
        'id' => 'SO-PRE-001',
        'customer_name' => 'PT PreOrder Test',
        'customer_po_number' => 'PO-CUST-PRE-001',
        'order_date' => now(),
        'sales_type' => 'js',
        'order_status' => 'pending_stock',
        'is_pre_order' => true,
        'pre_order_eta' => now()->addDays(14),
        'dp_amount' => 2000000,
        'dp_paid' => 0,
        'dp_status' => 'unpaid',
        'subtotal' => 10000000,
        'grand_total' => 10000000,
    ]);

    $response = $this->actingAs($user)->get(route('sales-finance.pre-orders.index'));
    $response->assertOk();
    $response->assertSee('PT PreOrder Test');
    $response->assertSee('PO-CUST-PRE-001');

    // Record DP Payment
    $payResponse = $this->actingAs($user)->post(route('sales-finance.dp-payment.store', $so), [
        'dp_paid_amount' => 2000000,
        'payment_date' => now()->toDateString(),
        'notes' => 'Transfer DP BCA Lunas',
    ]);

    $payResponse->assertRedirect();

    $so->refresh();
    $this->assertEquals(2000000, (float) $so->dp_paid);
    $this->assertEquals('paid', $so->dp_status);
});

test('user can generate and print proforma invoice for pre-order', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $so = SalesOrder::create([
        'id' => 'SO-PRE-002',
        'customer_name' => 'PT Customer Proforma',
        'customer_po_number' => 'PO-PI-999',
        'order_date' => now(),
        'sales_type' => 'js',
        'order_status' => 'pending_stock',
        'is_pre_order' => true,
        'subtotal' => 5000000,
        'grand_total' => 5000000,
    ]);

    $genResponse = $this->actingAs($user)->post(route('sales-finance.proforma.generate', $so));
    $genResponse->assertRedirect();

    $this->assertDatabaseHas('proforma_invoices', [
        'sales_order_id' => $so->id,
        'grand_total' => 5000000,
    ]);

    $printResponse = $this->actingAs($user)->get(route('sales-finance.proforma.print', $so));
    $printResponse->assertOk();
    $printResponse->assertSee('PROFORMA INVOICE');
    $printResponse->assertSee('PT Customer Proforma');
});

