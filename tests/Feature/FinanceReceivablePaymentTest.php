<?php

use App\Models\Invoice;
use App\Models\Master_customer;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);


test('user can view receivables list and see pelunasan options', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $customer = Master_customer::create([
        'id' => 'CUST-TEST-001',
        'nama_customer' => 'PT Test Customer',
        'nama_pic' => 'Budi',
        'nomor_hp' => '081234567',
        'alamat' => 'Alamat Test',
        'kota' => 'Jakarta',
    ]);

    $so = SalesOrder::create([
        'id' => 'SO-TEST-001',
        'customer_id' => $customer->id,
        'customer_name' => $customer->nama_customer,
        'customer_po_number' => 'PO-TEST-99',
        'order_date' => now(),
        'sales_type' => 'js',
        'order_status' => 'invoiced',
        'subtotal' => 5000000,
        'grand_total' => 5000000,
    ]);

    $invoice = Invoice::create([
        'id' => 'INV-TEST-001',
        'invoice_number' => 'INV/TEST/2026/001',
        'invoice_date' => now(),
        'due_date' => now()->addDays(14),
        'subtotal' => 5000000,
        'grand_total' => 5000000,
        'paid_amount' => 0,
        'outstanding_amount' => 5000000,
        'status' => 'outstanding',
    ]);
    $invoice->salesOrders()->attach($so->id);

    $response = $this->actingAs($user)->get(route('finance.receivables'));

    $response->assertOk();
    $response->assertSee('INV/TEST/2026/001');
    $response->assertSee('Pelunasan');
});

test('user can record payment to settle an outstanding invoice', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $invoice = Invoice::create([
        'id' => 'INV-TEST-002',
        'invoice_number' => 'INV/TEST/2026/002',
        'invoice_date' => now(),
        'due_date' => now()->addDays(14),
        'subtotal' => 2000000,
        'grand_total' => 2000000,
        'paid_amount' => 0,
        'outstanding_amount' => 2000000,
        'status' => 'outstanding',
    ]);

    $response = $this->actingAs($user)->post(route('finance.payment.store', $invoice->id), [
        'type' => 'ar',
        'id' => $invoice->id,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'transfer_bank',
        'receiving_account' => 'js',
        'amount' => 2000000,
        'notes' => 'Pelunasan lunas via test',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    $invoice->refresh();
    $this->assertEquals(2000000, (float) $invoice->paid_amount);
    $this->assertEquals(0, (float) $invoice->outstanding_amount);
    $this->assertEquals('paid', $invoice->status);

    $this->assertDatabaseHas('invoice_payments', [
        'invoice_id' => $invoice->id,
        'amount' => 2000000,
        'method' => 'transfer_bank',
    ]);
});

test('user can record payment using giro with complete bank and bilyet giro details', function () {
    $user = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);

    $invoice = Invoice::create([
        'id' => 'INV-TEST-003',
        'invoice_number' => 'INV/TEST/2026/003',
        'invoice_date' => now(),
        'due_date' => now()->addDays(14),
        'subtotal' => 3500000,
        'grand_total' => 3500000,
        'paid_amount' => 0,
        'outstanding_amount' => 3500000,
        'status' => 'outstanding',
    ]);

    $response = $this->actingAs($user)->post(route('finance.payment.store', $invoice->id), [
        'type' => 'ar',
        'id' => $invoice->id,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'giro',
        'receiving_account' => 'js',
        'bank_name' => 'Bank Central Asia (BCA)',
        'giro_number' => 'BG-88991122',
        'giro_due_date' => now()->addDays(30)->toDateString(),
        'giro_status' => 'pending',
        'amount' => 3500000,
        'notes' => 'Pembayaran via Bilyet Giro BCA',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    $invoice->refresh();
    $this->assertEquals(3500000, (float) $invoice->paid_amount);
    $this->assertEquals(0, (float) $invoice->outstanding_amount);
    $this->assertEquals('paid', $invoice->status);

    $this->assertDatabaseHas('invoice_payments', [
        'invoice_id' => $invoice->id,
        'amount' => 3500000,
        'method' => 'giro',
        'bank_name' => 'Bank Central Asia (BCA)',
        'giro_number' => 'BG-88991122',
        'giro_status' => 'pending',
    ]);

    $showResponse = $this->actingAs($user)->get(route('finance.showPiutang', $invoice->id));
    $showResponse->assertOk();
    $showResponse->assertSee('Bank Central Asia (BCA)');
    $showResponse->assertSee('BG-88991122');
});


