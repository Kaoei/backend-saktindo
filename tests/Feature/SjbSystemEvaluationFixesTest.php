<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Master_customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SjbSystemEvaluationFixesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Master_customer $customer;
    protected Supplier $supplier;
    protected SupplierProduct $supplierProduct;
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
            'id' => 'CUST-EVAL-01',
            'nama_customer' => 'PT Mandiri Jaya',
            'nama_pic' => 'Budi',
            'nomor_hp' => '081234567890',
            'email' => 'mandiri@cust.com',
            'alamat' => 'Jl. Merdeka No. 10',
            'kota' => 'Jakarta',
        ]);

        $this->supplier = Supplier::create([
            'id' => 'SUP-EVAL-01',
            'supplier_name' => 'PT Baja Metalindo',
            'name' => 'PT Baja Metalindo',
            'phone' => '08123456789',
            'address' => 'Jl. Industri Baja',
            'status' => 'active',
        ]);

        $this->supplierProduct = SupplierProduct::create([
            'id' => 'SP-EVAL-01',
            'supplier_id' => $this->supplier->id,
            'sku' => 'SKU-BAUT-01',
            'item_name' => 'Baut Baja Ringan',
            'last_purchase_price' => 10000,
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'product_id' => 'PROD-EVAL-01',
            'product_name' => 'Baut Baja Ringan',
            'seller_sku' => 'SKU-BAUT-01',
            'variation_value' => '5cm Galvalum',
            'brand' => 'BajaBrand',
            'category' => 'Hardware',
            'sub_category' => 'Baut',
            'sku_id' => 'SKU-BAUT-01',
            'price' => 15000,
            'quantity' => 10,
        ]);

        $this->order = SalesOrder::create([
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->nama_customer,
            'customer_po_number' => 'PO-CUST-999',
            'order_date' => now()->toDateString(),
            'po_date' => now()->toDateString(),
            'sales_type' => 'credit',
            'order_status' => 'confirmed',
            'stock_status' => 'pending',
            'subtotal' => 30000,
            'tax_amount' => 0,
            'grand_total' => 30000,
        ]);

        SalesOrderItem::create([
            'sales_order_id' => $this->order->id,
            'product_name' => 'Baut Baja Ringan',
            'quantity' => 2,
            'unit' => 'pcs',
            'unit_price' => 15000,
            'line_total' => 30000,
        ]);

        $this->invoice = Invoice::create([
            'sales_order_id' => $this->order->id,
            'invoice_number' => 'INV-TEST-001',
            'invoice_type' => 'standar',
            'tax_type' => 'sjb_pajak',
            'faktur_number' => '010.001-26.00000001',
            'invoice_date' => now()->toDateString(),
            'status' => 'unpaid',
            'subtotal' => 30000,
            'tax_amount' => 0,
            'grand_total' => 30000,
            'paid_amount' => 0,
            'outstanding_amount' => 30000,
        ]);

        $this->order->update(['invoice_id' => $this->invoice->id]);
    }

    public function test_invoice_dotmatrix_pdf_and_html_print_modes(): void
    {
        // 1. Direct browser dotmatrix print (action=print_html)
        $htmlResponse = $this->actingAs($this->user)
            ->get(route('sales-finance.invoices.pdf', [$this->invoice, 'action' => 'print_html']));

        $htmlResponse->assertStatus(200);
        $htmlResponse->assertSee('window.print()');
        $htmlResponse->assertSee('8.5in 5.5in'); // continuous form half letter
        $htmlResponse->assertSee('Baut Baja Ringan');

        // 2. Normal DomPDF generation
        $pdfResponse = $this->actingAs($this->user)
            ->get(route('sales-finance.invoices.pdf', $this->invoice));

        $pdfResponse->assertStatus(200);
        $this->assertStringContainsString('application/pdf', (string) $pdfResponse->headers->get('Content-Type'));
    }

    public function test_stock_check_and_approve_allows_order_fulfillment(): void
    {
        // Buat Order baru yang belum di-invoice
        $freshOrder = SalesOrder::create([
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->nama_customer,
            'customer_po_number' => 'PO-CUST-FRESH',
            'order_date' => now()->toDateString(),
            'po_date' => now()->toDateString(),
            'sales_type' => 'credit',
            'order_status' => 'confirmed',
            'stock_status' => 'pending',
            'subtotal' => 30000,
            'tax_amount' => 0,
            'grand_total' => 30000,
        ]);

        SalesOrderItem::create([
            'sales_order_id' => $freshOrder->id,
            'product_name' => 'Baut Baja Ringan',
            'quantity' => 2,
            'unit' => 'pcs',
            'unit_price' => 15000,
            'line_total' => 30000,
        ]);

        // Cek stok otomatis dari ketersediaan produk
        $response = $this->actingAs($this->user)
            ->post(route('sales-finance.stock-check', $freshOrder));

        $response->assertRedirect();
        $freshOrder->refresh();
        $this->assertEquals('available', $freshOrder->stock_status);

        // Uji tombol manual approval stok
        $freshOrder->update(['stock_status' => 'pending']);
        $approveResponse = $this->actingAs($this->user)
            ->post(route('sales-finance.stock-approve', $freshOrder));

        $approveResponse->assertRedirect();
        $freshOrder->refresh();
        $this->assertEquals('available', $freshOrder->stock_status);

        // Verifikasi log activity tercatat
        $latestActivity = ActivityLog::where('action', 'manual_approve_stock')
            ->orWhere('description', 'manual_approve_stock')
            ->first();
        $this->assertNotNull($latestActivity);
    }

    public function test_faktur_pajak_revision_and_audit_trail_logging(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('sales-finance.invoices.update-faktur', $this->invoice), [
                'faktur_number' => '010.001-26.00000099',
                'revision_reason' => 'Koreksi NPWP dan salah penomoran dari DJP',
            ]);

        $response->assertRedirect();
        $this->invoice->refresh();
        $this->assertEquals('010.001-26.00000099', $this->invoice->faktur_number);

        // Periksa activity log
        $activity = ActivityLog::where('action', 'update_faktur_pajak')
            ->orWhere('description', 'update_faktur_pajak')
            ->first();
        $this->assertNotNull($activity);
        $this->assertEquals('010.001-26.00000001', $activity->properties['old_faktur_number']);
        $this->assertEquals('010.001-26.00000099', $activity->properties['new_faktur_number']);
        $this->assertEquals('Koreksi NPWP dan salah penomoran dari DJP', $activity->properties['revision_reason']);
    }

    public function test_product_options_include_variation_label(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('sales-finance.create'));

        $response->assertStatus(200);
        $response->assertSee('[Variasi: 5cm Galvalum]');
    }

    public function test_supplier_po_ui_has_non_breaking_qty_stepper(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('supplier-po.create'));

        $response->assertStatus(200);
        $response->assertSee('qty-stepper-group');
        $response->assertSee('[Variasi: 5cm Galvalum]');
    }
}
