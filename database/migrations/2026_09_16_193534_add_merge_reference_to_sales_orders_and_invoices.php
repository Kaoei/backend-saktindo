<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_orders', 'merged_into_sales_order_id')) {
                $table->string('merged_into_sales_order_id')
                    ->nullable()
                    ->after('invoice_id');

                $table->index('merged_into_sales_order_id');

                $table->foreign('merged_into_sales_order_id')
                    ->references('id')
                    ->on('sales_orders')
                    ->nullOnDelete();
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'merged_into_invoice_id')) {
                $table->string('merged_into_invoice_id')
                    ->nullable()
                    ->after('sales_order_id');

                $table->index('merged_into_invoice_id');

                $table->foreign('merged_into_invoice_id')
                    ->references('id')
                    ->on('invoices')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'merged_into_invoice_id')) {
                $table->dropForeign(['merged_into_invoice_id']);
                $table->dropIndex(['merged_into_invoice_id']);
                $table->dropColumn('merged_into_invoice_id');
            }
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            if (Schema::hasColumn('sales_orders', 'merged_into_sales_order_id')) {
                $table->dropForeign(['merged_into_sales_order_id']);
                $table->dropIndex(['merged_into_sales_order_id']);
                $table->dropColumn('merged_into_sales_order_id');
            }
        });
    }
};