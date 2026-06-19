<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\SupplierProduct;
use Illuminate\Database\Seeder;

class DummyProductSeeder extends Seeder
{
    public function run(): void
    {
        $supplier = Supplier::query()->updateOrCreate(
            ['vendor_code' => 'DUMMY-SAKTINDO'],
            [
                'name' => 'Dummy Supplier Saktindo',
                'vendor_type' => 'electrical',
                'company_name' => 'PT Dummy Supplier Saktindo',
                'pic_name' => 'Admin Supplier',
                'pic_phone' => '081200000001',
                'email' => 'dummy-supplier@example.com',
                'phone' => '021-0000001',
                'payment_due_days' => 30,
                'status' => 'active',
                'notes' => 'Supplier dummy untuk testing item Sales Order.',
            ]
        );

        $products = [
            ['sku' => 'LED-STRIP-12V-5M', 'item_name' => 'LED Strip 12V 5 Meter', 'category' => 'LED Strip', 'brand' => 'Saktindo', 'unit' => 'roll', 'last_purchase_price' => 125000],
            ['sku' => 'LED-PANEL-18W', 'item_name' => 'LED Panel 18W Square', 'category' => 'Panel Light', 'brand' => 'Saktindo', 'unit' => 'pcs', 'last_purchase_price' => 85000],
            ['sku' => 'LED-FLOOD-50W', 'item_name' => 'LED Flood Light 50W', 'category' => 'Outdoor Light', 'brand' => 'Saktindo', 'unit' => 'pcs', 'last_purchase_price' => 185000],
            ['sku' => 'LED-DOWN-12W', 'item_name' => 'LED Downlight 12W', 'category' => 'Downlight', 'brand' => 'Saktindo', 'unit' => 'pcs', 'last_purchase_price' => 65000],
            ['sku' => 'PSU-12V-10A', 'item_name' => 'Power Supply 12V 10A', 'category' => 'Power Supply', 'brand' => 'Mean Well', 'unit' => 'pcs', 'last_purchase_price' => 210000],
            ['sku' => 'CTRL-RGB-WIFI', 'item_name' => 'RGB Controller WiFi', 'category' => 'Controller', 'brand' => 'Saktindo', 'unit' => 'pcs', 'last_purchase_price' => 95000],
            ['sku' => 'CABLE-NYM-2X1.5', 'item_name' => 'Kabel NYM 2x1.5', 'category' => 'Kabel', 'brand' => 'Supreme', 'unit' => 'meter', 'last_purchase_price' => 8500],
            ['sku' => 'ALU-PROFILE-2M', 'item_name' => 'Aluminium Profile LED 2 Meter', 'category' => 'Aksesoris', 'brand' => 'Saktindo', 'unit' => 'batang', 'last_purchase_price' => 45000],
        ];

        foreach ($products as $product) {
            SupplierProduct::query()->updateOrCreate(
                [
                    'supplier_id' => $supplier->id,
                    'sku' => $product['sku'],
                ],
                array_merge($product, [
                    'part_number' => $product['sku'],
                    'minimum_order_qty' => 1,
                    'lead_time_days' => 3,
                    'status' => 'active',
                    'notes' => 'Produk dummy untuk testing Sales & Finance Flow.',
                ])
            );
        }
    }
}
