<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Master_customer;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use App\Models\WebSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MOMFakturPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_faktur_pdf_renders_with_sidebar_logo_and_customer_po_number(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $customer = Master_customer::create([
            'id' => 'CUST-TEST-001',
            'nama_customer' => 'PT Pelanggan Sejahtera',
            'nama_pic' => 'Budi Santoso',
            'nomor_hp' => '081234567890',
            'email' => 'pelanggan@test.com',
            'alamat' => 'Jl. Merdeka No. 45',
            'kota' => 'Jakarta Pusat',
        ]);

        $salesOrder = SalesOrder::create([
            'customer_id' => $customer->id,
            'customer_name' => 'PT Pelanggan Sejahtera',
            'customer_po_number' => 'PO-CUST-2026-999',
            'po_date' => now()->toDateString(),
            'order_date' => now()->toDateString(),
            'sales_type' => 'credit',
            'order_status' => 'confirmed',
            'subtotal' => 370000,
            'tax_amount' => 0,
            'grand_total' => 370000,
        ]);

        SalesOrderItem::create([
            'sales_order_id' => $salesOrder->id,
            'product_name' => 'Lampu LED Downlight 12W',
            'product_code' => 'LED-DL-12W',
            'quantity' => 10,
            'unit' => 'pcs',
            'unit_price' => 37000,
            'line_total' => 370000,
        ]);

        $invoice = Invoice::create([
            'sales_order_id' => $salesOrder->id,
            'invoice_number' => 'INV/2026/09/0001',
            'invoice_type' => 'standar',
            'tax_type' => 'sjb_non_pajak',
            'faktur_number' => 'FKT-2026-0001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'unpaid',
            'subtotal' => 370000,
            'tax_amount' => 0,
            'grand_total' => 370000,
            'paid_amount' => 0,
            'outstanding_amount' => 370000,
        ]);

        $salesOrder->update(['invoice_id' => $invoice->id]);

        // Render the view directly to assert HTML contents
        $view = view('sales-finance.pdf.invoice', ['invoice' => $invoice])->render();

        // 1. Verify PO number appears in No Reff
        $this->assertStringContainsString('PO-CUST-2026-999', $view);
        $this->assertStringContainsString('No Reff :', $view);

        // 2. Verify Terbilang 370.000
        $this->assertStringContainsString('Tiga Ratus Tujuh Puluh Ribu Rupiah', $view);

        // 3. Verify Customer Name in Kepada Yth / cash-box
        $this->assertStringContainsString('PT Pelanggan Sejahtera', $view);

        // 4. Verify Perhatian points requested
        $this->assertStringContainsString('Barang barang yang telah dibeli tidak dapat dikembalikan', $view);
        $this->assertStringContainsString('Pembayaran dengan cek/giro belum berarti lunas sebelum diuangkan', $view);
        $this->assertStringContainsString('PT.SAKTINDO JAYA BERSAMA', $view);
        $this->assertStringContainsString('BCA KENARI, REK NO. 068.3055678', $view);

        // 5. Verify Tgl & Jam Cetak
        $this->assertStringContainsString('Tgl & Jam Cetak :', $view);
        $this->assertStringContainsString('WIB', $view);

        // 6. Verify Logo is present as base64 data URI
        $this->assertStringContainsString('data:image/', $view);
        $this->assertStringContainsString('base64,', $view);

        // 7. Verify the actual download endpoint returns 200 with PDF content type
        $response = $this->actingAs($user)->get(route('sales-finance.invoices.pdf', $invoice));
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }
}
