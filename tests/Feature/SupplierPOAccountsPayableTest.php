<?php

use App\Models\Supplier;
use App\Models\SupplierPO;
use App\Models\SupplierPOItem;
use App\Models\SupplierProduct;
use App\Models\SupplierPoPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can view supplier POs in accounts payable list and see bayar tagihan button', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $supplier = Supplier::create([
        'id' => 'SUP-TEST-001',
        'name' => 'PT Sumber Cahaya',
        'payment_due_days' => 14,
    ]);

    $product = SupplierProduct::create([
        'id' => 'SPR-TEST-001',
        'supplier_id' => $supplier->id,
        'sku' => 'SKU-001',
        'item_name' => 'Lampu LED Hannochs 10W',
    ]);

    $po = SupplierPO::create([
        'id' => 'SPO-TEST-001',
        'supplier_id' => $supplier->id,
        'po_number' => 'PO/2026/11/001',
        'reference_number' => 'INV-SUP-9988',
        'order_date' => now()->toDateString(),
        'subtotal' => 1000000,
        'total_amount' => 1000000,
        'paid_amount' => 0,
        'outstanding_amount' => 1000000,
        'payment_status' => 'unpaid',
        'status' => 'received',
    ]);

    SupplierPOItem::create([
        'supplier_po_id' => $po->id,
        'supplier_product_id' => $product->id,
        'qty' => 10,
        'price' => 100000,
        'discount' => 0,
    ]);

    $response = $this->actingAs($user)->get(route('finance.ap'));

    $response->assertOk();
    $response->assertSee('INV-SUP-9988');
    $response->assertSee('PT Sumber Cahaya');
    $response->assertSee('Lampu LED Hannochs 10W');
    $response->assertSee('Bayar Tagihan');
});

test('user can pay supplier PO in full and status becomes paid', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $supplier = Supplier::create([
        'id' => 'SUP-TEST-002',
        'name' => 'PT Mitra Sejahtera',
    ]);

    $po = SupplierPO::create([
        'id' => 'SPO-TEST-002',
        'supplier_id' => $supplier->id,
        'po_number' => 'PO/2026/11/002',
        'reference_number' => 'INV-MS-001',
        'order_date' => now()->toDateString(),
        'subtotal' => 2500000,
        'total_amount' => 2500000,
        'paid_amount' => 0,
        'outstanding_amount' => 2500000,
        'payment_status' => 'unpaid',
        'status' => 'received',
    ]);

    $response = $this->actingAs($user)->post(route('finance.payment.store'), [
        'type' => 'ap',
        'id' => $po->id,
        'amount' => 2500000,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'transfer_bank',
        'bank_name' => 'BCA Operasional',
        'reference_number' => 'TRF-001-2026',
        'notes' => 'Pelunasan penuh PO supplier',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    $po->refresh();
    expect((float) $po->paid_amount)->toBe(2500000.0)
        ->and((float) $po->outstanding_amount)->toBe(0.0)
        ->and($po->payment_status)->toBe('paid');

    $this->assertDatabaseHas('supplier_po_payments', [
        'supplier_po_id' => $po->id,
        'amount' => 2500000,
        'payment_method' => 'transfer_bank',
        'bank_name' => 'BCA Operasional',
    ]);
});

test('user can pay supplier PO in installments (partial payment)', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $supplier = Supplier::create([
        'id' => 'SUP-TEST-003',
        'name' => 'PT Zenata Jaya',
    ]);

    $po = SupplierPO::create([
        'id' => 'SPO-TEST-003',
        'supplier_id' => $supplier->id,
        'po_number' => 'PO/2026/11/003',
        'order_date' => now()->toDateString(),
        'subtotal' => 5000000,
        'total_amount' => 5000000,
        'paid_amount' => 0,
        'outstanding_amount' => 5000000,
        'payment_status' => 'unpaid',
        'status' => 'received',
    ]);

    // First installment: 2.000.000
    $this->actingAs($user)->post(route('finance.payment.store'), [
        'type' => 'ap',
        'id' => $po->id,
        'amount' => 2000000,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'transfer',
        'bank_name' => 'Mandiri',
    ]);

    $po->refresh();
    expect((float) $po->paid_amount)->toBe(2000000.0)
        ->and((float) $po->outstanding_amount)->toBe(3000000.0)
        ->and($po->payment_status)->toBe('partial');

    // Second installment: 3.000.000 (settles remainder)
    $this->actingAs($user)->post(route('finance.payment.store'), [
        'type' => 'ap',
        'id' => $po->id,
        'amount' => 3000000,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
    ]);

    $po->refresh();
    expect((float) $po->paid_amount)->toBe(5000000.0)
        ->and((float) $po->outstanding_amount)->toBe(0.0)
        ->and($po->payment_status)->toBe('paid');
});

test('payment rejects overpayment for supplier PO', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $supplier = Supplier::create([
        'id' => 'SUP-TEST-004',
        'name' => 'PT Overpay Supplier',
    ]);

    $po = SupplierPO::create([
        'id' => 'SPO-TEST-004',
        'supplier_id' => $supplier->id,
        'po_number' => 'PO/2026/11/004',
        'order_date' => now()->toDateString(),
        'subtotal' => 1000000,
        'total_amount' => 1000000,
        'paid_amount' => 0,
        'outstanding_amount' => 1000000,
        'payment_status' => 'unpaid',
        'status' => 'received',
    ]);

    $response = $this->actingAs($user)->post(route('finance.payment.store'), [
        'type' => 'ap',
        'id' => $po->id,
        'amount' => 1500000, // Exceeds 1.000.000
        'payment_date' => now()->toDateString(),
        'payment_method' => 'transfer',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');

    $po->refresh();
    expect((float) $po->paid_amount)->toBe(0.0)
        ->and($po->payment_status)->toBe('unpaid');
});

test('user can view detail hutang supplier show page', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $supplier = Supplier::create([
        'id' => 'SUP-TEST-005',
        'name' => 'PT Detail Supplier',
    ]);

    $po = SupplierPO::create([
        'id' => 'SPO-TEST-005',
        'supplier_id' => $supplier->id,
        'po_number' => 'PO/2026/11/005',
        'reference_number' => 'REF-DETAIL-001',
        'order_date' => now()->toDateString(),
        'subtotal' => 800000,
        'total_amount' => 800000,
        'paid_amount' => 0,
        'outstanding_amount' => 800000,
        'payment_status' => 'unpaid',
        'status' => 'received',
    ]);

    $response = $this->actingAs($user)->get(route('finance.ap.show', $po->id));

    $response->assertOk();
    $response->assertSee('PO/2026/11/005');
    $response->assertSee('REF-DETAIL-001');
    $response->assertSee('PT Detail Supplier');
    $response->assertSee('Ringkasan Hutang');
});

test('finance overview dashboard includes supplier PO in totalAP calculation', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $supplier = Supplier::create([
        'id' => 'SUP-TEST-006',
        'name' => 'PT Overview AP',
    ]);

    SupplierPO::create([
        'id' => 'SPO-TEST-006',
        'supplier_id' => $supplier->id,
        'po_number' => 'PO/2026/11/006',
        'order_date' => now()->toDateString(),
        'subtotal' => 3500000,
        'total_amount' => 3500000,
        'paid_amount' => 500000,
        'outstanding_amount' => 3000000,
        'payment_status' => 'partial',
        'status' => 'received',
    ]);

    $response = $this->actingAs($user)->get(route('finance.index'));

    $response->assertOk();
    $response->assertViewHas('totalAP', 3000000.0);
});

