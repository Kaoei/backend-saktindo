<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::insert([
            [
                'id_product' => 'PRD_000001',
                'sku' => 'SKU001',
                'name' => 'Laptop ASUS',
                'desc' => 'Laptop ASUS VivoBook',
                'stock' => 0,
                'Tgl_masuk' => '2026-06-19',
            ],
            [
                'id_product' => 'PRD_000002',
                'sku' => 'SKU002',
                'name' => 'Mouse Logitech',
                'desc' => 'Wireless Mouse Logitech',
                'stock' => 0,
                'Tgl_masuk' => '2026-06-19',
            ],
            [
                'id_product' => 'PRD_000003',
                'sku' => 'SKU003',
                'name' => 'Keyboard Mechanical',
                'desc' => 'Keyboard RGB Mechanical',
                'stock' => 0,
                'Tgl_masuk' => '2026-06-19',
            ],
            [
                'id_product' => 'PRD_000004',
                'sku' => 'SKU004',
                'name' => 'Monitor LG 24 Inch',
                'desc' => 'Monitor Full HD',
                'stock' => 0,
                'Tgl_masuk' => '2026-06-19',
            ],
            [
                'id_product' => 'PRD_000005',
                'sku' => 'SKU005',
                'name' => 'SSD Samsung 1TB',
                'desc' => 'SSD NVMe Gen4',
                'stock' => 0,
                'Tgl_masuk' => '2026-06-19',
            ],
        ]);
    }
}