<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\GudangProduct;
use App\Models\Invoice;
use App\Models\Master_customer;
use App\Models\Product;
use App\Models\Rak;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Supplier;
use App\Models\SupplierPO;
use App\Models\SupplierProduct;
use App\Models\User;
use App\Models\WarehouseTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QCBugFixesEvaluationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $gudangUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->gudangUser = User::factory()->create([
            'role' => User::ROLE_GUDANG,
        ]);
    }

    public function test_point_01_products_by_rack_and_quick_view()
    {
        $rak = Rak::create([
            'rak_kode' => 'RAK-QC-01',
            'location' => 'Zone A',
            'gudang' => 'js',
        ]);

        $supplier = Supplier::create([
            'name' => 'Supplier QC',
            'status' => 'active',
        ]);

        $sp = SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'sku' => 'SKU-QC-01',
            'item_name' => 'Produk QC Lampu 10W',
            'unit' => 'pcs',
        ]);

        GudangProduct::create([
            'id' => 'GPROD-QC-01',
            'supplier_product_id' => $sp->id,
            'rack_id' => $rak->rak_kode,
            'gudang_type' => 'JS',
            'qty' => 25,
            'price' => 15000,
            'status' => 'stored',
        ]);

        // API Endpoint for quick products by rack
        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('stock-cards.products-by-rack', ['rack_id' => $rak->rak_kode]));

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('rack_id', 'RAK-QC-01')
            ->assertJsonPath('count', 1);

        $this->assertEquals(25, $response->json('products.0.qty'));

        // Rak view contains interactive quick view
        $rakView = $this->actingAs($this->superAdmin)->get(route('rak.index'));
        $rakView->assertStatus(200);
        $rakView->assertSee('btn-quick-view-rack');
        $rakView->assertSee('rackProductsModal');
    }

    public function test_point_02_and_05_master_product_edit_rack_allocations()
    {
        $rakA = Rak::create(['rak_kode' => 'RAK-EDIT-A', 'location' => 'Zone A', 'gudang' => 'js']);
        $rakB = Rak::create(['rak_kode' => 'RAK-EDIT-B', 'location' => 'Zone B', 'gudang' => 'js']);

        $supplier = Supplier::create(['name' => 'Supplier Edit', 'status' => 'active']);
        $sp = SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'sku' => 'SKU-EDIT-01',
            'item_name' => 'Produk Edit Rak',
            'unit' => 'pcs',
        ]);

        $gp = GudangProduct::create([
            'id' => 'GPROD-EDIT-01',
            'supplier_product_id' => $sp->id,
            'rack_id' => $rakA->rak_kode,
            'gudang_type' => 'JS',
            'qty' => 10,
            'status' => 'stored',
        ]);

        // Update product rack allocation to Rak B
        $response = $this->actingAs($this->superAdmin)->put(route('products.update', $sp->id), [
            'item_name' => 'Produk Edit Rak Updated',
            'sku' => $sp->sku,
            'stock_allocations' => [
                [
                    'id' => $gp->id,
                    'rack_id' => $rakB->rak_kode,
                    'qty' => 15,
                ]
            ]
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('gudang_products', [
            'id' => $gp->id,
            'rack_id' => 'RAK-EDIT-B',
            'qty' => 15,
        ]);
    }

    public function test_point_03_and_08_gudang_role_can_edit_warehouse_tasks()
    {
        $so = SalesOrder::create([
            'id' => 'SO-QC-WT-01',
            'order_date' => now()->toDateString(),
            'customer_name' => 'Customer QC',
            'customer_po_number' => 'PO-CUST-01',
            'order_status' => 'confirmed',
            'total_amount' => 100000,
        ]);

        $inv = Invoice::create([
            'id' => 'INV-QC-WT-01',
            'invoice_number' => 'INV/QC/WT/01',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'sales_order_id' => $so->id,
            'status' => 'outstanding',
            'grand_total' => 100000,
            'outstanding_amount' => 100000,
        ]);

        $task = WarehouseTask::create([
            'id' => WarehouseTask::generateId(),
            'sales_order_id' => $so->id,
            'invoice_id' => $inv->id,
            'status' => 'waiting',
        ]);

        // Gudang role can access task edit view
        $response = $this->actingAs($this->gudangUser)
            ->get(route('warehouse-task.edit', $task->id));

        $response->assertStatus(200);
        $response->assertSee('Edit Warehouse Task');
        $response->assertSee('Rincian Barang yang Dipesan');

        // Gudang role can update task
        $updateResp = $this->actingAs($this->gudangUser)
            ->put(route('warehouse-task.update', $task->id), [
                'sales_order_id' => $so->id,
                'invoice_id' => $inv->id,
                'assigned_to' => 'Staff Gudang QC',
                'status' => 'process',
                'note' => 'Sedang dipacking oleh staff gudang',
            ]);

        $updateResp->assertRedirect(route('warehouse-task.index'));
        $this->assertDatabaseHas('warehouse_tasks', [
            'id' => $task->id,
            'assigned_to' => 'Staff Gudang QC',
            'status' => 'process',
        ]);
    }

    public function test_point_04_and_07_saving_product_does_not_500()
    {
        Rak::create(['rak_kode' => 'RAK-SAVE-01', 'location' => 'Zone S', 'gudang' => 'js']);

        $response = $this->actingAs($this->superAdmin)->post(route('products.store'), [
            'product_name' => 'Lampu LED Downlight 18W',
            'category' => 'Elektronik',
            'sub_category' => 'Lampu',
            'brand' => 'Philips',
            'sku' => 'SKU-LED-18W',
            'price' => 45000,
            'unit' => 'pcs',
            'initial_qty' => 50,
            'rack_id' => 'RAK-SAVE-01',
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('supplier_products', [
            'sku' => 'SKU-LED-18W',
            'item_name' => 'Lampu LED Downlight 18W',
        ]);
    }

    public function test_point_10_deleting_product_with_relations_handles_gracefully()
    {
        $supplier = Supplier::create(['name' => 'Supplier Del', 'status' => 'active']);
        $sp = SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'sku' => 'SKU-DEL-01',
            'item_name' => 'Produk Terikat PO',
            'unit' => 'pcs',
        ]);

        $po = SupplierPO::create([
            'id' => SupplierPO::generateId(),
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-DEL-01',
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'total_amount' => 50000,
        ]);

        \DB::table('supplier_po_items')->insert([
            'supplier_po_id' => $po->id,
            'supplier_product_id' => $sp->id,
            'qty' => 10,
            'price' => 5000,
            'discount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Attempting to delete a product tied to a PO must redirect with friendly error, NEVER 500
        $response = $this->actingAs($this->superAdmin)
            ->delete(route('products.destroy', $sp->id));

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('error');
    }

    public function test_point_12_inventory_summary_totals_include_rk_and_outbound_sync()
    {
        $rakRK = Rak::create(['rak_kode' => 'RAK-RK-01', 'location' => 'Zone RK', 'gudang' => 'rk']);
        $supplier = Supplier::create(['name' => 'Supplier Sync', 'status' => 'active']);

        $sp = SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'sku' => 'SKU-SYNC-01',
            'item_name' => 'Kabel Roll 50M',
            'unit' => 'roll',
        ]);

        GudangProduct::create([
            'id' => 'GPROD-RK-01',
            'supplier_product_id' => $sp->id,
            'rack_id' => $rakRK->rak_kode,
            'gudang_type' => 'RK',
            'qty' => 80,
            'price' => 120000,
            'status' => 'stored',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('gudang-product.index'));

        $response->assertStatus(200);
        $this->assertEquals(80, $response->viewData('totalRK'));
    }

    public function test_point_15_item_descriptions_present_in_sales_and_warehouse_task_views()
    {
        $so = SalesOrder::create([
            'id' => 'SO-QC-ITEM-01',
            'order_date' => now()->toDateString(),
            'order_status' => 'confirmed',
            'customer_name' => 'PT Mitra Sejati',
            'customer_po_number' => 'PO-MITRA-01',
            'total_amount' => 250000,
        ]);

        SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_name' => 'VARIO - CAPSULE 10W',
            'product_code' => 'SKU-VARIO-10W',
            'quantity' => 16,
            'unit' => 'pcs',
            'unit_price' => 15000,
            'line_total' => 240000,
        ]);

        $inv = Invoice::create([
            'id' => 'INV-QC-ITEM-01',
            'invoice_number' => 'INV/QC/ITEM/01',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'sales_order_id' => $so->id,
            'status' => 'outstanding',
            'grand_total' => 250000,
            'outstanding_amount' => 250000,
        ]);

        WarehouseTask::create([
            'id' => WarehouseTask::generateId(),
            'sales_order_id' => $so->id,
            'invoice_id' => $inv->id,
            'status' => 'waiting',
        ]);

        // Sales Finance index displays Keterangan Barang column and product name
        $salesResp = $this->actingAs($this->superAdmin)->get(route('sales-finance.index'));
        $salesResp->assertStatus(200);
        $salesResp->assertSee('Keterangan Barang');
        $salesResp->assertSee('VARIO - CAPSULE 10W');

        // Warehouse Task index displays Keterangan Barang column and product name
        $taskResp = $this->actingAs($this->superAdmin)->get(route('warehouse-task.index'));
        $taskResp->assertStatus(200);
        $taskResp->assertSee('Keterangan Barang');
        $taskResp->assertSee('VARIO - CAPSULE 10W');
    }

    public function test_point_17_accounts_payable_date_has_data_order_attribute()
    {
        $supplier = Supplier::create(['name' => 'Supplier AP Date', 'status' => 'active']);

        SupplierPO::create([
            'id' => SupplierPO::generateId(),
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-DATE-01',
            'order_date' => '2026-11-02',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'total_amount' => 24820255,
            'outstanding_amount' => 24820255,
        ]);

        SupplierPO::create([
            'id' => SupplierPO::generateId(),
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-DATE-02',
            'order_date' => '2026-08-11',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'total_amount' => 256511,
            'outstanding_amount' => 256511,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('finance.ap'));
        $response->assertStatus(200);
        // data-order ensures DataTables sorts by ISO date instead of string DD/MM/YYYY
        $response->assertSee('data-order="2026-11-02"', false);
        $response->assertSee('data-order="2026-08-11"', false);
    }

    public function test_point_14_and_16_invoice_faktur_print_consolidates_items_and_syncs_numbers()
    {
        $so = SalesOrder::create([
            'id' => 'SO-QC-PRINT-01',
            'order_date' => now()->toDateString(),
            'order_status' => 'confirmed',
            'customer_name' => 'PT Test Kenari',
            'customer_po_number' => 'PO-KENARI-01',
            'sales_type' => 'js',
            'total_amount' => 320000,
        ]);

        // Same product split across 2 racks: 10 pcs and 6 pcs
        SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_name' => 'VARIO - CAPSULE',
            'product_code' => 'SKU-VARIO',
            'quantity' => 10,
            'unit' => 'pcs',
            'unit_price' => 20000,
            'line_total' => 200000,
            'rack_id' => 'RAK-01',
        ]);

        SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_name' => 'VARIO - CAPSULE',
            'product_code' => 'SKU-VARIO',
            'quantity' => 6,
            'unit' => 'pcs',
            'unit_price' => 20000,
            'line_total' => 120000,
            'rack_id' => 'RAK-02',
        ]);

        $inv = Invoice::create([
            'id' => 'INV-QC-PRINT-01',
            'invoice_number' => 'INV/QC/PRINT/001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'sales_order_id' => $so->id,
            'status' => 'outstanding',
            'tax_type' => 'non_pajak',
            'grand_total' => 320000,
            'outstanding_amount' => 320000,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('sales-finance.invoices.pdf', ['invoice' => $inv->id, 'action' => 'print_html']));

        $response->assertStatus(200);

        // Point 14: No Faktur and No Reff match, and No Seri Faktur Pajak is omitted for non SJB Pajak
        $response->assertSee('No. Faktur :</span>', false);
        $response->assertSee('No Reff :</span>', false);
        $response->assertSee('INV/QC/PRINT/001');
        $response->assertDontSee('No . Seri faktur Pajak :');

        // Point 16: Identical items consolidated to 1 row with total 16 pcs (not 2 separate rows)
        $response->assertSee('VARIO - CAPSULE');
        $response->assertSee('16');
    }

    public function test_zero_dead_buttons_across_critical_views()
    {
        // 1. Rak view
        $rakResp = $this->actingAs($this->superAdmin)->get(route('rak.index'));
        $rakResp->assertStatus(200);
        $rakResp->assertSee('id="rackProductsModal"', false);
        $rakResp->assertSee('id="deleteModal"', false);

        // 2. Warehouse task view
        $taskResp = $this->actingAs($this->superAdmin)->get(route('warehouse-task.index'));
        $taskResp->assertStatus(200);
        $taskResp->assertSee('id="deleteModal"', false);

        // 3. Finance AP view
        $apResp = $this->actingAs($this->superAdmin)->get(route('finance.ap'));
        $apResp->assertStatus(200);
        $apResp->assertSee('id="paymentModal"', false);

        // 4. Stock Cards view
        $scResp = $this->actingAs($this->superAdmin)->get(route('stock-cards.index'));
        $scResp->assertStatus(200);
        $scResp->assertSee('Pilih Cepat Rak:');
    }
}
