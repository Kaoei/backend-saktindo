<?php

use App\Mail\InvoiceReminderMail;
use App\Models\Invoice;
use App\Models\Master_customer;
use App\Models\Product;
use App\Models\RekeningBank;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    \App\Models\Role::firstOrCreate(['slug' => User::ROLE_SUPER_ADMIN], ['name' => 'Super Admin', 'permissions' => []]);
    \App\Models\Role::firstOrCreate(['slug' => User::ROLE_FINANCE], ['name' => 'Finance', 'permissions' => ['dashboard', 'sales_finance.view']]);
    \App\Models\Role::firstOrCreate(['slug' => User::ROLE_SALES], ['name' => 'Sales', 'permissions' => ['dashboard', 'sales_finance.view']]);

    // Create dummy supplier & supplier product for SO items
    $supplier = Supplier::create([
        'id' => 'SUP-TEST-001',
        'name' => 'PT Supplier Utama',
        'code' => 'SUP01',
        'category' => 'General',
        'status' => 'active',
    ]);

    $this->supplierProduct = SupplierProduct::create([
        'id' => 'SP-TEST-001',
        'supplier_id' => $supplier->id,
        'item_name' => 'Kipas Radiator',
        'sku' => 'KR-001',
        'part_number' => 'PN-KR-001',
        'unit' => 'pcs',
        'last_purchase_price' => 150000,
        'status' => 'active',
    ]);

    $this->user = User::factory()->create([
        'role' => User::ROLE_SUPER_ADMIN,
    ]);
});

test('client with less than 3 unpaid invoices can create new SO', function () {
    $customer = Master_customer::create([
        'id' => 'CUST-OK-001',
        'nama_customer' => 'PT Customer Lancar',
        'nama_pic' => 'Budi',
        'nomor_hp' => '081234567',
        'alamat' => 'Alamat Lancar',
        'kota' => 'Jakarta',
        'email' => 'lancar@customer.com',
    ]);

    // Create 2 unpaid invoices for this customer
    for ($i = 1; $i <= 2; $i++) {
        $so = SalesOrder::create([
            'id' => "SO-OK-00{$i}",
            'customer_id' => $customer->id,
            'customer_name' => $customer->nama_customer,
            'customer_po_number' => "PO-OK-00{$i}",
            'order_date' => now(),
            'sales_type' => 'js',
            'order_status' => 'invoiced',
            'subtotal' => 1000000,
            'grand_total' => 1000000,
        ]);

        $invoice = Invoice::create([
            'id' => "INV-OK-00{$i}",
            'sales_order_id' => $so->id,
            'invoice_number' => "INV/OK/00{$i}",
            'invoice_date' => now(),
            'due_date' => now()->addDays(7),
            'status' => 'outstanding',
            'subtotal' => 1000000,
            'grand_total' => 1000000,
            'paid_amount' => 0,
            'outstanding_amount' => 1000000,
        ]);
        $so->update(['invoice_id' => $invoice->id]);
    }

    expect($customer->unpaid_invoices_count)->toBe(2);
    expect($customer->is_blocked_for_so)->toBeFalse();

    // Attempt to create new SO
    $response = $this->actingAs($this->user)->post(route('sales-finance.store'), [
        'customer_id' => $customer->id,
        'customer_name' => $customer->nama_customer,
        'customer_po_number' => 'PO-NEW-003',
        'order_date' => now()->toDateString(),
        'sales_type' => 'js',
        'order_status' => 'draft',
        'items' => [
            [
                'product_code' => $this->supplierProduct->id,
                'product_name' => $this->supplierProduct->item_name,
                'unit' => 'pcs',
                'quantity' => 2,
                'unit_price' => 200000,
            ]
        ]
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('sales_orders', [
        'customer_id' => $customer->id,
        'customer_po_number' => 'PO-NEW-003',
    ]);
});

test('client with 3 or more unpaid invoices is blocked from creating new SO', function () {
    $customer = Master_customer::create([
        'id' => 'CUST-BLOCKED-001',
        'nama_customer' => 'PT Customer Macet',
        'nama_pic' => 'Andi',
        'nomor_hp' => '081234568',
        'alamat' => 'Alamat Macet',
        'kota' => 'Surabaya',
        'email' => 'macet@customer.com',
    ]);

    // Create 3 unpaid invoices for this customer
    for ($i = 1; $i <= 3; $i++) {
        $so = SalesOrder::create([
            'id' => "SO-MACET-00{$i}",
            'customer_id' => $customer->id,
            'customer_name' => $customer->nama_customer,
            'customer_po_number' => "PO-MACET-00{$i}",
            'order_date' => now(),
            'sales_type' => 'js',
            'order_status' => 'invoiced',
            'subtotal' => 1000000,
            'grand_total' => 1000000,
        ]);

        $invoice = Invoice::create([
            'id' => "INV-MACET-00{$i}",
            'sales_order_id' => $so->id,
            'invoice_number' => "INV/MACET/00{$i}",
            'invoice_date' => now(),
            'due_date' => now()->addDays(7),
            'status' => 'outstanding',
            'subtotal' => 1000000,
            'grand_total' => 1000000,
            'paid_amount' => 0,
            'outstanding_amount' => 1000000,
        ]);
        $so->update(['invoice_id' => $invoice->id]);
    }

    expect($customer->unpaid_invoices_count)->toBe(3);
    expect($customer->is_blocked_for_so)->toBeTrue();

    // Attempt to create new SO
    $response = $this->actingAs($this->user)->post(route('sales-finance.store'), [
        'customer_id' => $customer->id,
        'customer_name' => $customer->nama_customer,
        'customer_po_number' => 'PO-NEW-004',
        'order_date' => now()->toDateString(),
        'sales_type' => 'js',
        'order_status' => 'draft',
        'items' => [
            [
                'product_code' => $this->supplierProduct->id,
                'product_name' => $this->supplierProduct->item_name,
                'unit' => 'pcs',
                'quantity' => 1,
                'unit_price' => 100000,
            ]
        ]
    ]);

    $response->assertSessionHasErrors('customer_id');
    $this->assertDatabaseMissing('sales_orders', [
        'customer_po_number' => 'PO-NEW-004',
    ]);
});

test('dashboards finance, sales, and superadmin display unpaid clients information', function () {
    $customer = Master_customer::create([
        'id' => 'CUST-DASH-001',
        'nama_customer' => 'PT Menunggak Dashboard',
        'nama_pic' => 'Doni',
        'nomor_hp' => '0811223344',
        'alamat' => 'Alamat Dashboard',
        'kota' => 'Bandung',
        'email' => 'menunggak@dashboard.com',
    ]);

    $so = SalesOrder::create([
        'id' => 'SO-DASH-001',
        'customer_id' => $customer->id,
        'customer_name' => $customer->nama_customer,
        'customer_po_number' => 'PO-DASH-01',
        'order_date' => now(),
        'sales_type' => 'js',
        'order_status' => 'invoiced',
        'subtotal' => 2500000,
        'grand_total' => 2500000,
    ]);

    Invoice::create([
        'id' => 'INV-DASH-001',
        'sales_order_id' => $so->id,
        'invoice_number' => 'INV/DASH/001',
        'invoice_date' => now(),
        'due_date' => now()->addDays(5),
        'status' => 'outstanding',
        'subtotal' => 2500000,
        'grand_total' => 2500000,
        'paid_amount' => 0,
        'outstanding_amount' => 2500000,
    ]);

    // Test Finance Dashboard
    $financeUser = User::factory()->create(['role' => 'finance']);
    $resFinance = $this->actingAs($financeUser)->get(route('dashboard'));
    $resFinance->assertOk();
    $resFinance->assertSee('PT Menunggak Dashboard');
    $resFinance->assertSee('Informasi Client Belum Lunas / Belum Bayar');

    // Test Sales Dashboard
    $salesUser = User::factory()->create(['role' => 'sales']);
    $resSales = $this->actingAs($salesUser)->get(route('dashboard'));
    $resSales->assertOk();
    $resSales->assertSee('PT Menunggak Dashboard');
    $resSales->assertSee('Informasi Client Belum Lunas / Belum Bayar');

    // Test Super Admin Dashboard
    $resAdmin = $this->actingAs($this->user)->get(route('dashboard'));
    $resAdmin->assertOk();
    $resAdmin->assertSee('PT Menunggak Dashboard');
    $resAdmin->assertSee('Informasi Client Belum Lunas / Belum Bayar');
});

test('user can send invoice reminder email to customer', function () {
    Mail::fake();

    RekeningBank::create([
        'id' => 'REK-TEST-001',
        'bank_name' => 'BCA',
        'account_name' => 'PT Saktindo Jayatama Samudera',
        'account_number' => '1234567890',
        'toko' => 'js',
    ]);

    $customer = Master_customer::create([
        'id' => 'CUST-MAIL-001',
        'nama_customer' => 'PT Email Receiver',
        'nama_pic' => 'Eko',
        'nomor_hp' => '0812999888',
        'alamat' => 'Alamat Receiver',
        'kota' => 'Semarang',
        'email' => 'client@emailreceiver.com',
    ]);

    $so = SalesOrder::create([
        'id' => 'SO-MAIL-001',
        'customer_id' => $customer->id,
        'customer_name' => $customer->nama_customer,
        'customer_po_number' => 'PO-MAIL-01',
        'order_date' => now(),
        'sales_type' => 'js',
        'order_status' => 'invoiced',
        'subtotal' => 1500000,
        'grand_total' => 1500000,
    ]);

    $invoice = Invoice::create([
        'id' => 'INV-MAIL-001',
        'sales_order_id' => $so->id,
        'invoice_number' => 'INV/MAIL/001',
        'invoice_date' => now(),
        'due_date' => now()->addDays(3),
        'status' => 'outstanding',
        'subtotal' => 1500000,
        'grand_total' => 1500000,
        'paid_amount' => 0,
        'outstanding_amount' => 1500000,
    ]);

    $response = $this->actingAs($this->user)->post(route('finance.invoices.send-email', $invoice->id), [
        'email' => 'client@emailreceiver.com',
        'message' => 'Mohon segera transfer pelunasan.',
    ]);

    $response->assertSessionHas('status');

    Mail::assertSent(InvoiceReminderMail::class, function ($mail) use ($invoice) {
        return $mail->hasTo('client@emailreceiver.com') && $mail->invoice->id === $invoice->id;
    });

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'send_email',
        'subject_id' => $invoice->id,
    ]);
});
