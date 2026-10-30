<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Master_customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTerbilangTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Master_customer $customer;
    protected SalesOrder $order;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->customer = Master_customer::create([
            'id' => 'CUST-TB-01',
            'nama_customer' => 'PT Terbilang Makmur',
            'nomor_hp' => '081234567890',
            'email' => 'terbilang@cust.com',
            'alamat' => 'Jl. Uang No. 150',
            'kota' => 'Jakarta',
        ]);

        $this->order = SalesOrder::create([
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->nama_customer,
            'customer_po_number' => 'PO-150K',
            'order_date' => now()->toDateString(),
            'po_date' => now()->toDateString(),
            'sales_type' => 'credit',
            'order_status' => 'confirmed',
            'stock_status' => 'available',
            'subtotal' => 150000,
            'tax_amount' => 0,
            'grand_total' => 150000,
        ]);

        SalesOrderItem::create([
            'sales_order_id' => $this->order->id,
            'product_name' => 'Barang Uji 150 Ribu',
            'quantity' => 1,
            'unit' => 'pcs',
            'unit_price' => 150000,
            'line_total' => 150000,
        ]);

        $this->invoice = Invoice::create([
            'sales_order_id' => $this->order->id,
            'invoice_number' => 'INV-150K-001',
            'invoice_type' => 'standar',
            'tax_type' => 'sjb_non_pajak',
            'faktur_number' => 'FKT-150K-001',
            'invoice_date' => now()->toDateString(),
            'status' => 'unpaid',
            'subtotal' => 150000,
            'tax_amount' => 0,
            'grand_total' => 150000,
            'paid_amount' => 0,
            'outstanding_amount' => 150000,
        ]);

        $this->order->update(['invoice_id' => $this->invoice->id]);
    }

    public function test_helper_terbilang_rupiah_accurate(): void
    {
        $this->assertEquals('Seratus Lima Puluh Ribu Rupiah', terbilangRupiah(150000));
        $this->assertEquals('Seratus Lima Puluh Ribu Rupiah', terbilangRupiah(150000.00));
        $this->assertEquals('Seratus Lima Puluh Ribu Rupiah', terbilangRupiah('150000.00'));
        $this->assertEquals('Nol Rupiah', terbilangRupiah(0));
        $this->assertEquals('Satu Juta Lima Ratus Ribu Rupiah', terbilangRupiah(1500000));
    }

    public function test_helper_parse_flexible_money(): void
    {
        $this->assertEquals(150000.0, parseFlexibleMoney('150000.00'));
        $this->assertEquals(150000.0, parseFlexibleMoney('150.000'));
        $this->assertEquals(150000.0, parseFlexibleMoney('150.000,00'));
        $this->assertEquals(150000.0, parseFlexibleMoney('150,000.00'));
        $this->assertEquals(150000.0, parseFlexibleMoney(150000));
    }

    public function test_invoice_print_html_displays_seratus_lima_puluh_ribu_rupiah(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('sales-finance.invoices.pdf', [$this->invoice, 'action' => 'print_html']));

        $response->assertStatus(200);
        $response->assertSee('Terbilang :');
        $response->assertSee('Seratus Lima Puluh Ribu Rupiah');
    }

    public function test_invoice_custom_form_post_displays_correct_terbilang(): void
    {
        // Simulasi submit dari modal Cetak / Sesuaikan Faktur dengan nilai grand_total 150000.00
        $response = $this->actingAs($this->user)
            ->post(route('sales-finance.invoices.pdf', $this->invoice), [
                'action' => 'print_html',
                'subtotal' => '150000.00',
                'tax_amount' => '0.00',
                'grand_total' => '150000.00',
                'terbilang' => '', // kosongkan agar sistem memicu terbilang otomatis
            ]);

        $response->assertStatus(200);
        $response->assertSee('Seratus Lima Puluh Ribu Rupiah');
    }
}

