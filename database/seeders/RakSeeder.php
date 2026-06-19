<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rak;

class RakSeeder extends Seeder
{
    public function run(): void
    {
        Rak::insert([
            [
                'rak_kode' => 'A-00',
                'location' => '-',
            ],
            [
                'rak_kode' => 'A-01',
                'location' => 'Gudang Utama',
            ],
            [
                'rak_kode' => 'A-02',
                'location' => 'Gudang Utama',
            ],
            [
                'rak_kode' => 'B-01',
                'location' => 'Gudang Timur',
            ],
            [
                'rak_kode' => 'B-02',
                'location' => 'Gudang Timur',
            ],
            [
                'rak_kode' => 'C-01',
                'location' => 'Gudang Barat',
            ],
        ]);
    }
}