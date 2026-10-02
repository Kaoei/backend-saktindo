<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Supplier;
use App\Models\SupplierPO;
use App\Models\SupplierPOItem;
use App\Models\SupplierProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierPODiscountAndTaxTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Supplier $supplier;
    protected SupplierProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->supplier = Supplier::create([
            'id' => 'SUP-000001',
            'name' => 'PT Test Supplier Indonesia',
        ]);

        $this->product = SupplierProduct::create([
            'id' => 'SP-000001',
            'supplier_id' => $this->supplier->id,
            'item_name' => 'LED Panel 18W Square',
            'sku' => 'LED-PANEL-18W',
            'last_purchase_price' => 100000,
            'unit' => 'pcs',
        ]);
    }

    public function test_can_create_po_with_nominal_additional_discount_and_non_pajak(): void
    {
        // Item: 2 pcs @ 100.000 = 200.000
        // Additional Discount: 20.000
        // Non Pajak -> tax = 0
        // Expected Grand Total = 180.000
        $response = $this->actingAs($this->admin)->post(route('supplier-po.store'), [
            'supplier_id' => $this->supplier->id,
            'order_date' => now()->toDateString(),
            'reference_number' => 'REF-TEST-001',
            'additional_discount' => 20000,
            'tax_type' => 'non_pajak',
            'tax_amount' => 0,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'qty' => 2,
                    'price' => 100000,
                    'discount_1' => 0,
                    'discount_2' => 0,
                    'discount_3' => 0,
                    'discount_4' => 0,
                ],
            ],
        ]);

        $response->assertRedirect(route('supplier-po.index'));

        $po = SupplierPO::latest('created_at')->first();
        $this->assertNotNull($po);
        $this->assertEquals(200000, (float) $po->subtotal);
        $this->assertEquals(20000, (float) $po->additional_discount);
        $this->assertEquals('non_pajak', $po->tax_type);
        $this->assertEquals(0, (float) $po->tax_amount);
        $this->assertEquals(180000, (float) $po->total_amount);
        $this->assertEquals(180000, $po->dpp);
    }

    public function test_can_create_po_with_nominal_discount_and_manual_tax_input(): void
    {
        // Item: 1 pcs @ 1.000.000 = 1.000.000
        // Additional Discount: 100.000 -> DPP = 900.000
        // Manual Tax Input: 99.000 (PPN 11% or any custom manual tax)
        // Grand Total = 999.000
        $response = $this->actingAs($this->admin)->post(route('supplier-po.store'), [
            'supplier_id' => $this->supplier->id,
            'order_date' => now()->toDateString(),
            'reference_number' => 'REF-TAX-002',
            'additional_discount' => 100000,
            'tax_type' => 'pajak',
            'tax_amount' => 99000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'qty' => 1,
                    'price' => 1000000,
                    'discount_1' => 0,
                    'discount_2' => 0,
                    'discount_3' => 0,
                    'discount_4' => 0,
                ],
            ],
        ]);

        $response->assertRedirect(route('supplier-po.index'));

        $po = SupplierPO::latest('created_at')->first();
        $this->assertNotNull($po);
        $this->assertEquals(1000000, (float) $po->subtotal);
        $this->assertEquals(100000, (float) $po->additional_discount);
        $this->assertEquals('pajak', $po->tax_type);
        $this->assertEquals(99000, (float) $po->tax_amount);
        $this->assertEquals(999000, (float) $po->total_amount);
        $this->assertEquals(900000, $po->dpp);
    }

    public function test_can_update_po_with_discount_and_tax(): void
    {
        $po = SupplierPO::create([
            'id' => 'SPO-000099',
            'supplier_id' => $this->supplier->id,
            'po_number' => 'P-26/10/0099',
            'order_date' => now()->toDateString(),
            'subtotal' => 500000,
            'additional_discount' => 0,
            'tax_type' => 'non_pajak',
            'tax_amount' => 0,
            'total_amount' => 500000,
            'status' => 'pending',
        ]);

        SupplierPOItem::create([
            'supplier_po_id' => $po->id,
            'supplier_product_id' => $this->product->id,
            'qty' => 5,
            'price' => 100000,
        ]);

        // Update to add 50.000 discount and 49.500 tax
        $response = $this->actingAs($this->admin)->put(route('supplier-po.update', $po->id), [
            'supplier_id' => $this->supplier->id,
            'order_date' => now()->toDateString(),
            'reference_number' => 'REF-UPDATED',
            'status' => 'received',
            'additional_discount' => 50000,
            'tax_type' => 'pajak',
            'tax_amount' => 49500,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'qty' => 5,
                    'price' => 100000,
                    'discount_1' => 0,
                    'discount_2' => 0,
                    'discount_3' => 0,
                    'discount_4' => 0,
                ],
            ],
        ]);

        $response->assertRedirect(route('supplier-po.index'));

        $po->refresh();
        $this->assertEquals(500000, (float) $po->subtotal);
        $this->assertEquals(50000, (float) $po->additional_discount);
        $this->assertEquals('pajak', $po->tax_type);
        $this->assertEquals(49500, (float) $po->tax_amount);
        $this->assertEquals(499500, (float) $po->total_amount);
        $this->assertEquals('received', $po->status);

        // Verify show, edit and invoice pages render correctly
        $this->actingAs($this->admin)->get(route('supplier-po.show', $po->id))
            ->assertStatus(200)
            ->assertSee('Diskon Tambahan')
            ->assertSee('Pajak (PPN)');

        $this->actingAs($this->admin)->get(route('supplier-po.edit', $po->id))
            ->assertStatus(200)
            ->assertSee('Diskon Tambahan (Nominal / Rp)')
            ->assertSee('Pilihan Pajak');

        $this->actingAs($this->admin)->get(route('supplier-po.invoice', $po->id))
            ->assertStatus(200);
    }
}
