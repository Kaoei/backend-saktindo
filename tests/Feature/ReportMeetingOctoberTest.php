<?php

namespace Tests\Feature;

use App\Models\GudangProduct;
use App\Models\InBound;
use App\Models\Master_customer;
use App\Models\Rak;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\SupplierPO;
use App\Models\SupplierProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportMeetingOctoberTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private Supplier $supplier;
    private SupplierProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->supplier = Supplier::create([
            'name' => 'PT Supplier Indah Test',
            'status' => 'active',
        ]);

        $this->product = SupplierProduct::create([
            'supplier_id' => $this->supplier->id,
            'sku' => 'SKU-TEST-OCT-01',
            'item_name' => 'Produk Test Oktober',
            'last_purchase_price' => 100000,
            'status' => 'active',
        ]);
    }

    /**
     * Point 1.1: Inbound list displays supplier PO reference number / SJ
     */
    public function test_inbound_list_displays_supplier_po_reference_number(): void
    {
        $po = SupplierPO::create([
            'id' => 'SPO-TEST-1001',
            'po_number' => 'PO-SUP-TEST-001',
            'reference_number' => 'SJ-SUPP-998877',
            'supplier_id' => $this->supplier->id,
            'status' => 'pending',
            'order_date' => now()->toDateString(),
            'total_amount' => 1000000,
        ]);

        $inbound = InBound::create([
            'id' => InBound::generateId(),
            'po_number' => $po->po_number,
            'supplier_po_id' => $po->id,
            'supplier_id' => $this->supplier->id,
            'supplier_product_id' => $this->product->id,
            'qty_received' => 10,
            'received_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('inbound.index'));

        $response->assertStatus(200);
        $response->assertSee('SJ-SUPP-998877');
    }

    /**
     * Point 2.1: Inbound list hides Saldo Awal / Migration records by default,
     * but allows viewing them via toggle.
     */
    public function test_inbound_list_hides_saldo_awal_migration_by_default_and_allows_toggle(): void
    {
        // 1. Create a real inbound
        $realInbound = InBound::create([
            'id' => InBound::generateId(),
            'supplier_id' => $this->supplier->id,
            'supplier_product_id' => $this->product->id,
            'qty_received' => 20,
            'received_date' => now()->toDateString(),
            'status' => 'pending',
            'notes' => 'Penerimaan Real Supplier',
        ]);

        // 2. Create a migration / saldo awal inbound
        $migrationInbound = InBound::create([
            'id' => InBound::generateId(),
            'supplier_id' => $this->supplier->id,
            'supplier_product_id' => $this->product->id,
            'qty_received' => 100,
            'received_date' => now()->toDateString(),
            'status' => 'stored',
            'notes' => 'Saldo Awal Stok (Auto Reconcile)',
        ]);

        // Access index without param: Saldo Awal should be filtered out
        $response = $this->actingAs($this->superAdmin)
            ->get(route('inbound.index'));

        $response->assertStatus(200);
        $response->assertSee($realInbound->id);
        $response->assertDontSee($migrationInbound->id);
        $response->assertSee('Saldo Awal Migrasi Disembunyikan');

        // Access index with show_saldo_awal=1: Saldo Awal should be visible
        $responseWithSaldoAwal = $this->actingAs($this->superAdmin)
            ->get(route('inbound.index', ['show_saldo_awal' => 1]));

        $responseWithSaldoAwal->assertStatus(200);
        $responseWithSaldoAwal->assertSee($realInbound->id);
        $responseWithSaldoAwal->assertSee($migrationInbound->id);
        $responseWithSaldoAwal->assertSee('Migrasi / Saldo Awal');
    }

    /**
     * Point 2.3: Gudang RK is supported in racks and inventory
     */
    public function test_gudang_rk_is_supported_in_racks_and_inventory(): void
    {
        // 1. Create rack with RK
        $rakRk = Rak::create([
            'rak_kode' => 'RAK-RK-01',
            'location' => 'Lantai 1 RK',
            'gudang' => 'rk',
        ]);

        $this->assertDatabaseHas('raks', [
            'rak_kode' => 'RAK-RK-01',
            'gudang' => 'rk',
        ]);

        // 2. Manual stock allocation to RK
        $responseStock = $this->actingAs($this->superAdmin)
            ->post(route('gudang-product.storeManual'), [
                'supplier_product_id' => $this->product->id,
                'item_name' => $this->product->item_name,
                'gudang_type' => 'RK',
                'rack_id' => 'RAK-RK-01',
                'qty' => 25,
            ]);
        $responseStock->assertRedirect();

        $this->assertDatabaseHas('gudang_products', [
            'supplier_product_id' => $this->product->id,
            'gudang_type' => 'RK',
            'rack_id' => 'RAK-RK-01',
            'qty' => 25,
        ]);

        // 3. View Gudang index shows RK card
        $indexResponse = $this->actingAs($this->superAdmin)
            ->get(route('gudang-product.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Gudang RK');
    }

    /**
     * Point 2.4: Partial rack allocation sets status to partial, tracks qty_allocated,
     * and final allocation sets to stored.
     */
    public function test_partial_rack_allocation_workflow(): void
    {
        $rak1 = Rak::create(['rak_kode' => 'RAK-JS-TEST', 'location' => 'A1', 'gudang' => 'js']);
        $rak2 = Rak::create(['rak_kode' => 'RAK-SJB-TEST', 'location' => 'B1', 'gudang' => 'sjb']);

        $inbound = InBound::create([
            'id' => InBound::generateId(),
            'supplier_id' => $this->supplier->id,
            'supplier_product_id' => $this->product->id,
            'qty_received' => 100,
            'qty_allocated' => 0,
            'received_date' => now()->toDateString(),
            'status' => 'pending',
            'no_faktur' => 'INV-TEST-PARTIAL',
        ]);

        // Step 1: Allocate only 40 pcs
        $response = $this->actingAs($this->superAdmin)
            ->post(route('gudang-product.store'), [
                'in_bound_id' => $inbound->id,
                'allocations' => [
                    [
                        'rack_id' => $rak1->rak_kode,
                        'gudang_type' => 'JS',
                        'qty' => 40,
                    ],
                ],
            ]);
        $response->assertRedirect(route('gudang-product.index'));

        $inbound->refresh();
        $this->assertEquals(40, $inbound->qty_allocated);
        $this->assertEquals(60, $inbound->remaining_qty);
        $this->assertEquals('partial', $inbound->status);

        // Step 2: Allocate the remaining 60 pcs
        $response2 = $this->actingAs($this->superAdmin)
            ->post(route('gudang-product.store'), [
                'in_bound_id' => $inbound->id,
                'allocations' => [
                    [
                        'rack_id' => $rak2->rak_kode,
                        'gudang_type' => 'SJB',
                        'qty' => 60,
                    ],
                ],
            ]);
        $response2->assertRedirect(route('gudang-product.index'));

        $inbound->refresh();
        $this->assertEquals(100, $inbound->qty_allocated);
        $this->assertEquals(0, $inbound->remaining_qty);
        $this->assertEquals('stored', $inbound->status);
    }

    /**
     * Points 3.1, 3.2, 3.3: Sales order with simplified status, discount amount, DP amount, and rack_id
     */
    public function test_sales_order_status_discount_dp_and_rack_selection(): void
    {
        $rak = Rak::create(['rak_kode' => 'RAK-01', 'location' => 'Zona 1', 'gudang' => 'js']);

        // Stock at RAK-01
        GudangProduct::create([
            'id' => 'GP-TEST-OCT-01',
            'supplier_product_id' => $this->product->id,
            'rack_id' => 'RAK-01',
            'gudang_type' => 'JS',
            'qty' => 30,
        ]);

        $poNumber = 'PO-OCT-' . uniqid();
        $payload = [
            'customer_name' => 'PT Customer Test SO',
            'customer_po_number' => $poNumber,
            'order_status' => 'stock_check', // Point 3.1
            'order_date' => now()->toDateString(),
            'sales_type' => 'js',
            'notes' => 'SO Test Discount & Rack',
            'discount_amount' => 20000,       // Point 3.2
            'dp_amount' => 50000,             // Point 3.2
            'apply_ppn' => 1,
            'items' => [
                [
                    'product_code' => $this->product->id,
                    'product_name' => $this->product->item_name,
                    'unit' => 'pcs',
                    'quantity' => 2,
                    'unit_price' => 100000,
                    'rack_id' => 'RAK-01',    // Point 3.3
                    'discount_1' => 0,
                    'discount_2' => 0,
                    'discount_3' => 0,
                    'discount_4' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('sales-finance.store'), $payload);
        $response->assertRedirect();

        $so = SalesOrder::where('customer_po_number', $poNumber)->first();
        $this->assertNotNull($so);
        $this->assertEquals('stock_check', $so->order_status);
        $this->assertEquals(20000, (float) $so->discount_amount);
        $this->assertEquals(50000, (float) $so->dp_amount);

        // Subtotal = 2 * 100,000 = 200,000.
        // DPP = 200,000 - 20,000 = 180,000.
        // PPN (11%) = 19,800.
        // Total = 180,000 + 19,800 = 199,800.
        $this->assertEquals(19800, (float) $so->tax_amount);
        $this->assertEquals(199800, (float) $so->grand_total);

        // Check SalesOrderItem rack_id
        $item = $so->items()->first();
        $this->assertNotNull($item);
        $this->assertEquals('RAK-01', $item->rack_id);
    }
}
