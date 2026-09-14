<?php

namespace Tests\Feature;

use App\Models\InBound;
use App\Models\Master_customer;
use App\Models\Rak;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotulenBugFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_action_button_on_inbound_list_opens_create_page_without_error(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $supplier = Supplier::create([
            'name' => 'Test Supplier',
            'status' => 'active',
        ]);

        $product = SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'sku' => 'SKU-TEST-001',
            'item_name' => 'Barang Test Inbound',
            'status' => 'active',
        ]);

        $inbound = InBound::create([
            'id' => 'INB-TEST-001',
            'supplier_id' => $supplier->id,
            'supplier_product_id' => $product->id,
            'qty_received' => 10,
            'received_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        Rak::create([
            'rak_kode' => 'RAK-01',
            'location' => 'Gudang Utama',
            'gudang' => 'js',
        ]);

        $response = $this->actingAs($user)
            ->get(route('gudang-product.create', ['inbound_id' => $inbound->id]));

        $response->assertStatus(200);
        $response->assertSee('Simpan Barang ke Rak');
    }

    public function test_order_and_invoice_page_loads_without_error(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $customer = Master_customer::create([
            'id' => 'CUST-001',
            'nama_customer' => 'Customer Test',
            'nama_pic' => 'PIC Test',
            'nomor_hp' => '08123456789',
            'email' => 'customer@test.com',
            'alamat' => 'Alamat Test',
            'kota' => 'Jakarta',
        ]);

        $response = $this->actingAs($user)
            ->get(route('sales-finance.index'));

        $response->assertStatus(200);
        $response->assertSee('Customer Test');
    }
}
