<?php

namespace Tests\Feature;

use App\Models\GudangProduct;
use App\Models\InBound;
use App\Models\Invoice;
use App\Models\Master_customer;
use App\Models\Rak;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\User;
use App\Models\WarehouseTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleBusinessFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create standard roles with permissions
        Role::create(['slug' => User::ROLE_SUPER_ADMIN, 'name' => 'Super Admin', 'permissions' => []]);
        Role::create(['slug' => User::ROLE_GUDANG, 'name' => 'Gudang', 'permissions' => ['suppliers.view']]);
        Role::create(['slug' => User::ROLE_SALES, 'name' => 'Sales', 'permissions' => ['sales_finance.view', 'sales_finance.create', 'sales_finance.edit']]);
        Role::create(['slug' => User::ROLE_FINANCE, 'name' => 'Finance', 'permissions' => ['sales_finance.view', 'suppliers.view']]);
    }

    public function test_gudang_role_complete_inbound_to_outbound_flow(): void
    {
        $gudangUser = User::factory()->create(['role' => User::ROLE_GUDANG]);

        $supplier = Supplier::create(['name' => 'PT Supplier Utama', 'status' => 'active']);
        $supplierProduct = SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'sku' => 'SKU-GDG-100',
            'item_name' => 'Barang Masuk Testing',
            'status' => 'active',
        ]);
        $rak = Rak::create(['rak_kode' => 'RAK-A1', 'location' => 'Lantai 1', 'gudang' => 'js']);

        // 1. Gudang user accesses Inbound list
        $this->actingAs($gudangUser)
            ->get(route('inbound.index'))
            ->assertStatus(200);

        // 2. Gudang user creates Inbound record
        $this->actingAs($gudangUser)
            ->post(route('inbound.store'), [
                'supplier_id' => $supplier->id,
                'supplier_product_id' => $supplierProduct->id,
                'qty_received' => 50,
                'received_date' => now()->toDateString(),
            ])->assertRedirect(route('inbound.index'));

        $inbound = InBound::first();
        $this->assertNotNull($inbound);
        $this->assertEquals('pending', $inbound->status);

        // 3. Gudang user processes Inbound into Rak
        $this->actingAs($gudangUser)
            ->post(route('gudang-product.store'), [
                'in_bound_id' => $inbound->id,
                'rack_id' => $rak->rak_kode,
                'gudang_type' => 'JS',
            ])->assertRedirect(route('gudang-product.index'));

        $inbound->refresh();
        $this->assertEquals('stored', $inbound->status);
        $this->assertDatabaseHas('gudang_products', [
            'supplier_product_id' => $supplierProduct->id,
            'rack_id' => $rak->rak_kode,
            'qty' => 50,
        ]);
    }

    public function test_sales_role_so_and_invoice_creation_flow(): void
    {
        $salesUser = User::factory()->create(['role' => User::ROLE_SALES]);
        $customer = Master_customer::create([
            'id' => 'CUST-SLS-01',
            'nama_customer' => 'Customer Sales Flow',
            'nama_pic' => 'Budi',
            'nomor_hp' => '081234567',
            'email' => 'sales@cust.com',
            'alamat' => 'Jl. Sales',
            'kota' => 'Jakarta',
        ]);

        $supplier = Supplier::create(['name' => 'Supplier Sales', 'status' => 'active']);
        $product = SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'sku' => 'SKU-SLS-01',
            'item_name' => 'Barang Sales',
            'status' => 'active',
            'last_purchase_price' => 100000,
        ]);

        // 1. Sales accesses SO list & form
        $this->actingAs($salesUser)
            ->get(route('sales-finance.index'))
            ->assertStatus(200);

        $this->actingAs($salesUser)
            ->get(route('sales-finance.create'))
            ->assertStatus(200);

        // 2. Sales submits Sales Order
        $response = $this->actingAs($salesUser)
            ->post(route('sales-finance.store'), [
                'customer_id' => $customer->id,
                'customer_name' => $customer->nama_customer,
                'customer_po_number' => 'PO-CUST-999',
                'po_date' => now()->toDateString(),
                'order_date' => now()->toDateString(),
                'sales_type' => 'js',
                'order_status' => 'draft',
                'items' => [
                    [
                        'product_code' => $product->id,
                        'product_name' => $product->item_name,
                        'unit' => 'pcs',
                        'quantity' => 5,
                        'unit_price' => 150000,
                        'discount_1' => 0,
                        'discount_2' => 0,
                        'discount_3' => 0,
                        'discount_4' => 0,
                    ],
                ],
            ]);

        $salesOrder = SalesOrder::latest()->first();
        $this->assertNotNull($salesOrder);
        $response->assertRedirect(route('sales-finance.show', $salesOrder));

        // 3. Sales generates Invoice
        $this->actingAs($salesUser)
            ->post(route('sales-finance.invoice.generate', $salesOrder->id), [
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'tax_type' => 'js',
            ])->assertRedirect(route('sales-finance.show', $salesOrder));

        $this->assertDatabaseHas('invoices', [
            'sales_order_id' => $salesOrder->id,
        ]);
    }

    public function test_finance_role_ar_and_payment_flow(): void
    {
        $financeUser = User::factory()->create(['role' => User::ROLE_FINANCE]);
        $customer = Master_customer::create([
            'id' => 'CUST-FIN-01',
            'nama_customer' => 'Customer Finance Flow',
            'nama_pic' => 'Ani',
            'nomor_hp' => '087654321',
            'email' => 'finance@cust.com',
            'alamat' => 'Jl. Finance',
            'kota' => 'Surabaya',
        ]);

        $salesOrder = SalesOrder::create([
            'id' => 'SO-FIN-001',
            'customer_id' => $customer->id,
            'customer_name' => $customer->nama_customer,
            'customer_po_number' => 'PO-FIN-001',
            'order_date' => now(),
            'sales_type' => 'js',
            'order_status' => 'confirmed',
            'subtotal' => 1000000,
            'tax_amount' => 0,
            'grand_total' => 1000000,
        ]);

        $invoice = Invoice::create([
            'id' => 'INV-FIN-001',
            'sales_order_id' => $salesOrder->id,
            'invoice_number' => 'INV-FIN-001',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => 1000000,
            'tax_amount' => 0,
            'grand_total' => 1000000,
            'paid_amount' => 0,
            'outstanding_amount' => 1000000,
            'status' => 'outstanding',
            'invoice_type' => 'normal',
        ]);

        // 1. Finance accesses AR page
        $this->actingAs($financeUser)
            ->get(route('finance.ar'))
            ->assertStatus(200)
            ->assertSee('INV-FIN-001');

        // 2. Finance records pelunasan payment
        $this->actingAs($financeUser)
            ->post(route('finance.payment.store', $invoice->id), [
                'type' => 'ar',
                'id' => $invoice->id,
                'payment_date' => now()->toDateString(),
                'method' => 'transfer_bank',
                'receiving_account' => 'js',
                'amount' => 1000000,
                'reference_number' => 'TRF-BCA-888',
                'notes' => 'Lunas via Transfer BCA',
            ])->assertRedirect();

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(0, $invoice->outstanding_amount);
    }
}
