<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\BundlePromo;
use App\Models\BundlePromoItem;
use App\Models\Category;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\GudangProduct;
use App\Models\InBound;
use App\Models\Invoice;
use App\Models\Master_customer;
use App\Models\OutBound;
use App\Models\Product;
use App\Models\Rak;
use App\Models\RekeningBank;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SubCategory;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Models\SupplierPaymentTerm;
use App\Models\SupplierPO;
use App\Models\SupplierPOItem;
use App\Models\SupplierProduct;
use App\Models\SupplierPurchaseHistory;
use App\Models\User;
use App\Models\Variant;
use App\Models\WarehouseTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemFeatureHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);
    }

    public function test_all_static_get_routes_return_successful_response(): void
    {
        $staticRoutes = [
            'dashboard',
            'users.index',
            'users.create',
            'roles.index',
            'roles.create',
            'suppliers.index',
            'suppliers.vendors',
            'suppliers.contacts',
            'suppliers.contacts.create',
            'suppliers.products',
            'suppliers.products.create',
            'suppliers.purchases',
            'suppliers.purchases.create',
            'suppliers.payment-terms',
            'suppliers.payment-terms.create',
            'suppliers.create',
            'sales-finance.pre-orders.index',
            'sales-finance.index',
            'sales-finance.create',
            'finance.index',
            'finance.ar',
            'finance.receivables',
            'finance.ap',
            'finance.report',
            'supplier-po.index',
            'supplier-po.create',
            'bundle-promos.index',
            'bundle-promos.create',
            'activity-logs.index',
            'sessions.index',
            'web-customization.edit',
            'products.index',
            'products.create',
            'master-customer.index',
            'master-customer.create',
            'master-customer.import',
            'rak.index',
            'rak.create',
            'inbound.index',
            'inbound.create',
            'gudang-product.index',
            'gudang-product.create',
            'gudang-product.import',
            'warehouse-task.index',
            'warehouse-task.create',
            'outbound.index',
            'brands.index',
            'categories.index',
            'sub-categories.index',
            'variants.index',
            'returs.index',
            'internal-invoices.index',
            'internal-invoices.create',
            'rekening-banks.index',
            'rekening-banks.create',
        ];

        foreach ($staticRoutes as $routeName) {
            $url = route($routeName);
            $response = $this->actingAs($this->admin)->get($url);
            $this->assertTrue(
                in_array($response->getStatusCode(), [200, 302]),
                "Route [{$routeName}] at URL [{$url}] failed with status: {$response->getStatusCode()}"
            );
        }
    }

    public function test_all_parameterized_pages_render_without_laravel_error(): void
    {
        // 1. Supplier ecosystem
        $supplier = Supplier::create([
            'name' => 'Supplier Health Test',
            'code' => 'SUP-HEALTH-01',
            'status' => 'active',
        ]);
        $supplierContact = SupplierContact::create([
            'supplier_id' => $supplier->id,
            'name' => 'Contact Health',
            'phone' => '08123456789',
        ]);
        $supplierProduct = SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'sku' => 'SKU-HEALTH-01',
            'item_name' => 'Barang Health Test',
            'last_purchase_price' => 50000,
            'status' => 'active',
        ]);
        $supplierPurchase = SupplierPurchaseHistory::create([
            'supplier_id' => $supplier->id,
            'item_name' => 'Lampu LED Inbound',
            'po_number' => 'PO-HIST-01',
            'purchase_date' => now()->toDateString(),
            'total_amount' => 1000000,
        ]);
        $supplierPaymentTerm = SupplierPaymentTerm::create([
            'supplier_id' => $supplier->id,
            'name' => 'Tempo 30 Hari',
            'due_days' => 30,
        ]);

        $this->actingAs($this->admin)->get(route('suppliers.show', $supplier))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('suppliers.edit', $supplier))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('suppliers.contacts.edit', $supplierContact))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('suppliers.purchases.edit', $supplierPurchase))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('suppliers.payment-terms.edit', $supplierPaymentTerm))->assertStatus(200);

        // 2. Master Customer
        $customer = Master_customer::create([
            'id' => 'CUST-HEALTH-01',
            'nama_customer' => 'Customer Health Check',
            'nama_pic' => 'PIC Health',
            'nomor_hp' => '08111111111',
            'email' => 'health@cust.com',
            'alamat' => 'Jl. Sehat No. 1',
            'kota' => 'Jakarta',
        ]);
        $this->actingAs($this->admin)->get(route('master-customer.edit', $customer))->assertStatus(200);

        // 3. Master Brand, Category, SubCategory, Product, Variant
        $brand = Brand::create(['name' => 'Brand Health', 'slug' => 'brand-health']);
        $category = Category::create(['name' => 'Category Health', 'slug' => 'cat-health']);
        $subCategory = SubCategory::create(['name' => 'SubCat Health', 'slug' => 'subcat-health', 'category_id' => $category->id]);
        $product = Product::create([
            'product_id' => 'PROD-HEALTH-01',
            'product_name' => 'Product Health',
            'brand' => 'Brand Health',
            'category' => 'Category Health',
            'sub_category' => 'SubCat Health',
            'sku_id' => 'SKU-HEALTH-PROD',
            'price' => 75000,
            'quantity' => 10,
        ]);
        $variant = Variant::create([
            'name' => 'Varian Health Putih',
        ]);

        $this->actingAs($this->admin)->put(route('brands.update', $brand), ['name' => 'Brand Health Updated'])->assertStatus(302);
        $this->actingAs($this->admin)->put(route('categories.update', $category), ['name' => 'Category Health Updated'])->assertStatus(302);
        $this->actingAs($this->admin)->put(route('sub-categories.update', $subCategory), ['name' => 'SubCat Health Updated', 'category_id' => $category->id])->assertStatus(302);
        $this->actingAs($this->admin)->put(route('variants.update', $variant), ['name' => 'Variant Health Updated'])->assertStatus(302);
        $this->actingAs($this->admin)->get(route('products.show', $supplierProduct->id))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('products.edit', $supplierProduct->id))->assertStatus(200);

        // 4. Warehouse: Rak, InBound, OutBound, GudangProduct, WarehouseTask
        $rak = Rak::create(['rak_kode' => 'RAK-HEALTH', 'location' => 'Lantai 1', 'gudang' => 'js']);
        $inbound = InBound::create([
            'id' => 'INB-HEALTH-01',
            'supplier_id' => $supplier->id,
            'supplier_product_id' => $supplierProduct->id,
            'qty_received' => 50,
            'received_date' => now()->toDateString(),
            'status' => 'pending',
        ]);
        $gudangProduct = GudangProduct::create([
            'id' => 'GP-HEALTH-01',
            'supplier_product_id' => $supplierProduct->id,
            'rack_id' => $rak->id,
            'gudang_type' => 'js',
            'qty' => 50,
            'price' => 50000,
        ]);
        $this->actingAs($this->admin)->get(route('rak.edit', $rak))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('gudang-product.create', ['inbound_id' => $inbound->id]))->assertStatus(200);

        // 5. Sales & Finance: SalesOrder, Invoice, DeliveryNote
        $salesOrder = SalesOrder::create([
            'customer_id' => $customer->id,
            'customer_name' => $customer->nama_customer,
            'customer_po_number' => 'PO-SO-HEALTH-01',
            'order_date' => now()->toDateString(),
            'po_date' => now()->toDateString(),
            'sales_type' => 'credit',
            'order_status' => 'confirmed',
            'subtotal' => 200000,
            'tax_amount' => 0,
            'grand_total' => 200000,
        ]);
        SalesOrderItem::create([
            'sales_order_id' => $salesOrder->id,
            'product_name' => 'Barang Health Test',
            'quantity' => 2,
            'unit' => 'pcs',
            'unit_price' => 100000,
            'line_total' => 200000,
        ]);
        $invoice = Invoice::create([
            'sales_order_id' => $salesOrder->id,
            'invoice_number' => 'INV-HEALTH-01',
            'invoice_type' => 'standar',
            'tax_type' => 'sjb_non_pajak',
            'faktur_number' => 'FKT-HEALTH-01',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => 'unpaid',
            'subtotal' => 200000,
            'tax_amount' => 0,
            'grand_total' => 200000,
            'paid_amount' => 0,
            'outstanding_amount' => 200000,
        ]);
        $salesOrder->update(['invoice_id' => $invoice->id]);

        $deliveryNote = DeliveryNote::create([
            'invoice_id' => $invoice->id,
            'delivery_note_number' => 'SJ-HEALTH-01',
            'delivery_date' => now()->toDateString(),
            'status' => 'delivered',
        ]);
        DeliveryNoteItem::create([
            'delivery_note_id' => $deliveryNote->id,
            'sales_order_item_id' => $salesOrder->items->first()->id,
            'qty_sent' => 2,
        ]);

        $this->actingAs($this->admin)->get(route('sales-finance.show', $salesOrder))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('sales-finance.edit', $salesOrder))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('sales-finance.proforma.print', $salesOrder))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('sales-finance.invoices.pdf', $invoice))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('sales-finance.delivery-notes.print', $deliveryNote))->assertStatus(200);

        // Warehouse Tasks
        $warehouseTask = WarehouseTask::create([
            'id' => 'WT-HEALTH-01',
            'sales_order_id' => $salesOrder->id,
            'invoice_id' => $invoice->id,
            'status' => 'waiting',
        ]);
        $this->actingAs($this->admin)->get(route('warehouse-task.edit', $warehouseTask))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('warehouse-task.print', $warehouseTask))->assertStatus(200);

        // 6. Supplier PO & Invoice
        $supplierPo = SupplierPO::create([
            'id' => 'SPO-HEALTH-01',
            'supplier_id' => $supplier->id,
            'po_number' => 'SPO-HEALTH-01',
            'order_date' => now()->toDateString(),
            'status' => 'received',
            'subtotal' => 500000,
            'tax_amount' => 0,
            'grand_total' => 500000,
        ]);
        SupplierPOItem::create([
            'supplier_po_id' => $supplierPo->id,
            'supplier_product_id' => $supplierProduct->id,
            'qty' => 10,
            'price' => 50000,
        ]);

        $this->actingAs($this->admin)->get(route('supplier-po.show', $supplierPo))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('supplier-po.invoice', $supplierPo))->assertStatus(200);

        // 7. Bundle Promo
        $bundlePromo = BundlePromo::create([
            'id' => 'BND-HEALTH-01',
            'bundle_code' => 'BNDL-001',
            'name' => 'Paket Hemat Health',
            'original_price' => 100000,
            'bundle_price' => 90000,
            'status' => 'active',
        ]);
        BundlePromoItem::create([
            'bundle_promo_id' => $bundlePromo->id,
            'supplier_product_id' => $supplierProduct->id,
            'qty' => 2,
            'unit_price' => 50000,
        ]);

        $this->actingAs($this->admin)->get(route('bundle-promos.show', $bundlePromo))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('bundle-promos.edit', $bundlePromo))->assertStatus(200);

        // 8. Rekening Bank
        $rekeningBank = RekeningBank::create([
            'id' => 'REK-HEALTH-01',
            'bank_name' => 'BCA',
            'account_name' => 'PT. SAKTINDO JAYA BERSAMA',
            'account_number' => '0683055678',
            'toko' => 'sjb',
        ]);
        $this->actingAs($this->admin)->get(route('rekening-banks.edit', $rekeningBank->id))->assertStatus(200);

        // 9. Users and Roles
        $role = Role::create([
            'id' => 'ROL-HEALTH-01',
            'name' => 'Custom Role Health',
            'slug' => 'custom_health',
            'permissions' => ['dashboard'],
        ]);
        $sampleUser = User::factory()->create(['role' => 'custom_health']);

        $this->actingAs($this->admin)->get(route('users.edit', $sampleUser))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('users.reset-password', $sampleUser))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('roles.edit', $role))->assertStatus(200);
    }

    public function test_all_critical_interactive_actions_and_buttons_succeed()
    {
        // 1. Web Customization Update
        $response = $this->actingAs($this->admin)->put(route('web-customization.update'), [
            'primary_color' => '#1A73E8',
        ]);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // 2. Setup Data for Invoice & Delivery Note & Return
        $customer = Master_customer::create([
            'id' => 'CUST-ACT-01',
            'nama_customer' => 'PT Aksi Sukses',
            'nama_pic' => 'PIC Aksi',
            'tipe_customer' => 'perusahaan',
            'nomor_hp' => '0811111111',
            'alamat' => 'Jl. Aksi No. 1',
            'kota' => 'Surabaya',
        ]);

        $so = SalesOrder::create([
            'id' => 'SO-ACT-01',
            'customer_id' => $customer->id,
            'customer_name' => $customer->nama_customer,
            'customer_po_number' => 'PO-CUST-ACT-01',
            'order_date' => now()->toDateString(),
            'po_date' => now()->toDateString(),
            'sales_type' => 'credit',
            'order_status' => 'confirmed',
            'subtotal' => 100000,
            'grand_total' => 100000,
        ]);

        $soItem = SalesOrderItem::create([
            'sales_order_id' => $so->id,
            'product_name' => 'Barang Aksi',
            'quantity' => 2,
            'unit' => 'pcs',
            'unit_price' => 50000,
            'line_total' => 100000,
        ]);

        $invoice = Invoice::create([
            'id' => 'INV-ACT-01',
            'invoice_number' => 'INV-202610-099',
            'sales_order_id' => $so->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'subtotal' => 100000,
            'grand_total' => 100000,
            'paid_amount' => 0,
            'outstanding_amount' => 100000,
            'status' => 'unpaid',
        ]);

        // 3. Store Delivery Note
        $dnResponse = $this->actingAs($this->admin)->post(route('invoices.delivery-note.store', $invoice), [
            'delivery_date' => now()->toDateString(),
            'status' => 'delivered',
            'pic_sales' => 'Budi',
            'pic_gudang' => 'Joko',
            'notes' => 'Pengiriman lengkap',
        ]);
        $dnResponse->assertSessionHasNoErrors();
        $dnResponse->assertRedirect();

        $deliveryNote = DeliveryNote::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($deliveryNote);

        // Create DeliveryNoteItem for return test
        $dnItem = DeliveryNoteItem::create([
            'delivery_note_id' => $deliveryNote->id,
            'sales_order_item_id' => $soItem->id,
            'qty_sent' => 2,
        ]);

        // 4. Store Delivery Note Return
        $returnResponse = $this->actingAs($this->admin)->post(
            route('sales-finance.delivery-notes.returns.store', $deliveryNote),
            [
                'return_date' => now()->toDateString(),
                'status' => 'received',
                'notes' => 'Barang retur rusak fisik',
                'items' => [
                    [
                        'sales_order_item_id' => $soItem->id,
                        'qty_returned' => 1,
                        'reason' => 'Kemasan rusak',
                    ],
                ],
            ]
        );
        $returnResponse->assertSessionHasNoErrors();
        $returnResponse->assertRedirect();

        // 5. Store Payment from Sales Finance (Requires completed WarehouseTask)
        WarehouseTask::create([
            'id' => 'WT-ACT-01',
            'sales_order_id' => $so->id,
            'invoice_id' => $invoice->id,
            'status' => 'completed',
        ]);

        $paymentResponse = $this->actingAs($this->admin)->post(
            route('invoices.payments.store', $invoice),
            [
                'payment_date' => now()->toDateString(),
                'amount' => 50000,
                'method' => 'transfer_bank',
                'receiving_account' => 'sjb',
                'bank_account' => 'BCA SJB',
                'reference_number' => 'TRX-998877',
                'notes' => 'Pembayaran parsial 1',
            ]
        );
        $paymentResponse->assertSessionHasNoErrors();
        $paymentResponse->assertRedirect();

        // 6. Store Payment from Finance Module
        $financePaymentResponse = $this->actingAs($this->admin)->post(
            route('finance.payment.store', $invoice->id),
            [
                'type' => 'ar',
                'id' => $invoice->id,
                'amount' => 50000,
                'payment_date' => now()->toDateString(),
                'payment_method' => 'cash',
                'receiving_account' => 'sjb',
                'notes' => 'Pelunasan invoice',
            ]
        );
        $financePaymentResponse->assertSessionHasNoErrors();
        $financePaymentResponse->assertRedirect();

        $invoice->refresh();
        $this->assertEquals(0, $invoice->outstanding_amount);
        $this->assertEquals('paid', $invoice->status);
    }
}

