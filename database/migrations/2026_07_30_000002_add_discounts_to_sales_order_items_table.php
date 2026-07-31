<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_order_items', 'discount_1')) {
                $table->decimal('discount_1', 5, 2)->default(0)->after('unit_price');
            }
            if (!Schema::hasColumn('sales_order_items', 'discount_2')) {
                $table->decimal('discount_2', 5, 2)->default(0)->after('discount_1');
            }
            if (!Schema::hasColumn('sales_order_items', 'discount_3')) {
                $table->decimal('discount_3', 5, 2)->default(0)->after('discount_2');
            }
            if (!Schema::hasColumn('sales_order_items', 'discount_4')) {
                $table->decimal('discount_4', 5, 2)->default(0)->after('discount_3');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->dropColumn(['discount_1', 'discount_2', 'discount_3', 'discount_4']);
        });
    }
};
