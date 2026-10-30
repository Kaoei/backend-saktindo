<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Master_customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTransferJsSjbTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Master_customer $customer;
    protected Product $product;
    protected SalesOrder $order;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->customer = Master_customer::create([
            'id' => 'CUST-TRF-01',
            'nama_customer' => 'PT Pelita Bangsa',
            'nomor_hp' => '08123456789',
            'email' => 'pelita@cust.com',
            'alamat' => 'Jl. Kebon Jeruk No. 5',
            'kota' => 'Jakarta Barat',
        ]);

        $this->product = Product::create([
            'product_id' => 'PRD-TRF-01',
            'product_name' => 'Kabel Listrik NYA 1.5mm',
            'seller_sku' => 'SKU-KBL-01',
            'variation_value' => 'Merah 50M',
            'brand' => 'Extrana',
            'category' => 'Kabel',
            'sku_id' => 'SKU-KBL-01',
            'price' => 100000,
            'quantity' => 20,
        ]);

        $this->order = SalesOrder::create([
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->nama_customer,
            'customer_po_number' => 'PO-CUST-TRF-100',
            'order_date' => now()->toDateString(),
            'po_date' => now()->toDateString(),
            'sales_type' => 'js',
            'toko' => 'js',
            'order_status' => 'confirmed',
            'stock_status' => 'available',
            'subtotal' => 100000,
            'tax_amount' => 0,
            'grand_total' => 100000,
        ]);

        SalesOrderItem::create([
            'sales_order_id' => $this->order->id,
            'product_name' => 'Kabel Listrik NYA 1.5mm',
            'quantity' => 1,
            'unit' => 'rol',
            'unit_price' => 100000,
            'line_total' => 100000,
        ]);

        $this->invoice = Invoice::create([
            'sales_order_id' => $this->order->id,
            'invoice_number' => 'INV-JS-202610-001',
            'invoice_type' => 'normal',
            'tax_type' => 'js',
            'faktur_number' => 'FKT-JS-001',
            'invoice_date' => now()->toDateString(),
            'status' => 'outstanding',
            'subtotal' => 100000,
            'tax_amount' => 0,
            'grand_total' => 100000,
            'paid_amount' => 0,
            'outstanding_amount' => 100000,
        ]);

        $this->order->update(['invoice_id' => $this->invoice->id]);
    }

    public function test_transfer_transaction_from_js_to_sjb_pajak_creates_new_invoice_and_records_history(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('sales-finance.transfer-transaction', $this->order), [
                'target_type' => 'sjb_pajak',
                'reason' => 'Permintaan pembeli minta faktur pajak resmi PT SJB',
                'faktur_number' => '010.001-26.99999999',
                'invoice_date' => now()->toDateString(),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->order->refresh();
        $this->invoice->refresh();

        // 1. Verifikasi Invoice Lama telah dialihkan
        $this->assertEquals('cancelled', $this->invoice->status);
        $this->assertNotNull($this->invoice->transferred_to_invoice_id);
        $this->assertNotNull($this->invoice->transferred_to_invoice_number);
        $this->assertEquals('Permintaan pembeli minta faktur pajak resmi PT SJB', $this->invoice->transfer_reason);

        // 2. Verifikasi Invoice Baru berhasil dibuat sesuai entitas tujuan
        $newInvoice = Invoice::find($this->order->invoice_id);
        $this->assertNotNull($newInvoice);
        $this->assertNotEquals($this->invoice->id, $newInvoice->id);
        $this->assertEquals('sjb_pajak', $newInvoice->tax_type);
        $this->assertEquals('010.001-26.99999999', $newInvoice->faktur_number);
        $this->assertEquals(100000, (float) $newInvoice->subtotal);
        $this->assertEquals(11000, (float) $newInvoice->tax_amount); // 11% PPN SJB
        $this->assertEquals(111000, (float) $newInvoice->grand_total);
        $this->assertEquals($this->invoice->id, $newInvoice->transferred_from_invoice_id);
        $this->assertEquals($this->invoice->invoice_number, $newInvoice->transferred_from_invoice_number);
        $this->assertEquals('Permintaan pembeli minta faktur pajak resmi PT SJB', $newInvoice->transfer_reason);

        // 3. Verifikasi Sales Order terupdate
        $this->assertEquals('sjb', $this->order->sales_type);
        $this->assertEquals('sjb', $this->order->toko);
        $this->assertEquals(111000, (float) $this->order->grand_total);

        // 4. Verifikasi ActivityLog audit trail tercatat
        $transferInLog = ActivityLog::where('action', 'transfer_transaction_in')
            ->where('subject_id', $newInvoice->id)
            ->first();
        $this->assertNotNull($transferInLog);
        $this->assertEquals($this->invoice->invoice_number, $transferInLog->properties['from_invoice_number']);
        $this->assertEquals($newInvoice->invoice_number, $transferInLog->properties['to_invoice_number']);
        $this->assertEquals('js', $transferInLog->properties['from_tax_type']);
        $this->assertEquals('sjb_pajak', $transferInLog->properties['to_tax_type']);

        // 5. Verifikasi tampilan UI show merender informasi asal pemindahan
        $showResponse = $this->actingAs($this->user)
            ->get(route('sales-finance.show', $this->order));

        $showResponse->assertStatus(200);
        $showResponse->assertSee('Transaksi Hasil Pemindahan');
        $showResponse->assertSee($this->invoice->invoice_number);
        $showResponse->assertSee('Permintaan pembeli minta faktur pajak resmi PT SJB');
        $showResponse->assertSee('Riwayat Pemindahan Transaksi (JS ⇄ SJB)');
    }

    public function test_transfer_transaction_from_sjb_to_js_removes_tax_and_logs_origin(): void
    {
        // Ubah invoice awal menjadi SJB Pajak
        $this->invoice->update([
            'tax_type' => 'sjb_pajak',
            'tax_amount' => 11000,
            'grand_total' => 111000,
            'outstanding_amount' => 111000,
        ]);
        $this->order->update([
            'sales_type' => 'sjb',
            'toko' => 'sjb',
            'tax_amount' => 11000,
            'grand_total' => 111000,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('sales-finance.transfer-transaction', $this->order), [
                'target_type' => 'js',
                'reason' => 'Pengalihan transaksi non-pajak ke entitas JS',
            ]);

        $response->assertRedirect();
        $this->order->refresh();

        $newInvoice = Invoice::find($this->order->invoice_id);
        $this->assertNotNull($newInvoice);
        $this->assertEquals('js', $newInvoice->tax_type);
        $this->assertEquals(0, (float) $newInvoice->tax_amount);
        $this->assertEquals(100000, (float) $newInvoice->grand_total);
        $this->assertEquals($this->invoice->invoice_number, $newInvoice->transferred_from_invoice_number);
    }

    public function test_transfer_transaction_rejected_if_target_same_as_current(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('sales-finance.transfer-transaction', $this->order), [
                'target_type' => 'js', // Tipe awal sudah JS
                'reason' => 'Mencoba transfer ke tipe yang sama',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_transfer_transaction_validation_requires_reason(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('sales-finance.transfer-transaction', $this->order), [
                'target_type' => 'sjb_pajak',
                'reason' => '', // Kosong
            ]);

        $response->assertSessionHasErrors('reason');
    }
}

