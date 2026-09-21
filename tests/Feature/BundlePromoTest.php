<?php

namespace Tests\Feature;

use App\Models\BundlePromo;
use App\Models\BundlePromoItem;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BundlePromoTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Supplier $supplier;
    protected SupplierProduct $productA;
    protected SupplierProduct $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->supplier = Supplier::create([
            'name' => 'PT Broco Electrical',
            'status' => 'active',
        ]);

        $this->productA = SupplierProduct::create([
            'supplier_id' => $this->supplier->id,
            'sku' => 'BROCO-1210',
            'item_name' => 'Fitting Plafon Besar Broco',
            'last_purchase_price' => 20000,
            'unit' => 'pcs',
            'status' => 'active',
        ]);

        $this->productB = SupplierProduct::create([
            'supplier_id' => $this->supplier->id,
            'sku' => 'BROCO-1212',
            'item_name' => 'Fitting Plafon Bulat Broco',
            'last_purchase_price' => 30000,
            'unit' => 'pcs',
            'status' => 'active',
        ]);
    }

    public function test_can_create_bundle_promo_with_items(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('bundle-promos.store'), [
            'bundle_code' => 'BDL-TEST-001',
            'name' => 'Paket Hemat Broco Fitting',
            'description' => 'Beli 2 Fitting Besar + 1 Fitting Bulat hemat Rp 15.000',
            'discount_type' => 'fixed',
            'discount_value' => 15000,
            'status' => 'active',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'qty' => 2,
                    'unit_price' => 20000,
                ],
                [
                    'product_id' => $this->productB->id,
                    'qty' => 1,
                    'unit_price' => 30000,
                ],
            ],
        ]);

        $response->assertRedirect(route('bundle-promos.index'));

        $this->assertDatabaseHas('bundle_promos', [
            'bundle_code' => 'BDL-TEST-001',
            'name' => 'Paket Hemat Broco Fitting',
            'original_price' => 70000.00, // (2 * 20000) + (1 * 30000)
            'bundle_price' => 55000.00,   // 70000 - 15000
            'status' => 'active',
        ]);

        $this->assertDatabaseCount('bundle_promo_items', 2);
    }

    public function test_can_create_percentage_discount_bundle_promo(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('bundle-promos.store'), [
            'bundle_code' => 'BDL-TEST-002',
            'name' => 'Paket Diskon 20% Broco',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'status' => 'active',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'qty' => 1,
                    'unit_price' => 50000,
                ],
            ],
        ]);

        $response->assertRedirect(route('bundle-promos.index'));

        $this->assertDatabaseHas('bundle_promos', [
            'bundle_code' => 'BDL-TEST-002',
            'original_price' => 50000.00,
            'bundle_price' => 40000.00, // 50000 - 20% (10000)
            'discount_type' => 'percentage',
            'discount_value' => 20.00,
        ]);
    }

    public function test_api_active_bundles_returns_active_promos_with_discounted_prices(): void
    {
        $bundle = BundlePromo::create([
            'id' => BundlePromo::generateId(),
            'bundle_code' => 'BDL-API-001',
            'name' => 'Paket Spesial API',
            'original_price' => 100000,
            'bundle_price' => 80000, // 20% off
            'discount_type' => 'fixed',
            'discount_value' => 20000,
            'status' => 'active',
        ]);

        BundlePromoItem::create([
            'bundle_promo_id' => $bundle->id,
            'supplier_product_id' => $this->productA->id,
            'qty' => 2,
            'unit_price' => 50000,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('bundle-promos.api.active'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'bundle_code',
                    'name',
                    'original_price',
                    'bundle_price',
                    'savings_amount',
                    'savings_percentage',
                    'items' => [
                        '*' => [
                            'product_id',
                            'product_name',
                            'qty',
                            'original_unit_price',
                            'bundle_unit_price',
                        ],
                    ],
                ],
            ],
        ]);

        $data = $response->json('data.0');
        $this->assertEquals('BDL-API-001', $data['bundle_code']);
        $this->assertEquals(80000, $data['bundle_price']);
        $this->assertEquals(40000, $data['items'][0]['bundle_unit_price']); // 50000 * 0.8
    }

    public function test_can_update_and_delete_bundle_promo(): void
    {
        $bundle = BundlePromo::create([
            'id' => BundlePromo::generateId(),
            'bundle_code' => 'BDL-UPDATE-001',
            'name' => 'Paket Awal',
            'original_price' => 20000,
            'bundle_price' => 15000,
            'discount_type' => 'fixed',
            'discount_value' => 5000,
            'status' => 'active',
        ]);

        BundlePromoItem::create([
            'bundle_promo_id' => $bundle->id,
            'supplier_product_id' => $this->productA->id,
            'qty' => 1,
            'unit_price' => 20000,
        ]);

        $updateResponse = $this->actingAs($this->adminUser)->put(route('bundle-promos.update', $bundle->id), [
            'bundle_code' => 'BDL-UPDATE-001',
            'name' => 'Paket Diperbarui',
            'discount_type' => 'fixed',
            'discount_value' => 8000,
            'status' => 'inactive',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'qty' => 1,
                    'unit_price' => 20000,
                ],
            ],
        ]);

        $updateResponse->assertRedirect(route('bundle-promos.index'));

        $this->assertDatabaseHas('bundle_promos', [
            'id' => $bundle->id,
            'name' => 'Paket Diperbarui',
            'bundle_price' => 12000.00, // 20000 - 8000
            'status' => 'inactive',
        ]);

        // Delete
        $deleteResponse = $this->actingAs($this->adminUser)->delete(route('bundle-promos.destroy', $bundle->id));
        $deleteResponse->assertRedirect(route('bundle-promos.index'));
        $this->assertDatabaseMissing('bundle_promos', ['id' => $bundle->id]);
    }
}
