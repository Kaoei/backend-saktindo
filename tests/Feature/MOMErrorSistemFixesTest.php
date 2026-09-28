<?php

namespace Tests\Feature;

use App\Models\InBound;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierPO;
use App\Models\SupplierProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MOMErrorSistemFixesTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);
    }

    /**
     * Test ERR-01: Save Product works when user only fills General Info tab
     */
    public function test_err_01_save_product_general_info_only(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('products.store'), [
            'product_name' => 'Produk Lampu LED Broco Test',
            'category' => 'INDOOR',
            'sub_category' => 'ACC (INDOOR)',
            'brand' => 'BROCO',
            'price' => 100000,
            'unit' => 'pcs',
            'description' => 'Tidak ada deskripsi',
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', [
            'product_name' => 'Produk Lampu LED Broco Test',
            'category' => 'INDOOR',
            'brand' => 'BROCO',
        ]);
    }

    /**
     * Test ERR-02: Supplier Product form accepts lead_time_days and minimum_order_qty
     */
    public function test_err_02_supplier_product_lead_time_works(): void
    {
        $supplier = Supplier::create([
            'id' => 'SUP-0001',
            'name' => 'Supplier Test Saktindo',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('suppliers.products.store'), [
            'supplier_id' => $supplier->id,
            'item_name' => 'Lampu Sorot LED 100W',
            'sku' => 'SKU-TEST-100W',
            'unit' => 'pcs',
            'last_purchase_price' => 75000,
            'minimum_order_qty' => 5,
            'lead_time_days' => 14,
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('supplier_products', [
            'item_name' => 'Lampu Sorot LED 100W',
            'lead_time_days' => 14,
            'minimum_order_qty' => 5,
        ]);
    }

    /**
     * Test ERR-03: Supplier PO invoice generation handles PO numbers with '/' without InvalidArgumentException
     */
    public function test_err_03_supplier_po_invoice_with_slash_filename(): void
    {
        $supplier = Supplier::create([
            'id' => 'SUP-0002',
            'name' => 'Supplier Sahabat Abadi',
            'status' => 'active',
        ]);

        $po = SupplierPO::create([
            'id' => 'SPO-000006',
            'po_number' => 'PO/SPO/2026/09/0006',
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'status' => 'received',
            'total_amount' => 500000,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('supplier-po.invoice', $po->id));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $disposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('Faktur-PO-SPO-2026-09-0006.pdf', $disposition);
    }

    /**
     * Test ERR-04: Inbound delete handles string ID and already deleted records gracefully without 404
     */
    public function test_err_04_inbound_delete_success_and_already_deleted_handling(): void
    {
        $supplier = Supplier::create([
            'id' => 'SUP-0003',
            'name' => 'Supplier Gudang',
            'status' => 'active',
        ]);

        $supplierProduct = SupplierProduct::create([
            'id' => 'SP-0001',
            'supplier_id' => $supplier->id,
            'item_name' => 'LED Flood Light 50W',
            'sku' => 'LED-FLOOD-50W',
            'status' => 'active',
        ]);

        $inbound = InBound::create([
            'id' => 'INB-000261',
            'supplier_id' => $supplier->id,
            'supplier_product_id' => $supplierProduct->id,
            'qty_received' => 1,
            'received_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        // 1. First delete: should succeed
        $deleteResponse = $this->actingAs($this->superAdmin)->delete(route('inbound.destroy', $inbound->id));
        $deleteResponse->assertRedirect(route('inbound.index'));
        $deleteResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('in_bounds', ['id' => 'INB-000261']);

        // 2. Second delete (already deleted): should redirect gracefully instead of 404
        $repeatDelete = $this->actingAs($this->superAdmin)->delete(route('inbound.destroy', 'INB-000261'));
        $repeatDelete->assertRedirect(route('inbound.index'));
        $repeatDelete->assertSessionHas('info');
    }
}
