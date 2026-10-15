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

        // Use standard RoleSeeder
        (new \Database\Seeders\RoleSeeder())->run();
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

    public function test_end_to_end_cross_role_workflow_with_approvals_and_tasks(): void
    {
        // 1. Create actors
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $gudangUser = User::factory()->create(['role' => User::ROLE_GUDANG]);
        $salesUser = User::factory()->create(['role' => User::ROLE_SALES]);
        $financeUser = User::factory()->create(['role' => User::ROLE_FINANCE]);

        // 2. Setup Master Data
        $supplier = Supplier::create(['name' => 'Supplier Multi Role', 'status' => 'active']);
        $product = SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'sku' => 'SKU-E2E-001',
            'item_name' => 'Produk Multi Role Flow',
            'last_purchase_price' => 200000,
            'status' => 'active',
        ]);
        $rak = Rak::create(['rak_kode' => 'RAK-FLOW-01', 'location' => 'JS', 'gudang' => 'js']);
        $customer = Master_customer::create([
            'id' => 'CUST-E2E-01',
            'nama_customer' => 'Customer Multi Role Flow',
            'nama_pic' => 'Budi Santoso',
            'nomor_hp' => '08123456789',
            'email' => 'e2e@example.com',
            'alamat' => 'Jl. Flow No. 1',
            'kota' => 'Jakarta',
        ]);

        // 3. Purchasing Workflow: SuperAdmin creates Supplier PO
        $poResponse = $this->actingAs($superAdmin)->post(route('supplier-po.store'), [
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'reference_number' => 'PO-REF-E2E',
            'notes' => 'PO Pengadaan E2E',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 100,
                    'price' => 200000,
                    'discount_1' => 0,
                ],
            ],
        ]);
        $poResponse->assertRedirect();
        $supplierPo = \App\Models\SupplierPO::latest()->first();
        $this->assertNotNull($supplierPo);
        $this->assertEquals('pending', $supplierPo->status);

        // 4. Inbound Workflow: Gudang user processes PO into Inbound & stores to Rak
        $this->actingAs($gudangUser)
            ->get(route('inbound.index'))
            ->assertStatus(200);

        $inboundResponse = $this->actingAs($gudangUser)->post(route('inbound.store'), [
            'supplier_id' => $supplier->id,
            'supplier_po_id' => $supplierPo->id,
            'received_date' => now()->toDateString(),
            'items' => [
                [
                    'supplier_product_id' => $product->id,
                    'qty_received' => 100,
                    'rack_id' => $rak->rak_kode,
                    'hpp' => 200000,
                ],
            ],
        ]);
        $inboundResponse->assertRedirect(route('inbound.index'));

        $supplierPo->refresh();
        $this->assertEquals('received', $supplierPo->status);

        $gudangProduct = GudangProduct::where('supplier_product_id', $product->id)
            ->where('rack_id', $rak->rak_kode)
            ->first();
        $this->assertNotNull($gudangProduct);
        $this->assertEquals(100, $gudangProduct->qty);

        // 5. Sales Workflow: Sales user creates Sales Order & checks stock
        $soResponse = $this->actingAs($salesUser)->post(route('sales-finance.store'), [
            'customer_id' => $customer->id,
            'customer_name' => $customer->nama_customer,
            'customer_po_number' => 'PO-SO-E2E',
            'po_date' => now()->toDateString(),
            'order_date' => now()->toDateString(),
            'sales_type' => 'js',
            'order_status' => 'draft',
            'items' => [
                [
                    'product_code' => $product->id,
                    'product_name' => $product->item_name,
                    'unit' => 'pcs',
                    'quantity' => 20,
                    'unit_price' => 300000,
                    'rack_id' => $rak->rak_kode,
                    'discount_1' => 0,
                    'discount_2' => 0,
                    'discount_3' => 0,
                    'discount_4' => 0,
                ],
            ],
        ]);
        $salesOrder = SalesOrder::latest()->first();
        $this->assertNotNull($salesOrder);
        $soResponse->assertRedirect(route('sales-finance.show', $salesOrder));

        // Check stock
        $this->actingAs($salesUser)
            ->post(route('sales-finance.stock-check', $salesOrder))
            ->assertRedirect();

        $salesOrder->refresh();
        $this->assertEquals('available', $salesOrder->stock_status);

        // Generate Invoice -> creates WarehouseTask
        $invResponse = $this->actingAs($salesUser)
            ->post(route('sales-finance.invoice.generate', $salesOrder), [
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'tax_type' => 'js',
            ]);
        $invResponse->assertRedirect(route('sales-finance.show', $salesOrder));

        $salesOrder->refresh();
        $this->assertEquals('invoiced', $salesOrder->order_status);

        $task = WarehouseTask::where('sales_order_id', $salesOrder->id)->first();
        $this->assertNotNull($task);
        $this->assertEquals('waiting', $task->status);

        // 6. Gudang Workflow: Gudang user sees task, changes to process, and executes outbound
        $this->actingAs($gudangUser)
            ->get(route('warehouse-task.index'))
            ->assertStatus(200)
            ->assertSee($task->id);

        // Process task
        $this->actingAs($gudangUser)
            ->patch(route('warehouse-task.process', $task->id))
            ->assertRedirect();

        $task->refresh();
        $this->assertEquals('process', $task->status);

        // Access outbound create and submit outbound
        $this->actingAs($gudangUser)
            ->get(route('outbound.create', $task->id))
            ->assertStatus(200);

        $outboundResponse = $this->actingAs($gudangUser)->post(route('outbound.store'), [
            'warehouse_task_id' => $task->id,
            'gudang_product_id' => $gudangProduct->id,
            'qty' => 20,
            'outbound_date' => now()->toDateString(),
            'delivery_type' => 'full',
            'note' => 'Pengiriman barang pesanan E2E',
        ]);
        $outboundResponse->assertRedirect(route('outbound.index'));

        // Verify task completed and stock deducted
        $task->refresh();
        $this->assertEquals('completed', $task->status);

        $gudangProduct->refresh();
        $this->assertEquals(80, $gudangProduct->qty);

        $salesOrder->refresh();
        $this->assertEquals('delivered', $salesOrder->order_status);

        // 7. Finance Workflow: Finance sees invoice, records payment
        $invoice = Invoice::where('sales_order_id', $salesOrder->id)->first();
        $this->assertNotNull($invoice);

        $this->actingAs($financeUser)
            ->get(route('finance.ar'))
            ->assertStatus(200)
            ->assertSee($invoice->invoice_number);

        // Record pelunasan payment
        $payResponse = $this->actingAs($financeUser)->post(route('finance.payment.store', $invoice->id), [
            'type' => 'ar',
            'id' => $invoice->id,
            'payment_date' => now()->toDateString(),
            'method' => 'transfer_bank',
            'receiving_account' => 'js',
            'amount' => $invoice->grand_total,
            'reference_number' => 'TRF-E2E-SUCCESS',
            'notes' => 'Pelunasan invoice E2E',
        ]);
        $payResponse->assertRedirect();

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(0, $invoice->outstanding_amount);
    }
}
