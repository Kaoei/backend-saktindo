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
        $this->assertStringContainsString('Barang-barang yang telah dibeli tidak dapat dikembalikan', $view);
        $this->assertStringContainsString('Pembayaran dengan cek/giro belum berarti lunas sebelum diuangkan', $view);
        $this->assertStringContainsString('PT. SAKTINDO JAYA BERSAMA', $view);
        $this->assertStringContainsString('068.3055678', $view);

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

    public function test_faktur_pdf_custom_post_submission(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV/2026/09/0002',
            'invoice_type' => 'standar',
            'tax_type' => 'sjb_non_pajak',
            'faktur_number' => 'FKT-2026-0002',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'unpaid',
            'subtotal' => 500000,
            'tax_amount' => 0,
            'grand_total' => 500000,
            'paid_amount' => 0,
            'outstanding_amount' => 500000,
        ]);

        $customPayload = [
            'action' => 'stream',
            'invoice_number' => 'C-26090105',
            'po_number' => 'C-26090105',
            'notes' => 'Tk. 238',
            'customer_name' => 'PT. VITECH KARYA SOLUTIONS',
            'customer_address' => 'GRIYA ASRI BLOK C2 NO. 43 RT.011 RW.032, SUMBERJAYA',
            'items' => [
                [
                    'item_name' => 'FLEX. STEEL 1/2" @ 50M',
                    'qty' => 1,
                    'unit' => 'ROL',
                    'unit_price' => 370000,
                    'discount1' => 0,
                    'discount2' => 0,
                    'subtotal' => 370000,
                ],
            ],
            'subtotal' => 370000,
            'tax_amount' => 0,
            'grand_total' => 370000,
            'terbilang' => 'Tiga Ratus Tujuh Puluh Ribu',
            'signer_name' => 'FENIKI',
            'signer_title' => 'DIREKTUR',
        ];

        // 1. Test POST with action=stream
        $responseStream = $this->actingAs($user)->post(route('sales-finance.invoices.pdf', $invoice), $customPayload);
        $responseStream->assertStatus(200);
        $this->assertEquals('application/pdf', $responseStream->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $responseStream->headers->get('Content-Disposition'));

        // 2. Test POST with action=download
        $customPayload['action'] = 'download';
        $responseDownload = $this->actingAs($user)->post(route('sales-finance.invoices.pdf', $invoice), $customPayload);
        $responseDownload->assertStatus(200);
        $this->assertEquals('application/pdf', $responseDownload->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $responseDownload->headers->get('Content-Disposition'));

        // 3. Test POST with action=print_html (Direct Browser Print for Epson LX-310)
        $customPayload['action'] = 'print_html';
        $customPayload['paper_size'] = 'continuous_half';
        $customPayload['font_mode'] = 'dotmatrix';
        $responseHtml = $this->actingAs($user)->post(route('sales-finance.invoices.pdf', $invoice), $customPayload);
        $responseHtml->assertStatus(200);
        $responseHtml->assertSee('Mode Cetak Epson LX-310');
        $responseHtml->assertSee('size: 8.5in 5.5in', false);
        $responseHtml->assertSee('Courier New', false);
        $responseHtml->assertSee('window.print()', false);
    }

    public function test_faktur_and_delivery_note_consolidate_identical_items_from_different_racks(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $salesOrder = SalesOrder::create([
            'customer_name' => 'CARLOS',
            'customer_po_number' => 'PO-20261103-4E6EB',
            'po_date' => now()->toDateString(),
            'order_date' => now()->toDateString(),
            'sales_type' => 'credit',
            'order_status' => 'confirmed',
            'subtotal' => 800000,
            'tax_amount' => 0,
            'grand_total' => 800000,
        ]);

        // Item 1: Rak L2 R1-1 (10 pcs)
        $soItem1 = SalesOrderItem::create([
            'sales_order_id' => $salesOrder->id,
            'product_name' => 'VARIO - CAPSULE',
            'product_code' => 'SPR-202608-0092',
            'rack_id' => 'L2 R1-1',
            'quantity' => 10,
            'unit' => 'pcs',
            'unit_price' => 50000,
            'line_total' => 500000,
        ]);

        // Item 2: Rak L2 SMP R15 (6 pcs)
        $soItem2 = SalesOrderItem::create([
            'sales_order_id' => $salesOrder->id,
            'product_name' => 'VARIO - CAPSULE',
            'product_code' => 'SPR-202608-0092',
            'rack_id' => 'L2 SMP R15',
            'quantity' => 6,
            'unit' => 'pcs',
            'unit_price' => 50000,
            'line_total' => 300000,
        ]);

        $invoice = Invoice::create([
            'sales_order_id' => $salesOrder->id,
            'invoice_number' => 'INV/2026/11/0007',
            'invoice_type' => 'standar',
            'tax_type' => 'sjb_non_pajak',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'unpaid',
            'subtotal' => 800000,
            'tax_amount' => 0,
            'grand_total' => 800000,
            'paid_amount' => 0,
            'outstanding_amount' => 800000,
        ]);
        $salesOrder->update(['invoice_id' => $invoice->id]);

        // 1. Verify Faktur PDF consolidates 10 + 6 into 1 row of 16 pcs
        $fakturView = view('sales-finance.pdf.invoice', ['invoice' => $invoice])->render();
        $this->assertStringContainsString('VARIO - CAPSULE', $fakturView);
        $this->assertStringContainsString('16,00 PCS', $fakturView);
        $this->assertStringContainsString('800.000,00', $fakturView);
        // It must appear as row No 1 only, and not have row No 2
        $this->assertStringContainsString('<td class="center">1</td>', $fakturView);
        $this->assertStringNotContainsString('<td class="center">2</td>', $fakturView);

        // 2. Create Delivery Note and items
        $deliveryNote = \App\Models\DeliveryNote::create([
            'invoice_id' => $invoice->id,
            'delivery_note_number' => 'SJ-202611-0007',
            'delivery_date' => now()->toDateString(),
            'status' => 'process',
        ]);
        \App\Models\DeliveryNoteItem::create([
            'delivery_note_id' => $deliveryNote->id,
            'sales_order_item_id' => $soItem1->id,
            'qty_sent' => 10,
        ]);
        \App\Models\DeliveryNoteItem::create([
            'delivery_note_id' => $deliveryNote->id,
            'sales_order_item_id' => $soItem2->id,
            'qty_sent' => 6,
        ]);

        // 3. Verify Surat Jalan PDF consolidates 10 + 6 into 1 row of 16 pcs
        $dnView = view('sales-finance.pdf.delivery-note', ['deliveryNote' => $deliveryNote])->render();
        $this->assertStringContainsString('VARIO - CAPSULE', $dnView);
        $this->assertStringContainsString('16,00', $dnView);
        $this->assertStringContainsString('1 baris', $dnView);
    }
}

