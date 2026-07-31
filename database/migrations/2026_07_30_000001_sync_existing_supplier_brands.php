<?php

use App\Models\Brand;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Sync company_name from suppliers table
        Supplier::query()
            ->whereNotNull('company_name')
            ->where('company_name', '!=', '')
            ->each(function (Supplier $supplier) {
                Supplier::syncBrand($supplier->company_name);
            });

        // Sync brand from supplier_products table
        SupplierProduct::query()
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->each(function (SupplierProduct $product) {
                Supplier::syncBrand($product->brand);
            });
    }

    public function down(): void
    {
        // No rollback needed for data sync
    }
};
