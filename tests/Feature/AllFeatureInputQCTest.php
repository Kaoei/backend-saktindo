<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\BundlePromo;
use App\Models\Category;
use App\Models\DeliveryNote;
use App\Models\GudangProduct;
use App\Models\InBound;
use App\Models\InternalInvoice;
use App\Models\Invoice;
use App\Models\Master_customer;
use App\Models\OutBound;
use App\Models\Rak;
use App\Models\RekeningBank;
use App\Models\Retur;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\SubCategory;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Models\SupplierPaymentTerm;
use App\Models\SupplierPO;
use App\Models\SupplierProduct;
use App\Models\SupplierPurchaseHistory;
use App\Models\User;
use App\Models\Variant;
use App\Models\WarehouseTask;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AllFeatureInputQCTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->superAdmin = User::factory()->create([
            'email' => 'superadmin.qc@saktindo.com',
            'role' => User::ROLE_SUPER_ADMIN,
            'password' => Hash::make('password123'),
        ]);

        Storage::fake('public');
    }

    public function test_user_management_inputs_validation_and_persistence(): void
    {
        // 1. Valid user creation
        $createResponse = $this->actingAs($this->superAdmin)->post(route('users.store'), [
            'name' => 'QC Test User',
            'email' => 'qctest@saktindo.com',
            'role' => User::ROLE_SALES,
            'group' => 'Sales Tim A',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ]);
        $createResponse->assertRedirect(route('users.index'));
        $createResponse->assertSessionHas('status');
        $this->assertDatabaseHas('users', ['email' => 'qctest@saktindo.com', 'name' => 'QC Test User']);

        $user = User::where('email', 'qctest@saktindo.com')->firstOrFail();

        // 2. User update
        $updateResponse = $this->actingAs($this->superAdmin)->put(route('users.update', $user), [
            'name' => 'QC Test User Updated',
            'email' => 'qctest.updated@saktindo.com',
            'role' => User::ROLE_FINANCE,
            'group' => 'Finance Tim B',
            'password' => '',
            'password_confirmation' => '',
        ]);
        $updateResponse->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['email' => 'qctest.updated@saktindo.com', 'name' => 'QC Test User Updated']);

        // 3. User update password
        $pwResponse = $this->actingAs($this->superAdmin)->put(route('users.update-password', $user), [
            'password' => 'newpassword99',
            'password_confirmation' => 'newpassword99',
        ]);
        $pwResponse->assertRedirect(route('users.index'));

        // 4. Invalid input handling (short password, bad email, invalid role)
        $invalidResponse = $this->actingAs($this->superAdmin)->post(route('users.store'), [
            'name' => '',
            'email' => 'not-an-email',
            'role' => 'invalid_role_xyz',
            'password' => '123',
            'password_confirmation' => 'mismatch',
        ]);
        $invalidResponse->assertSessionHasErrors(['name', 'email', 'role', 'password']);
    }

    public function test_role_management_inputs_validation_and_persistence(): void
    {
        $createResponse = $this->actingAs($this->superAdmin)->post(route('roles.store'), [
            'name' => 'Supervisor QC',
            'description' => 'Role untuk pengujian QC',
            'permissions' => ['dashboard', 'suppliers.view', 'rak.view'],
        ]);
        $createResponse->assertRedirect(route('roles.index'));
        $this->assertDatabaseHas('roles', ['name' => 'Supervisor QC']);

        $role = Role::where('name', 'Supervisor QC')->firstOrFail();

        $updateResponse = $this->actingAs($this->superAdmin)->put(route('roles.update', $role), [
            'name' => 'Supervisor QC Updated',
            'description' => 'Updated description',
            'permissions' => ['dashboard', 'users.view'],
        ]);
        $updateResponse->assertRedirect(route('roles.index'));
        $this->assertDatabaseHas('roles', ['name' => 'Supervisor QC Updated']);

        // Duplicate role validation
        $duplicateResponse = $this->actingAs($this->superAdmin)->post(route('roles.store'), [
            'name' => 'Supervisor QC Updated',
        ]);
        $duplicateResponse->assertSessionHasErrors(['name']);
    }

    public function test_master_customer_inputs_validation_and_persistence(): void
    {
        // 1. Create with full details
        $res = $this->actingAs($this->superAdmin)->post(route('master-customer.store'), [
            'nama_customer' => 'PT Alpha Solusindo',
            'nama_pic' => 'Budi Santoso',
            'nomor_hp' => '081234567890',
            'email' => 'budi@alphasolusindo.com',
            'alamat' => 'Jl. Industri No. 45',
            'kota' => 'Surabaya',
            'npwp' => '01.234.567.8-012.000',
            'tipe_customer' => 'perusahaan',
            'termin' => 30,
            'limit_piutang' => 50000000,
        ]);
        $res->assertRedirect(route('master-customer.index'));
        $this->assertDatabaseHas('master_customers', ['nama_customer' => 'PT Alpha Solusindo']);

        $customer = Master_customer::where('nama_customer', 'PT Alpha Solusindo')->firstOrFail();

        // 2. Update customer
        $updRes = $this->actingAs($this->superAdmin)->put(route('master-customer.update', $customer->id), [
            'nama_customer' => 'PT Alpha Solusindo Perkasa',
            'nama_pic' => 'Budi S. Gunawan',
            'nomor_hp' => '081234567899',
            'email' => 'budig@alphasolusindo.com',
            'alamat' => 'Jl. Industri Baru No. 88',
            'kota' => 'Sidoarjo',
            'npwp' => '01.234.567.8-012.000',
            'tipe_customer' => 'perusahaan',
            'termin' => 45,
            'limit_piutang' => 75000000,
        ]);
        $updRes->assertRedirect(route('master-customer.index'));
        $this->assertDatabaseHas('master_customers', ['nama_customer' => 'PT Alpha Solusindo Perkasa']);

        // 3. Validation rejection on missing required fields
        $invalidRes = $this->actingAs($this->superAdmin)->post(route('master-customer.store'), [
            'nama_customer' => '',
            'nama_pic' => '',
        ]);
        $invalidRes->assertSessionHasErrors(['nama_customer', 'nama_pic']);
    }

    public function test_supplier_and_sub_entities_inputs(): void
    {
        // 1. Supplier Store
        $supRes = $this->actingAs($this->superAdmin)->post(route('suppliers.store'), [
            'name' => 'PT Sumber Cahaya Abadi',
            'email' => 'info@sumbercahaya.com',
            'phone' => '021-5551234',
            'address' => 'Kawasan Industri Pulogadung',
            'payment_due_days' => 14,
            'status' => 'active',
        ]);
        $supplier = Supplier::where('name', 'PT Sumber Cahaya Abadi')->firstOrFail();
        $supRes->assertRedirect(route('suppliers.show', $supplier));

        // 2. Supplier Update
        $this->actingAs($this->superAdmin)->put(route('suppliers.update', $supplier), [
            'name' => 'PT Sumber Cahaya Abadi Sukses',
            'email' => 'info@sumbercahaya.com',
            'phone' => '021-5551235',
            'address' => 'Kawasan Industri Pulogadung Blok B',
            'payment_due_days' => 30,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('suppliers', ['name' => 'PT Sumber Cahaya Abadi Sukses']);

        // 3. Supplier Contact Store & Update
        $contactRes = $this->actingAs($this->superAdmin)->post(route('suppliers.contacts.store'), [
            'supplier_id' => $supplier->id,
            'name' => 'Hendra Setiawan',
            'role' => 'Sales Manager',
            'email' => 'hendra@sumbercahaya.com',
            'phone' => '081299998888',
            'is_primary' => true,
        ]);
        $contact = SupplierContact::where('name', 'Hendra Setiawan')->firstOrFail();
        $contactRes->assertRedirect(route('suppliers.show', $supplier->id));

        $this->actingAs($this->superAdmin)->put(route('suppliers.contacts.update', $contact), [
            'supplier_id' => $supplier->id,
            'name' => 'Hendra Setiawan S.T.',
            'role' => 'Senior Sales Manager',
            'email' => 'hendra.st@sumbercahaya.com',
            'phone' => '081299998888',
            'is_primary' => true,
        ]);
        $this->assertDatabaseHas('supplier_contacts', ['name' => 'Hendra Setiawan S.T.']);

        // 4. Supplier Product Store & Update
        $spRes = $this->actingAs($this->superAdmin)->post(route('suppliers.products.store'), [
            'supplier_id' => $supplier->id,
            'sku' => 'SKU-LED-100W',
            'part_number' => 'PN-LED-100',
            'item_name' => 'Lampu LED High Bay 100W',
            'category' => 'Lighting',
            'sub_category' => 'High Bay',
            'brand' => 'Philips',
            'unit' => 'pcs',
            'last_purchase_price' => 250000,
            'minimum_order_qty' => 5,
            'lead_time_days' => 3,
            'status' => 'active',
        ]);
        $spProduct = SupplierProduct::where('item_name', 'Lampu LED High Bay 100W')->firstOrFail();
        $spRes->assertRedirect(route('suppliers.show', $supplier->id));

        $this->actingAs($this->superAdmin)->put(route('suppliers.products.update', $spProduct), [
            'supplier_id' => $supplier->id,
            'sku' => 'SKU-LED-100W',
            'part_number' => 'PN-LED-100-PRO',
            'item_name' => 'Lampu LED High Bay 100W Pro',
            'category' => 'Lighting',
            'sub_category' => 'High Bay',
            'brand' => 'Philips',
            'unit' => 'pcs',
            'last_purchase_price' => 260000,
            'minimum_order_qty' => 10,
            'lead_time_days' => 4,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('supplier_products', ['item_name' => 'Lampu LED High Bay 100W Pro']);

        // 5. Supplier Payment Term Store & Update
        $ptRes = $this->actingAs($this->superAdmin)->post(route('suppliers.payment-terms.store'), [
            'supplier_id' => $supplier->id,
            'name' => 'Net 30 DP 20%',
            'due_days' => 30,
            'down_payment_percent' => 20,
            'late_fee_percent' => 1,
            'is_default' => true,
        ]);
        $paymentTerm = SupplierPaymentTerm::where('name', 'Net 30 DP 20%')->firstOrFail();
        $ptRes->assertRedirect(route('suppliers.show', $supplier->id));

        $this->actingAs($this->superAdmin)->put(route('suppliers.payment-terms.update', $paymentTerm), [
            'supplier_id' => $supplier->id,
            'name' => 'Net 45 DP 10%',
            'due_days' => 45,
            'down_payment_percent' => 10,
            'late_fee_percent' => 1.5,
            'is_default' => false,
        ]);
        $this->assertDatabaseHas('supplier_payment_terms', ['name' => 'Net 45 DP 10%']);

        // 6. Supplier Purchase History Store & Update
        $phRes = $this->actingAs($this->superAdmin)->post(route('suppliers.purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'invoice_number' => 'INV-SUP-001',
            'item_name' => 'Lampu LED High Bay 100W Pro',
            'quantity' => 20,
            'unit_price' => 250000,
            'total_amount' => 5000000,
            'status' => 'received',
            'notes' => 'Pembelian langsung',
        ]);
        $ph = SupplierPurchaseHistory::where('invoice_number', 'INV-SUP-001')->firstOrFail();
        $phRes->assertRedirect(route('suppliers.show', $supplier->id));

        $this->actingAs($this->superAdmin)->put(route('suppliers.purchases.update', $ph), [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'invoice_number' => 'INV-SUP-001-REV',
            'item_name' => 'Lampu LED High Bay 100W Pro',
            'quantity' => 20,
            'unit_price' => 240000,
            'total_amount' => 4800000,
            'status' => 'received',
            'notes' => 'Diskon harga negosiasi',
        ]);
        $this->assertDatabaseHas('supplier_purchase_histories', ['invoice_number' => 'INV-SUP-001-REV']);
    }

    public function test_supplier_po_input_creation_and_update(): void
    {
        $supplier = Supplier::create(['name' => 'Supplier PO Test', 'status' => 'active']);
        $product = SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'sku' => 'SKU-PO-TEST-1',
            'item_name' => 'Solar Panel 200W Monocrystalline',
            'last_purchase_price' => 1200000,
            'status' => 'active',
        ]);

        // 1. Create Supplier PO
        $poRes = $this->actingAs($this->superAdmin)->post(route('supplier-po.store'), [
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'reference_number' => 'REF-PO-2026-001',
            'notes' => 'Pengiriman via truk ekspedisi',
            'additional_discount' => 50000,
            'tax_type' => 'pajak',
            'tax_amount' => 126500,
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 5,
                    'price' => 1200000,
                    'discount_1' => 10,
                    'discount_2' => 5,
                    'discount_3' => 0,
                    'discount_4' => 0,
                ],
            ],
        ]);
        $poRes->assertRedirect(route('supplier-po.index'));
        $this->assertDatabaseHas('supplier_pos', ['reference_number' => 'REF-PO-2026-001']);

        $po = SupplierPO::where('reference_number', 'REF-PO-2026-001')->firstOrFail();
        $this->assertEquals('pending', $po->status);

        // 2. Update Supplier PO
        $poUpdRes = $this->actingAs($this->superAdmin)->put(route('supplier-po.update', $po->id), [
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'reference_number' => 'REF-PO-2026-001-REVISED',
            'notes' => 'Catatan revisi spesifikasi',
            'additional_discount' => 60000,
            'tax_type' => 'non_pajak',
            'tax_amount' => 0,
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 6,
                    'price' => 1200000,
                    'discount_1' => 12,
                    'discount_2' => 0,
                    'discount_3' => 0,
                    'discount_4' => 0,
                ],
            ],
        ]);
        $poUpdRes->assertRedirect(route('supplier-po.index'));
        $this->assertDatabaseHas('supplier_pos', ['reference_number' => 'REF-PO-2026-001-REVISED']);

        // 3. Create from shortage route
        $shortageRes = $this->actingAs($this->superAdmin)->post(route('supplier-po.create-from-shortage'), [
            'shortage_items' => [
                [
                    'product_code' => $product->sku,
                    'product_name' => $product->item_name,
                    'qty' => 15,
                    'unit' => 'pcs',
                    'price' => 1200000,
                ],
            ],
        ]);
        $shortageRes->assertStatus(200);
        $shortageRes->assertViewIs('supplier_po.create');
    }

    public function test_master_barang_brands_categories_and_products_inputs(): void
    {
        // 1. Brand Store & Update
        $bRes = $this->actingAs($this->superAdmin)->post(route('brands.store'), ['name' => 'Brand Master QC']);
        $bRes->assertRedirect(route('brands.index'));
        $brand = Brand::where('name', 'Brand Master QC')->firstOrFail();

        $this->actingAs($this->superAdmin)->put(route('brands.update', $brand), ['name' => 'Brand Master QC Updated']);
        $this->assertDatabaseHas('brands', ['name' => 'Brand Master QC Updated']);

        // 2. Category Store & Update
        $cRes = $this->actingAs($this->superAdmin)->post(route('categories.store'), ['name' => 'Category QC']);
        $cRes->assertRedirect(route('categories.index'));
        $category = Category::where('name', 'Category QC')->firstOrFail();

        $this->actingAs($this->superAdmin)->put(route('categories.update', $category), ['name' => 'Category QC Updated']);
        $this->assertDatabaseHas('categories', ['name' => 'Category QC Updated']);

        // 3. SubCategory Store & Update
        $scRes = $this->actingAs($this->superAdmin)->post(route('sub-categories.store'), [
            'category_id' => $category->id,
            'name' => 'SubCat QC 1',
        ]);
        $scRes->assertRedirect(route('sub-categories.index'));
        $subCategory = SubCategory::where('name', 'SubCat QC 1')->firstOrFail();

        $this->actingAs($this->superAdmin)->put(route('sub-categories.update', $subCategory), [
            'category_id' => $category->id,
            'name' => 'SubCat QC 1 Updated',
        ]);
        $this->assertDatabaseHas('sub_categories', ['name' => 'SubCat QC 1 Updated']);

        // 4. Variant Store & Update
        $vRes = $this->actingAs($this->superAdmin)->post(route('variants.store'), ['name' => 'Warna Merah QC']);
        $vRes->assertRedirect(route('variants.index'));
        $variant = Variant::where('name', 'Warna Merah QC')->firstOrFail();

        $this->actingAs($this->superAdmin)->put(route('variants.update', $variant), ['name' => 'Warna Merah QC Pro']);
        $this->assertDatabaseHas('variants', ['name' => 'Warna Merah QC Pro']);

        // 5. Rak Store & Update
        $rakRes = $this->actingAs($this->superAdmin)->post(route('rak.store'), [
            'rak_kode' => 'RAK-QC-01',
            'location' => 'Lantai 1 Lorong A',
            'gudang' => 'js',
        ]);
        $rakRes->assertRedirect(route('rak.index'));
        $this->assertDatabaseHas('raks', ['rak_kode' => 'RAK-QC-01']);

        $this->actingAs($this->superAdmin)->put(route('rak.update', 'RAK-QC-01'), [
            'rak_kode' => 'RAK-QC-01',
            'location' => 'Lantai 1 Lorong A Depan',
            'gudang' => 'sjb',
        ]);
        $this->assertDatabaseHas('raks', ['rak_kode' => 'RAK-QC-01', 'location' => 'Lantai 1 Lorong A Depan']);

        // 6. Product Store (with variations & initial stock)
        $prodRes = $this->actingAs($this->superAdmin)->post(route('products.store'), [
            'item_name' => 'Kabel Listrik NYA 2.5mm',
            'sku' => 'SKU-KBL-25',
            'brand' => 'Brand Master QC Updated',
            'category' => 'Category QC Updated',
            'sub_category' => 'SubCat QC 1 Updated',
            'price' => 350000,
            'unit' => 'roll',
            'initial_qty' => 20,
            'rack_id' => 'RAK-QC-01',
            'gudang_type' => 'JS',
            'variations' => [
                ['variation_name' => 'Warna', 'variation_value' => 'Warna Merah QC Pro'],
            ],
        ]);
        $prodRes->assertRedirect(route('products.index'));
        $product = SupplierProduct::where('item_name', 'Kabel Listrik NYA 2.5mm')->firstOrFail();
        $this->assertDatabaseHas('gudang_products', [
            'supplier_product_id' => $product->id,
            'rack_id' => 'RAK-QC-01',
            'qty' => 20,
        ]);

        // 7. Product Update
        $prodUpdRes = $this->actingAs($this->superAdmin)->put(route('products.update', $product->id), [
            'item_name' => 'Kabel Listrik NYA 2.5mm SNI',
            'sku' => 'SKU-KBL-25',
            'brand' => 'Brand Master QC Updated',
            'category' => 'Category QC Updated',
            'sub_category' => 'SubCat QC 1 Updated',
            'price' => 360000,
            'unit' => 'roll',
            'stock_allocations' => [
                [
                    'rack_id' => 'RAK-QC-01',
                    'qty' => 25,
                ],
            ],
        ]);
        $prodUpdRes->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('supplier_products', ['item_name' => 'Kabel Listrik NYA 2.5mm SNI']);
    }

    public function test_inbound_and_gudang_stock_inputs(): void
    {
        $supplier = Supplier::create(['name' => 'Supplier Inbound QC', 'status' => 'active']);
        $product = SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'sku' => 'SKU-INB-QC',
            'item_name' => 'Fitting Lampu E27 Keramik',
            'last_purchase_price' => 15000,
            'status' => 'active',
        ]);
        $rak1 = Rak::create(['rak_kode' => 'RAK-INB-1', 'location' => 'Zona 1', 'gudang' => 'js']);
        $rak2 = Rak::create(['rak_kode' => 'RAK-INB-2', 'location' => 'Zona 2', 'gudang' => 'js']);

        // 1. Inbound Store single item with rack placement
        $inbRes = $this->actingAs($this->superAdmin)->post(route('inbound.store'), [
            'supplier_id' => $supplier->id,
            'supplier_product_id' => $product->id,
            'qty_received' => 100,
            'qty_damaged' => 2,
            'qty_missing' => 0,
            'hpp' => 15000,
            'invoice_number' => 'INV-INB-100',
            'received_date' => now()->toDateString(),
            'rack_id' => $rak1->rak_kode,
            'notes' => 'Penerimaan batch 1',
        ]);
        $inbRes->assertRedirect(route('inbound.index'));
        $inbound = InBound::where('invoice_number', 'INV-INB-100')->firstOrFail();
        $this->assertEquals('stored', $inbound->status);
        $this->assertDatabaseHas('gudang_products', [
            'supplier_product_id' => $product->id,
            'rack_id' => $rak1->rak_kode,
            'qty' => 100,
        ]);

        // 2. Inbound Update
        $inbUpdRes = $this->actingAs($this->superAdmin)->put(route('inbound.update', $inbound->id), [
            'supplier_id' => $supplier->id,
            'supplier_product_id' => $product->id,
            'qty_received' => 110,
            'qty_damaged' => 2,
            'qty_missing' => 0,
            'hpp' => 15000,
            'invoice_number' => 'INV-INB-100-REV',
            'received_date' => now()->toDateString(),
            'notes' => 'Koreksi penambahan 10 pcs',
        ]);
        $inbUpdRes->assertRedirect(route('inbound.index'));
        $this->assertDatabaseHas('gudang_products', [
            'supplier_product_id' => $product->id,
            'rack_id' => $rak1->rak_kode,
            'qty' => 110,
        ]);

        // 3. Gudang Product split/transfer rack
        $gp = GudangProduct::where('supplier_product_id', $product->id)->where('rack_id', $rak1->rak_kode)->firstOrFail();
        $splitRes = $this->actingAs($this->superAdmin)->post(route('gudang-product.splitRack'), [
            'source_id' => $gp->id,
            'target_rack_id' => $rak2->rak_kode,
            'target_gudang_type' => 'JS',
            'qty' => 30,
        ]);
        $splitRes->assertRedirect();
        $this->assertDatabaseHas('gudang_products', [
            'supplier_product_id' => $product->id,
            'rack_id' => $rak1->rak_kode,
            'qty' => 80,
        ]);
        $this->assertDatabaseHas('gudang_products', [
            'supplier_product_id' => $product->id,
            'rack_id' => $rak2->rak_kode,
            'qty' => 30,
        ]);

        // 4. Inbound Cancel (adjusts stock back)
        $cancelRes = $this->actingAs($this->superAdmin)->patch(route('inbound.cancel', $inbound->id));
        $cancelRes->assertRedirect();
        $inbound->refresh();
        $this->assertEquals('cancelled', $inbound->status);
    }

    public function test_warehouse_task_and_outbound_inputs_including_error_boundary(): void
    {
        $customer = Master_customer::create([
            'id' => 'CUST-WH-01',
            'nama_customer' => 'PT Karya Cemerlang',
            'nama_pic' => 'Andi',
            'nomor_hp' => '0812345678',
        ]);
        $supplier = Supplier::create(['name' => 'Supplier WH QC', 'status' => 'active']);
        $product = SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'sku' => 'SKU-WH-01',
            'item_name' => 'MCB Schneider 16A',
            'last_purchase_price' => 50000,
            'status' => 'active',
        ]);
        $rak = Rak::create(['rak_kode' => 'RAK-WH-01', 'location' => 'Lantai 1', 'gudang' => 'js']);

        $gp = GudangProduct::create([
            'id' => 'GP-WH-01',
            'supplier_product_id' => $product->id,
            'rack_id' => $rak->rak_kode,
            'gudang_type' => 'JS',
            'qty' => 50,
            'price' => 50000,
            'status' => 'stored',
        ]);

        $order = SalesOrder::create([
            'order_date' => now(),
            'order_status' => 'invoice',
            'stock_status' => 'available',
            'customer_id' => $customer->id,
            'customer_name' => $customer->nama_customer,
            'customer_po_number' => 'PO-WH-001',
            'subtotal' => 500000,
            'grand_total' => 500000,
        ]);

        $invoice = Invoice::create([
            'id' => 'INV-WH-001',
            'invoice_number' => 'INV/2026/001',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'outstanding',
            'subtotal' => 500000,
            'grand_total' => 500000,
            'outstanding_amount' => 500000,
            'paid_amount' => 0,
            'customer_name' => $customer->nama_customer,
        ]);

        $order->update(['invoice_id' => $invoice->id]);
        $invoice->salesOrders()->attach($order->id);

        // 1. Warehouse Task Store
        $wtRes = $this->actingAs($this->superAdmin)->post(route('warehouse-task.store'), [
            'invoice_id' => $invoice->id,
            'assigned_to' => 'Petugas Gudang 1',
            'note' => 'Siapkan barang dengan bubble wrap',
        ]);
        $wtRes->assertRedirect(route('warehouse-task.index'));
        $task = WarehouseTask::where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertEquals('waiting', $task->status);

        // 2. Warehouse Task Process
        $procRes = $this->actingAs($this->superAdmin)->patch(route('warehouse-task.process', $task->id));
        $procRes->assertRedirect();
        $task->refresh();
        $this->assertEquals('process', $task->status);

        // 3. Outbound Input: Boundary Test - exceeding quantity fails cleanly with validation error
        $exceedRes = $this->actingAs($this->superAdmin)->post(route('outbound.store'), [
            'warehouse_task_id' => $task->id,
            'gudang_product_id' => $gp->id,
            'qty' => 999, // Exceeds available stock (50)
            'outbound_date' => now()->toDateString(),
            'delivery_type' => 'full',
            'note' => 'Percobaan melebihi stok',
        ]);
        $exceedRes->assertSessionHasErrors(['qty']);

        // 4. Outbound Input: Valid submission
        $validOutRes = $this->actingAs($this->superAdmin)->post(route('outbound.store'), [
            'warehouse_task_id' => $task->id,
            'gudang_product_id' => $gp->id,
            'qty' => 10,
            'outbound_date' => now()->toDateString(),
            'delivery_type' => 'full',
            'note' => 'Barang diambil ekspedisi',
        ]);
        $validOutRes->assertRedirect(route('outbound.index'));
        $gp->refresh();
        $this->assertEquals(40, $gp->qty); // 50 - 10 = 40
        $task->refresh();
        $this->assertEquals('completed', $task->status);
        $order->refresh();
        $this->assertEquals('delivered', $order->order_status);
    }

    public function test_sales_order_and_invoicing_inputs(): void
    {
        $customer = Master_customer::create([
            'id' => 'CUST-SO-QC',
            'nama_customer' => 'Toko Listrik Makmur',
            'nama_pic' => 'Koh Asep',
            'nomor_hp' => '087812345678',
        ]);
        $supplier = Supplier::create(['name' => 'Supplier SO QC', 'status' => 'active']);
        $product = SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'sku' => 'SKU-SO-01',
            'item_name' => 'Stop Kontak Arde Broco',
            'last_purchase_price' => 20000,
            'status' => 'active',
        ]);
        $rak = Rak::create(['rak_kode' => 'RAK-SO-01', 'location' => 'Rak A1', 'gudang' => 'js']);
        GudangProduct::create([
            'id' => 'GP-SO-01',
            'supplier_product_id' => $product->id,
            'rack_id' => $rak->rak_kode,
            'gudang_type' => 'JS',
            'qty' => 100,
            'price' => 25000,
            'status' => 'stored',
        ]);

        // 1. Create Sales Order
        $soRes = $this->actingAs($this->superAdmin)->post(route('sales-finance.store'), [
            'customer_id' => $customer->id,
            'customer_name' => $customer->nama_customer,
            'order_date' => now()->toDateString(),
            'order_status' => 'draft',
            'sales_type' => 'js',
            'items' => [
                [
                    'product_code' => $product->id,
                    'product_name' => $product->item_name,
                    'rack_id' => $rak->rak_kode,
                    'unit' => 'pcs',
                    'quantity' => 10,
                    'unit_price' => 30000,
                    'discount_1' => 5,
                    'discount_2' => 0,
                    'discount_3' => 0,
                    'discount_4' => 0,
                ],
            ],
        ]);
        $order = SalesOrder::where('customer_id', $customer->id)->firstOrFail();
        $soRes->assertRedirect(route('sales-finance.show', $order));

        // 2. Check Stock
        $checkRes = $this->actingAs($this->superAdmin)->post(route('sales-finance.stock-check', $order->id));
        $checkRes->assertRedirect();
        $order->refresh();
        $this->assertEquals('available', $order->stock_status);

        // 3. Generate Invoice
        $invRes = $this->actingAs($this->superAdmin)->post(route('sales-finance.invoice.generate', $order->id), [
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'tax_type' => 'sjb_non_pajak',
            'payment_type' => 'tempo',
        ]);
        $invRes->assertRedirect();
        $order->refresh();
        $this->assertNotNull($order->invoice_id);
        $this->assertEquals('invoiced', $order->order_status);

        // 4. Create Delivery Note from Invoice
        $dnRes = $this->actingAs($this->superAdmin)->post(route('invoices.delivery-note.store', $order->invoice_id), [
            'delivery_date' => now()->toDateString(),
            'status' => 'delivered',
            'pic_sales' => 'Sales Person 1',
            'pic_gudang' => 'Gudang Person 1',
            'notes' => 'Surat jalan resmi',
        ]);
        $dnRes->assertRedirect();
        $this->assertDatabaseHas('delivery_notes', ['invoice_id' => $order->invoice_id]);

        // 5. Record Payment for Invoice
        $invoice = Invoice::findOrFail($order->invoice_id);
        $payRes = $this->actingAs($this->superAdmin)->post(route('finance.payment.store', $invoice->id), [
            'type' => 'ar',
            'id' => $invoice->id,
            'amount' => 100000,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'transfer',
            'receiving_account' => 'js',
            'bank_name' => 'BCA',
            'notes' => 'Cicilan pertama',
        ]);
        $payRes->assertRedirect();
        $invoice->refresh();
        $this->assertEquals(100000, (float) $invoice->paid_amount);
        $this->assertEquals('partial', $invoice->status);
    }

    public function test_bundle_promos_inputs(): void
    {
        $supplier = Supplier::create(['name' => 'Supplier Bundle', 'status' => 'active']);
        $p1 = SupplierProduct::create(['supplier_id' => $supplier->id, 'sku' => 'SKU-BP-1', 'item_name' => 'Lampu LED 10W', 'last_purchase_price' => 20000, 'status' => 'active']);
        $p2 = SupplierProduct::create(['supplier_id' => $supplier->id, 'sku' => 'SKU-BP-2', 'item_name' => 'Saklar Engkel', 'last_purchase_price' => 15000, 'status' => 'active']);

        // 1. Store bundle promo with percentage discount
        $bpRes = $this->actingAs($this->superAdmin)->post(route('bundle-promos.store'), [
            'bundle_code' => 'BNDL-HEMAT-01',
            'name' => 'Paket Hemat Renovasi',
            'description' => 'Paket 5 lampu + 2 saklar',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'items' => [
                ['product_id' => $p1->id, 'qty' => 5, 'unit_price' => 25000],
                ['product_id' => $p2->id, 'qty' => 2, 'unit_price' => 20000],
            ],
        ]);
        $bpRes->assertRedirect(route('bundle-promos.index'));
        $this->assertDatabaseHas('bundle_promos', ['bundle_code' => 'BNDL-HEMAT-01']);

        $bundle = BundlePromo::where('bundle_code', 'BNDL-HEMAT-01')->firstOrFail();

        // 2. Update bundle promo
        $bpUpdRes = $this->actingAs($this->superAdmin)->put(route('bundle-promos.update', $bundle->id), [
            'bundle_code' => 'BNDL-HEMAT-01',
            'name' => 'Paket Hemat Renovasi Spesial',
            'description' => 'Paket revisi',
            'discount_type' => 'fixed',
            'discount_value' => 25000,
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(60)->toDateString(),
            'items' => [
                ['product_id' => $p1->id, 'qty' => 5, 'unit_price' => 25000],
                ['product_id' => $p2->id, 'qty' => 2, 'unit_price' => 20000],
            ],
        ]);
        $bpUpdRes->assertRedirect(route('bundle-promos.index'));
        $this->assertDatabaseHas('bundle_promos', ['name' => 'Paket Hemat Renovasi Spesial']);
    }

    public function test_rekening_bank_and_internal_invoices_inputs(): void
    {
        // 1. Rekening Bank Store & Update
        $rekRes = $this->actingAs($this->superAdmin)->post(route('rekening-banks.store'), [
            'bank_name' => 'Bank Mandiri',
            'account_name' => 'PT Saktindo Jaya Bersama',
            'account_number' => '142-00-1234567-8',
            'toko' => 'sjb',
        ]);
        $rekRes->assertRedirect(route('rekening-banks.index'));
        $rekening = RekeningBank::where('account_number', '142-00-1234567-8')->firstOrFail();

        $this->actingAs($this->superAdmin)->put(route('rekening-banks.update', $rekening->id), [
            'bank_name' => 'Bank Mandiri Cabang Sudirman',
            'account_name' => 'PT Saktindo Jaya Bersama',
            'account_number' => '142-00-1234567-8',
            'toko' => 'sjb',
        ]);
        $this->assertDatabaseHas('rekenings', ['bank_name' => 'Bank Mandiri Cabang Sudirman']);

        // 2. Internal Invoice Store
        $invIntRes = $this->actingAs($this->superAdmin)->post(route('internal-invoices.store'), [
            'from_store' => 'js',
            'to_store' => 'sjb',
            'invoice_date' => now()->toDateString(),
            'amount' => 1500000,
            'description' => 'Pinjaman operasional antar toko',
        ]);
        $invIntRes->assertRedirect(route('internal-invoices.index'));
        $this->assertDatabaseHas('internal_invoices', ['amount' => 1500000, 'from_store' => 'js', 'to_store' => 'sjb']);

        // 3. Internal Invoice rejection when from_store == to_store
        $sameStoreRes = $this->actingAs($this->superAdmin)->post(route('internal-invoices.store'), [
            'from_store' => 'js',
            'to_store' => 'js',
            'invoice_date' => now()->toDateString(),
            'amount' => 500000,
        ]);
        $sameStoreRes->assertSessionHasErrors(['to_store']);
    }

    public function test_retur_and_web_customization_inputs(): void
    {
        // 1. Retur Store & Update
        $order = SalesOrder::create([
            'order_date' => now(),
            'order_status' => 'delivered',
            'stock_status' => 'available',
            'customer_name' => 'Customer Retur Test',
            'customer_po_number' => 'PO-RET-01',
            'subtotal' => 100000,
            'grand_total' => 100000,
        ]);

        $retRes = $this->actingAs($this->superAdmin)->post(route('returs.store'), [
            'sales_order_id' => $order->id,
            'return_date' => now()->toDateString(),
            'notes' => 'Barang pecah saat pengiriman',
        ]);
        $retRes->assertRedirect();
        $retur = Retur::where('sales_order_id', $order->id)->firstOrFail();

        $retUpdRes = $this->actingAs($this->superAdmin)->put(route('returs.update', $retur->id), [
            'status' => 'received',
            'received_date' => now()->toDateString(),
            'notes' => 'Barang retur sudah diterima di gudang',
        ]);
        $retUpdRes->assertRedirect();
        $this->assertDatabaseHas('returs', ['status' => 'received']);

        // 2. Web Customization Update
        $fakeLogo = UploadedFile::fake()->image('logo.png', 100, 100);
        $custRes = $this->actingAs($this->superAdmin)->put(route('web-customization.update'), [
            'primary_color' => '#1E40AF',
            'sidebar_logo' => $fakeLogo,
        ]);
        $custRes->assertRedirect();
        $this->assertDatabaseHas('web_settings', ['key' => 'primary_color', 'value' => '#1E40AF']);
    }

    public function test_external_orders_api_input(): void
    {
        $payload = [
            'customer_name' => 'E-Commerce Buyer Budi',
            'customer_email' => 'buyer.budi@gmail.com',
            'customer_phone' => '081288776655',
            'customer_address' => 'Jl. Tebet Raya No. 12 Jakarta Selatan',
            'items' => [
                [
                    'product_code' => 'SKU-EXT-01',
                    'product_name' => 'LED Strip RGB 5M',
                    'unit' => 'pcs',
                    'quantity' => 2,
                    'unit_price' => 125000,
                ],
            ],
        ];

        $res = $this->postJson('/api/external-orders', $payload);
        $res->assertStatus(201);
        $res->assertJson(['success' => true]);

        $this->assertDatabaseHas('sales_orders', [
            'customer_name' => 'E-Commerce Buyer Budi',
            'grand_total' => 250000,
        ]);
    }
}
