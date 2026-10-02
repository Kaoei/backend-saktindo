<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Master_customer;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Supplier;
use App\Models\SupplierPO;
use App\Models\SupplierPOItem;
use App\Models\SupplierProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceComparisonTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(
            ['slug' => 'super_admin'],
            ['id' => 'ROL-SUPERADMIN', 'name' => 'Super Admin', 'permissions' => ['*']]
        );

        $this->admin = User::factory()->create([
            'role' => 'super_admin',
        ]);
    }

    public function test_sales_invoice_renders_properly()
    {
        $customer = Master_customer::create([
            'id' => 'CUST-COMP-01',
            'nama_customer' => 'PT Toko Pembeli Jaya',
            'nama_pic' => 'Budi Santoso',
            'tipe_customer' => 'perusahaan',
            'nomor_hp' => '0812345678',
            'alamat' => 'Jl. Kenari No. 12',
            'kota' => 'Jakarta Pusat',
        ]);

        $so = SalesOrder::create([
            'id' => 'SO-COMP-01',
            'customer_id' => $customer->id,
            'customer_name' => $customer->nama_customer,
            'customer_po_number' => 'PO-CUST-9988',
            'order_date' => '2026-10-02',
            'sales_type' => 'credit',
            'order_status' => 'confirmed',
            'subtotal' => 370000,
            'grand_total' => 370000,
        ]);

        SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_name' => 'Masker Medis 3 Ply',
            'unit' => 'box',
            'quantity' => 10,
            'unit_price' => 40000,
            'discount_1' => 5,
            'discount_2' => 2.5,
            'line_total' => 370500,
        ]);

        $invoice = Invoice::create([
            'id' => 'INV-COMP-01',
            'invoice_number' => 'INV-202610-001',
            'sales_order_id' => $so->id,
            'invoice_date' => '2026-10-02',
            'due_date' => '2026-11-01',
            'subtotal' => 370000,
            'grand_total' => 370000,
            'paid_amount' => 0,
            'outstanding_amount' => 370000,
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($this->admin)->get(route('sales-finance.invoices.pdf', $invoice));
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));

        // Render view HTML directly to inspect contents
        $view = view('sales-finance.pdf.invoice', ['invoice' => $invoice->load(['salesOrder.customer', 'salesOrder.items'])])->render();
        $this->assertStringContainsString('FAKTUR', $view);
        $this->assertStringContainsString('PT Toko Pembeli Jaya', $view);
        $this->assertStringContainsString('PO-CUST-9988', $view);
        $this->assertStringContainsString('Tiga Ratus Tujuh Puluh Ribu', $view);
        $this->assertStringContainsString('Barang barang yang telah dibeli tidak dapat dikembalikan', $view);
    }

    public function test_purchase_invoice_renders_properly()
    {
        $supplier = Supplier::create([
            'id' => 'SUP-COMP-01',
            'name' => 'PT Supplier Utama Indonesia',
            'phone' => '021-5556677',
            'email' => 'order@supplierutama.co.id',
            'address' => 'Kawasan Industri Pulogadung, Jakarta Timur',
        ]);

        $sp = SupplierProduct::create([
            'id' => 'SP-COMP-01',
            'supplier_id' => $supplier->id,
            'sku' => 'SKU-SUP-01',
            'item_name' => 'Kabel Listrik NYA 2.5mm',
            'unit' => 'roll',
            'last_purchase_price' => 250000,
            'status' => 'active',
        ]);

        $po = SupplierPO::create([
            'id' => 'SPO-COMP-01',
            'supplier_id' => $supplier->id,
            'po_number' => 'P-26/10/0001',
            'reference_number' => 'SJ-SUP-7788',
            'order_date' => '2026-10-02',
            'total_amount' => 500000,
            'status' => 'received',
            'notes' => 'Pengiriman barang batch 1',
        ]);

        SupplierPOItem::create([
            'supplier_po_id' => $po->id,
            'supplier_product_id' => $sp->id,
            'qty' => 2,
            'price' => 250000,
            'discount_1' => 0,
            'discount' => 0,
        ]);

        $response = $this->actingAs($this->admin)->get(route('supplier-po.invoice', $po));
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));

        // Render view HTML directly to inspect contents
        $view = view('supplier_po.invoice', ['supplierPo' => $po->load(['supplier', 'items.supplierProduct'])])->render();
        $this->assertStringContainsString('Faktur Pembelian', $view);
        $this->assertStringContainsString('PT Supplier Utama Indonesia', $view);
        $this->assertStringContainsString('PT. SAKTINDO JAYA BERSAMA', $view);
        $this->assertStringContainsString('P-26/10/0001', $view);
        $this->assertStringContainsString('SJ-SUP-7788', $view);
        $this->assertStringContainsString('Lima Ratus Ribu', $view);
    }
}
