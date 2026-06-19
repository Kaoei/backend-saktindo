<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        Supplier::insert([
            [
                'id_supplier' => 'SUP_000001',
                'name' => 'PT Sumber Makmur',
                'alamat' => 'Jakarta Barat',
                'no_tlp' => '081234567890',
                'email' => 'sumbermakmur@mail.com',
            ],
            [
                'id_supplier' => 'SUP_000002',
                'name' => 'PT Indo Teknologi',
                'alamat' => 'Jakarta Selatan',
                'no_tlp' => '081234567891',
                'email' => 'indotek@mail.com',
            ],
            [
                'id_supplier' => 'SUP_000003',
                'name' => 'CV Maju Jaya',
                'alamat' => 'Bandung',
                'no_tlp' => '081234567892',
                'email' => 'majujaya@mail.com',
            ],
        ]);
    }
}