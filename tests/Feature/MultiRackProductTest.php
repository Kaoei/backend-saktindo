<?php

namespace Tests\Feature;

use App\Models\GudangProduct;
use App\Models\InBound;
use App\Models\Master_customer;
use App\Models\Rak;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiRackProductTest extends TestCase
{
    use RefreshDatabase;

    private User $gudangUser;
    private User $superAdmin;
    private Supplier $supplier;
    private SupplierProduct $product;
    private Rak $rak1;
    private Rak $rak2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gudangUser = User::factory()->create(['role' => User::ROLE_GUDANG]);
        $this->superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->supplier = Supplier::create([
            'name' => 'PT Multi Rak Supplier',
            'status' => 'active',
        ]);

        $this->product = SupplierProduct::create([
            'supplier_id' => $this->supplier->id,
            'sku' => 'SKU-MULTI-01',
            'item_name' => 'Lampu Sorot LED 50W',
            'brand' => 'Philips',
            'category' => 'Lampu',
            'last_purchase_price' => 150000,
            'status' => 'active',
        ]);

        $this->rak1 = Rak::create([
            'rak_kode' => 'RAK-JS-01',
            'location' => 'Lantai 1 Sayap Barat',
            'gudang' => 'js',
        ]);

        $this->rak2 = Rak::create([
            'rak_kode' => 'RAK-SJB-02',
            'location' => 'Lantai 2 Rak Besi',
            'gudang' => 'sjb',
        ]);
    }

    public function test_inbound_item_can_be_allocated_to_multiple_different_racks_at_once(): void
    {
        // 1. Create Inbound receiving 100 pcs
        $inbound = InBound::create([
            'id' => InBound::generateId(),
            'supplier_id' => $this->supplier->id,
            'supplier_product_id' => $this->product->id,
            'qty_received' => 100,
            'received_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        // 2. Process to 2 different racks: 60 pcs in RAK-JS-01 (JS) and 40 pcs in RAK-SJB-02 (SJB)
        $response = $this->actingAs($this->gudangUser)
            ->post(route('gudang-product.store'), [
                'in_bound_id' => $inbound->id,
                'allocations' => [
                    [
                        'rack_id' => $this->rak1->rak_kode,
                        'gudang_type' => 'JS',
                        'qty' => 60,
                    ],
                    [
                        'rack_id' => $this->rak2->rak_kode,
                        'gudang_type' => 'SJB',
                        'qty' => 40,
                    ],
                ],
            ]);

        $response->assertRedirect(route('gudang-product.index'));
        $response->assertSessionHas('success');

        // 3. Verify InBound status is stored
        $inbound->refresh();
        $this->assertEquals('stored', $inbound->status);

        // 4. Verify GudangProduct has 2 records for this product on different racks
        $this->assertDatabaseHas('gudang_products', [
            'supplier_product_id' => $this->product->id,
            'rack_id' => $this->rak1->rak_kode,
            'gudang_type' => 'JS',
            'qty' => 60,
        ]);

        $this->assertDatabaseHas('gudang_products', [
            'supplier_product_id' => $this->product->id,
            'rack_id' => $this->rak2->rak_kode,
            'gudang_type' => 'SJB',
            'qty' => 40,
        ]);

        // 5. Total stock for this product in warehouse is 100
        $totalStock = GudangProduct::where('supplier_product_id', $this->product->id)->sum('qty');
        $this->assertEquals(100, $totalStock);
    }

    public function test_manual_stock_can_be_stored_across_multiple_racks(): void
    {
        $response = $this->actingAs($this->gudangUser)
            ->post(route('gudang-product.storeManual'), [
                'item_name' => 'Kabel Listrik NYA 2.5mm',
                'sku' => 'SKU-KBL-NYA',
                'brand' => 'Supreme',
                'price' => 500000,
                'allocations' => [
                    [
                        'rack_id' => $this->rak1->rak_kode,
                        'gudang_type' => 'JS',
                        'qty' => 30,
                    ],
                    [
                        'rack_id' => $this->rak2->rak_kode,
                        'gudang_type' => 'SJB',
                        'qty' => 20,
                    ],
                ],
            ]);

        $response->assertRedirect(route('gudang-product.index'));

        $sp = SupplierProduct::where('sku', 'SKU-KBL-NYA')->first();
        $this->assertNotNull($sp);

        $this->assertDatabaseHas('gudang_products', [
            'supplier_product_id' => $sp->id,
            'rack_id' => $this->rak1->rak_kode,
            'qty' => 30,
        ]);

        $this->assertDatabaseHas('gudang_products', [
            'supplier_product_id' => $sp->id,
            'rack_id' => $this->rak2->rak_kode,
            'qty' => 20,
        ]);
    }

    public function test_stock_can_be_split_or_transferred_between_racks(): void
    {
        // 1. Initial product with 50 pcs in RAK-JS-01
        $gudangProd = GudangProduct::create([
            'id' => GudangProduct::generateId($this->product->sku),
            'supplier_product_id' => $this->product->id,
            'rack_id' => $this->rak1->rak_kode,
            'gudang_type' => 'JS',
            'qty' => 50,
            'price' => 150000,
            'status' => 'stored',
        ]);

        // 2. Transfer 20 pcs from RAK-JS-01 to RAK-SJB-02
        $response = $this->actingAs($this->gudangUser)
            ->post(route('gudang-product.splitRack'), [
                'source_id' => $gudangProd->id,
                'target_rack_id' => $this->rak2->rak_kode,
                'target_gudang_type' => 'SJB',
                'qty' => 20,
            ]);

        $response->assertSessionHas('status');

        // 3. Source rack should now have 30 pcs
        $gudangProd->refresh();
        $this->assertEquals(30, $gudangProd->qty);

        // 4. Target rack should have 20 pcs
        $this->assertDatabaseHas('gudang_products', [
            'supplier_product_id' => $this->product->id,
            'rack_id' => $this->rak2->rak_kode,
            'qty' => 20,
        ]);

        // 5. Total stock is still 50 pcs
        $totalStock = GudangProduct::where('supplier_product_id', $this->product->id)->sum('qty');
        $this->assertEquals(50, $totalStock);
    }

    public function test_split_rack_validates_quantity_not_exceeding_source(): void
    {
        $gudangProd = GudangProduct::create([
            'id' => GudangProduct::generateId($this->product->sku),
            'supplier_product_id' => $this->product->id,
            'rack_id' => $this->rak1->rak_kode,
            'gudang_type' => 'JS',
            'qty' => 50,
            'price' => 150000,
            'status' => 'stored',
        ]);

        // Try transferring 60 pcs (more than available 50)
        $response = $this->actingAs($this->gudangUser)
            ->post(route('gudang-product.splitRack'), [
                'source_id' => $gudangProd->id,
                'target_rack_id' => $this->rak2->rak_kode,
                'target_gudang_type' => 'SJB',
                'qty' => 60,
            ]);

        $response->assertSessionHasErrors(['qty']);

        // Stock in source rack remains unchanged
        $gudangProd->refresh();
        $this->assertEquals(50, $gudangProd->qty);
    }

    public function test_check_stock_correctly_aggregates_product_from_all_different_racks(): void
    {
        // 40 pcs in Rak 1, 30 pcs in Rak 2 (Total: 70 pcs)
        GudangProduct::create([
            'id' => GudangProduct::generateId($this->product->sku),
            'supplier_product_id' => $this->product->id,
            'rack_id' => $this->rak1->rak_kode,
            'gudang_type' => 'JS',
            'qty' => 40,
            'price' => 150000,
            'status' => 'stored',
        ]);

        GudangProduct::create([
            'id' => GudangProduct::generateId($this->product->sku),
            'supplier_product_id' => $this->product->id,
            'rack_id' => $this->rak2->rak_kode,
            'gudang_type' => 'SJB',
            'qty' => 30,
            'price' => 150000,
            'status' => 'stored',
        ]);

        $customer = Master_customer::create([
            'id' => 'CUST-MR-01',
            'nama_customer' => 'Customer Multi Rak',
            'nama_pic' => 'Andi',
            'nomor_hp' => '081299999',
            'email' => 'andi@cust.com',
            'alamat' => 'Jl. Merdeka',
            'kota' => 'Surabaya',
        ]);

        $so = SalesOrder::create([
            'id' => SalesOrder::generateId(),
            'customer_id' => $customer->id,
            'customer_name' => $customer->nama_customer,
            'customer_po_number' => 'PO-MR-001',
            'order_date' => now()->toDateString(),
            'sales_type' => 'js',
            'order_status' => 'draft',
            'subtotal' => 65 * 150000,
            'grand_total' => 65 * 150000,
        ]);

        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_code' => $this->product->id,
            'product_name' => $this->product->item_name,
            'quantity' => 65, // Needs 65 pcs. Since total across Rak 1 & Rak 2 is 70, it must be 'available'
            'unit_price' => 150000,
        ]);

        $financeUser = User::factory()->create(['role' => User::ROLE_FINANCE]);

        $this->actingAs($this->superAdmin)
            ->post(route('sales-finance.stock-check', $so->id))
            ->assertRedirect();

        $soItem->refresh();
        $this->assertEquals('available', $soItem->stock_status);
        $this->assertEquals(70, $soItem->available_stock);
    }
}
