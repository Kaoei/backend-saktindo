<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('in_bounds', function (Blueprint $table) {
            $table->index('supplier_po_id');
            $table->index('supplier_id');
            $table->index('supplier_product_id');
        });

        Schema::table('warehouse_tasks', function (Blueprint $table) {
            $table->index('sales_order_id');
            $table->index('invoice_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('in_bounds', function (Blueprint $table) {
            $table->dropIndex(['supplier_po_id']);
            $table->dropIndex(['supplier_id']);
            $table->dropIndex(['supplier_product_id']);
        });

        Schema::table('warehouse_tasks', function (Blueprint $table) {
            $table->dropIndex(['sales_order_id']);
            $table->dropIndex(['invoice_id']);
            $table->dropIndex(['status']);
        });
    }
};
