<?php

namespace Tests\Feature;

use App\Models\Master_customer;
use App\Models\User;
use App\Models\Variant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CustomerAndVariationBugFixesTest extends TestCase
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

    public function test_master_customer_can_be_created_with_empty_alamat_and_kota(): void
    {
        $this->authenticateSuperAdmin();

        $response = $this->post(route('master-customer.store'), [
            'nama_customer' => 'PT Customer Tanpa Alamat',
            'nama_pic' => 'Budi Santoso',
            'nomor_hp' => '08123456789',
            'email' => 'tanpa_alamat@example.com',
            'alamat' => '',
            'kota' => '',
            'tipe_customer' => 'perusahaan',
            'termin' => 30,
            'limit_piutang' => 5000000,
        ]);

        $response->assertRedirect(route('master-customer.index'));
        $response->assertSessionHas('status', 'Customer berhasil ditambahkan');

        $this->assertDatabaseHas('master_customers', [
            'nama_customer' => 'PT Customer Tanpa Alamat',
            'nama_pic' => 'Budi Santoso',
        ]);
    }

    public function test_master_customer_manual_duplicate_triggers_friendly_validation_error(): void
    {
        $this->authenticateSuperAdmin();

        Master_customer::create([
            'id' => 'MCS-00001',
            'nama_customer' => 'PT Duplikat Test',
            'nama_pic' => 'PIC 1',
            'nomor_hp' => '0811111111',
            'email' => 'duplikat@example.com',
            'alamat' => null,
            'kota' => null,
            'tipe_customer' => 'perusahaan',
            'termin' => 0,
            'limit_piutang' => 0,
        ]);

        $response = $this->from(route('master-customer.create'))->post(route('master-customer.store'), [
            'nama_customer' => 'PT Duplikat Test',
            'nama_pic' => 'PIC 2',
            'nomor_hp' => '0822222222',
            'alamat' => '',
            'kota' => '',
        ]);

        $response->assertRedirect(route('master-customer.create'));
        $response->assertSessionHasErrors(['nama_customer']);
    }

    public function test_master_customer_import_prevents_duplicate_records(): void
    {
        $this->authenticateSuperAdmin();

        // Create initial customer
        Master_customer::create([
            'id' => 'MCS-00010',
            'nama_customer' => 'PT Pelita Jaya',
            'nama_pic' => 'Ahmad',
            'nomor_hp' => '081234567890',
            'email' => 'pelita@example.com',
            'alamat' => 'Jl. Lama No. 1',
            'kota' => 'Jakarta',
            'tipe_customer' => 'perusahaan',
            'termin' => 14,
            'limit_piutang' => 1000000,
        ]);

        $csvContent = "nama_customer,nama_pic,nomor_hp,email,alamat,kota,npwp,tipe_customer,termin,limit_piutang\n" .
                      "PT Pelita Jaya,Ahmad Updated,081234567890,pelita_new@example.com,Jl. Baru No. 2,Bandung,12345,perusahaan,30,2000000\n" .
                      "CV Baru Masuk,Siti,0899999999,baru@example.com,Jl. Anyar,Surabaya,,perorangan,0,500000\n";

        $file = UploadedFile::fake()->createWithContent('customers.csv', $csvContent);

        $response = $this->post(route('master-customer.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('master-customer.index'));

        // PT Pelita Jaya should NOT be duplicated, but updated
        $this->assertEquals(1, Master_customer::where('nama_customer', 'PT Pelita Jaya')->count());
        $this->assertDatabaseHas('master_customers', [
            'nama_customer' => 'PT Pelita Jaya',
            'nama_pic' => 'Ahmad Updated',
            'kota' => 'Bandung',
        ]);

        // CV Baru Masuk should be created
        $this->assertDatabaseHas('master_customers', [
            'nama_customer' => 'CV Baru Masuk',
            'nama_pic' => 'Siti',
        ]);
    }

    public function test_product_store_with_variations_persists_variants(): void
    {
        $this->authenticateSuperAdmin();

        $response = $this->post(route('products.store'), [
            'item_name' => 'Saklar Lampu Varian Test',
            'sku' => 'SKU-VAR-001',
            'brand' => 'Broco',
            'category' => 'Elektrik',
            'price' => 25000,
            'variations' => [
                ['variation_value' => 'Warna Putih', 'price' => 25000, 'quantity' => 10],
                ['variation_value' => 'Warna Hitam', 'price' => 27000, 'quantity' => 5],
            ]
        ]);

        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('variants', [
            'name' => 'Warna Putih'
        ]);
        $this->assertDatabaseHas('variants', [
            'name' => 'Warna Hitam'
        ]);
    }

    public function test_approved_or_invoiced_sales_order_cannot_be_rechecked_or_edited(): void
    {
        $this->authenticateSuperAdmin();

        $so = \App\Models\SalesOrder::create([
            'id' => 'SO-20261014-0099',
            'customer_name' => 'PT Approved Customer',
            'customer_po_number' => 'PO-CUST-APP-01',
            'order_date' => now(),
            'sales_type' => 'js',
            'order_status' => 'invoiced',
            'stock_status' => 'available',
            'subtotal' => 100000,
            'grand_total' => 100000,
        ]);

        // Trying to re-check stock should be rejected
        $response = $this->post(route('sales-finance.stock-check', $so));
        $response->assertSessionHas('error');
        $so->refresh();
        $this->assertEquals('invoiced', $so->order_status);

        $supplier = \App\Models\Supplier::create([
            'id' => 'SUP-TEST-SO',
            'name' => 'Supplier SO Test',
            'phone' => '081111',
            'email' => 'supso@example.com',
            'address' => 'Jakarta',
            'payment_terms' => '30_days',
            'status' => 'active',
        ]);

        $sp = \App\Models\SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'item_name' => 'Produk A',
            'sku' => 'SKU-A',
            'unit' => 'pcs',
            'last_purchase_price' => 100000,
            'status' => 'active',
        ]);

        // Trying to update order status back to draft or stock_check should be rejected
        $updateResponse = $this->put(route('sales-finance.update', $so), [
            'customer_name' => 'PT Approved Customer',
            'customer_po_number' => 'PO-CUST-APP-01',
            'sales_type' => 'js',
            'order_status' => 'draft',
            'order_date' => now()->toDateString(),
            'items' => [
                [
                    'product_code' => $sp->id,
                    'product_name' => 'Produk A',
                    'quantity' => 1,
                    'unit' => 'pcs',
                    'unit_price' => 100000,
                ]
            ]
        ]);
        $updateResponse->assertSessionHas('error');
        $so->refresh();
        $this->assertEquals('invoiced', $so->order_status);
    }

    public function test_received_supplier_po_cannot_be_reverted_to_pending(): void
    {
        $this->authenticateSuperAdmin();

        $supplier = \App\Models\Supplier::create([
            'id' => 'SUP-TEST-001',
            'name' => 'PT Supplier Utama',
            'phone' => '0812345678',
            'email' => 'sup@example.com',
            'address' => 'Jakarta',
            'payment_terms' => '30_days',
            'status' => 'active',
        ]);

        $po = \App\Models\SupplierPO::create([
            'id' => 'SPO-000099',
            'supplier_id' => $supplier->id,
            'po_number' => 'P-26/10/0099',
            'order_date' => now(),
            'subtotal' => 50000,
            'total_amount' => 50000,
            'status' => 'received',
        ]);

        // Trying to submit update with pending status should be rejected
        $updateResponse = $this->put(route('supplier-po.update', $po->id), [
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'items' => [
                ['product_id' => 'NON-EXISTENT', 'qty' => 1, 'price' => 50000]
            ]
        ]);
        $updateResponse->assertSessionHas('error');

        $po->refresh();
        $this->assertEquals('received', $po->status);
    }
}

