<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Gudang RK support
        try {
            DB::statement("ALTER TABLE gudang_products MODIFY COLUMN gudang_type ENUM('JS', 'SJB', 'RK') DEFAULT 'JS'");
        } catch (\Throwable $e) {
            // Fallback if SQLite or already modified
        }

        try {
            DB::statement("ALTER TABLE raks MODIFY COLUMN gudang ENUM('js', 'sjb', 'rk') DEFAULT 'js'");
        } catch (\Throwable $e) {
            // Fallback
        }

        // 2. Add qty_allocated to in_bounds if not exists
        if (!Schema::hasColumn('in_bounds', 'qty_allocated')) {
            Schema::table('in_bounds', function (Blueprint $table) {
                $table->integer('qty_allocated')->default(0)->after('qty_received');
            });

            // Backfill existing stored inbounds so qty_allocated matches qty_received
            DB::table('in_bounds')->where('status', 'stored')->update([
                'qty_allocated' => DB::raw('qty_received'),
            ]);
        }

        // 3. Add discount_amount to sales_orders if not exists
        if (!Schema::hasColumn('sales_orders', 'discount_amount')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->decimal('discount_amount', 15, 2)->default(0)->after('subtotal');
            });
        }

        // 4. Add rack_id to sales_order_items if not exists
        if (!Schema::hasColumn('sales_order_items', 'rack_id')) {
            Schema::table('sales_order_items', function (Blueprint $table) {
                $table->string('rack_id')->nullable()->after('product_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('sales_order_items', 'rack_id')) {
            Schema::table('sales_order_items', function (Blueprint $table) {
                $table->dropColumn('rack_id');
            });
        }

        if (Schema::hasColumn('sales_orders', 'discount_amount')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropColumn('discount_amount');
            });
        }

        if (Schema::hasColumn('in_bounds', 'qty_allocated')) {
            Schema::table('in_bounds', function (Blueprint $table) {
                $table->dropColumn('qty_allocated');
            });
        }
    }
};
