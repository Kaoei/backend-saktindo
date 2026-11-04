<?php

namespace Tests\Feature;

use App\Models\GudangProduct;
use App\Models\InBound;
use App\Models\OutBound;
use App\Models\Rak;
use App\Models\Role;
use App\Models\StockMutation;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\User;
use App\Models\WarehouseTask;
use App\Services\StockMutationService;
use App\Services\StockSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
    }

    protected function authenticateSuperAdmin(): User
    {
        $user = User::firstOrCreate(
            ['email' => 'superadmin_test@example.com'],
            [
                'name' => 'Super Admin Test',
                'password' => bcrypt('password'),
                'role' => User::ROLE_SUPER_ADMIN,
                'status' => 'active'
            ]
        );
        $this->actingAs($user);
        return $user;
    }

    public function test_stock_card_index_page_is_accessible(): void
    {
        $this->authenticateSuperAdmin();

        $supplier = Supplier::create(['name' => 'Supplier Test SC', 'status' => 'active']);
        $product = SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'sku' => 'SKU-SC-001',
            'item_name' => 'Produk Kartu Stok Test',
            'last_purchase_price' => 50000,
            'unit' => 'pcs',
            'status' => 'active',
        ]);

        $rack = Rak::firstOrCreate(['rak_kode' => 'RAK-SC-01'], ['location' => 'Gudang Utama', 'gudang' => 'js']);

        $gp = GudangProduct::create([
            'id' => 'GP-SC-001',
            'supplier_product_id' => $product->id,
            'rack_id' => $rack->rak_kode,
            'qty' => 100,
            'price' => 50000,
            'status' => 'stored',
        ]);

        // Record initial mutation
        StockMutationService::recordMutation([
            'supplier_product_id' => $product->id,
            'gudang_product_id'   => $gp->id,
            'rack_id'             => $rack->rak_kode,
            'transaction_date'    => now()->subDays(5),
            'mutation_type'       => 'initial_stock',
            'reference_number'    => 'INIT-001',
            'qty_in'              => 100,
            'qty_out'             => 0,
            'unit_cost'           => 50000,
            'notes'               => 'Saldo Awal Test',
        ]);

        $response = $this->get(route('stock-cards.index', ['product_id' => $product->id]));
        $response->assertStatus(200);
        $response->assertSee('Kartu Stok Persediaan Barang');
        $response->assertSee('Produk Kartu Stok Test');
        $response->assertSee('SKU-SC-001');
        $response->assertSee('Saldo Awal');
    }

    public function test_stock_card_running_balance_and_valuation(): void
    {
        $this->authenticateSuperAdmin();

        $supplier = Supplier::create(['name' => 'Supplier Test Balance', 'status' => 'active']);
        $product = SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'sku' => 'SKU-SC-BAL',
            'item_name' => 'Produk Perhitungan Saldo',
            'last_purchase_price' => 100000,
            'unit' => 'pcs',
            'status' => 'active',
        ]);

        // Mutation 1: Initial stock = 50 pcs @ 100.000
        StockMutationService::recordMutation([
            'supplier_product_id' => $product->id,
            'transaction_date'    => now()->subDays(10),
            'mutation_type'       => 'initial_stock',
            'reference_number'    => 'INIT-BAL',
            'qty_in'              => 50,
            'qty_out'             => 0,
            'unit_cost'           => 100000,
            'notes'               => 'Saldo Awal',
        ]);

        // Mutation 2: Inbound = 30 pcs @ 100.000 (Balance should be 80)
        StockMutationService::recordMutation([
            'supplier_product_id' => $product->id,
            'transaction_date'    => now()->subDays(5),
            'mutation_type'       => 'inbound',
            'reference_number'    => 'INB-TEST-01',
            'qty_in'              => 30,
            'qty_out'             => 0,
            'unit_cost'           => 100000,
            'notes'               => 'Barang Masuk',
        ]);

        // Mutation 3: Outbound = 20 pcs (Balance should be 60)
        StockMutationService::recordMutation([
            'supplier_product_id' => $product->id,
            'transaction_date'    => now()->subDays(2),
            'mutation_type'       => 'outbound',
            'reference_number'    => 'OUT-TEST-01',
            'qty_in'              => 0,
            'qty_out'             => 20,
            'unit_cost'           => 100000,
            'notes'               => 'Barang Keluar',
        ]);

        StockMutationService::recalculateBalances($product->id);

        $latestMutation = StockMutation::where('supplier_product_id', $product->id)
            ->orderBy('transaction_date', 'desc')
            ->first();

        $this->assertEquals(60, $latestMutation->balance);
        $this->assertEquals(6000000, (float)$latestMutation->balance_value);

        // Verify index page reflects the balance
        $response = $this->get(route('stock-cards.index', [
            'product_id' => $product->id,
            'start_date' => now()->subDays(15)->format('Y-m-d'),
            'end_date'   => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $response->assertSee('Produk Perhitungan Saldo');
    }

    public function test_stock_card_print_and_export(): void
    {
        $this->authenticateSuperAdmin();

        $supplier = Supplier::create(['name' => 'Supplier Test Print', 'status' => 'active']);
        $product = SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'sku' => 'SKU-SC-PRINT',
            'item_name' => 'Produk Cetak Kartu Stok',
            'last_purchase_price' => 75000,
            'unit' => 'pcs',
            'status' => 'active',
        ]);

        StockMutationService::recordMutation([
            'supplier_product_id' => $product->id,
            'transaction_date'    => now(),
            'mutation_type'       => 'inbound',
            'reference_number'    => 'INB-PRINT-01',
            'qty_in'              => 15,
            'qty_out'             => 0,
            'unit_cost'           => 75000,
            'notes'               => 'Barang Masuk Cetak',
        ]);

        // Test Print View
        $printResponse = $this->get(route('stock-cards.print', ['product_id' => $product->id]));
        $printResponse->assertStatus(200);
        $printResponse->assertSee('KARTU STOK PERSEDIAAN BARANG');
        $printResponse->assertSee('Produk Cetak Kartu Stok');

        // Test CSV Export
        $exportResponse = $this->get(route('stock-cards.export-excel', ['product_id' => $product->id]));
        $exportResponse->assertStatus(200);
        $this->assertStringContainsString('text/csv', $exportResponse->headers->get('content-type'));
    }

    public function test_recalculate_route(): void
    {
        $this->authenticateSuperAdmin();

        $supplier = Supplier::create(['name' => 'Supplier Test Recalc', 'status' => 'active']);
        $product = SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'sku' => 'SKU-SC-RECALC',
            'item_name' => 'Produk Recalculate Test',
            'last_purchase_price' => 20000,
            'unit' => 'pcs',
            'status' => 'active',
        ]);

        $response = $this->post(route('stock-cards.recalculate'), [
            'product_id' => $product->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
    }

    public function test_stock_card_filter_by_warehouse_and_rack_without_product(): void
    {
        $this->authenticateSuperAdmin();

        $supplier = Supplier::create(['name' => 'Supplier Test WhRack', 'status' => 'active']);
        $productA = SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'sku' => 'SKU-WHRACK-A',
            'item_name' => 'Barang WhRack A',
            'last_purchase_price' => 30000,
            'unit' => 'pcs',
            'status' => 'active',
        ]);
        $productB = SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'sku' => 'SKU-WHRACK-B',
            'item_name' => 'Barang WhRack B',
            'last_purchase_price' => 45000,
            'unit' => 'pcs',
            'status' => 'active',
        ]);

        $rack = Rak::firstOrCreate(['rak_kode' => 'L1 R1-1'], ['location' => 'Gudang JS Lantai 1', 'gudang' => 'js']);

        StockMutationService::recordMutation([
            'supplier_product_id' => $productA->id,
            'rack_id'             => $rack->rak_kode,
            'transaction_date'    => now()->subDay(),
            'mutation_type'       => 'inbound',
            'reference_number'    => 'INB-RACK-01',
            'qty_in'              => 50,
            'qty_out'             => 0,
            'unit_cost'           => 30000,
            'notes'               => 'Masuk Barang A ke Rak L1 R1-1',
        ]);

        StockMutationService::recordMutation([
            'supplier_product_id' => $productB->id,
            'rack_id'             => $rack->rak_kode,
            'transaction_date'    => now(),
            'mutation_type'       => 'inbound',
            'reference_number'    => 'INB-RACK-02',
            'qty_in'              => 25,
            'qty_out'             => 0,
            'unit_cost'           => 45000,
            'notes'               => 'Masuk Barang B ke Rak L1 R1-1',
        ]);

        // Filter purely by warehouse and rack (no product_id)
        $response = $this->get(route('stock-cards.index', [
            'gudang_type' => 'JS',
            'rack_id' => 'L1 R1-1',
        ]));

        $response->assertStatus(200);
        $response->assertSee('LOKASI RAK: L1 R1-1');
        $response->assertSee('Barang WhRack A');
        $response->assertSee('Barang WhRack B');

        // Test Print by Rack
        $printResponse = $this->get(route('stock-cards.print', [
            'gudang_type' => 'JS',
            'rack_id' => 'L1 R1-1',
        ]));
        $printResponse->assertStatus(200);
        $printResponse->assertSee('Rak L1 R1-1');

        // Test Export by Rack
        $exportResponse = $this->get(route('stock-cards.export-excel', [
            'gudang_type' => 'JS',
            'rack_id' => 'L1 R1-1',
        ]));
        $exportResponse->assertStatus(200);
        $this->assertStringContainsString('text/csv', $exportResponse->headers->get('content-type'));
    }
}
