<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('gudang_products', function (Blueprint $table) {
            $table->string('id', 100)->change();
        });

        if (Schema::hasTable('out_bounds') && Schema::hasColumn('out_bounds', 'gudang_product_id')) {
            Schema::table('out_bounds', function (Blueprint $table) {
                $table->string('gudang_product_id', 100)->change();
            });
        }

        if (Schema::hasTable('warehouse_tasks') && Schema::hasColumn('warehouse_tasks', 'gudang_product_id')) {
            Schema::table('warehouse_tasks', function (Blueprint $table) {
                $table->string('gudang_product_id', 100)->change();
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('gudang_products', function (Blueprint $table) {
            $table->string('id', 30)->change();
        });

        if (Schema::hasTable('out_bounds') && Schema::hasColumn('out_bounds', 'gudang_product_id')) {
            Schema::table('out_bounds', function (Blueprint $table) {
                $table->string('gudang_product_id', 30)->change();
            });
        }

        if (Schema::hasTable('warehouse_tasks') && Schema::hasColumn('warehouse_tasks', 'gudang_product_id')) {
            Schema::table('warehouse_tasks', function (Blueprint $table) {
                $table->string('gudang_product_id', 30)->change();
            });
        }

        Schema::enableForeignKeyConstraints();
    }
};
