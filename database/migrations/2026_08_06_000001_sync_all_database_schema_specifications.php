<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure sales_orders table has sales_type column
        if (Schema::hasTable('sales_orders')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('sales_orders', 'sales_type')) {
                    $table->string('sales_type')->default('js')->after('order_date');
                }
            });
        }

        // 2. Ensure sales_order_items table has delivered_qty and discount columns
        if (Schema::hasTable('sales_order_items')) {
            Schema::table('sales_order_items', function (Blueprint $table) {
                if (!Schema::hasColumn('sales_order_items', 'delivered_qty')) {
                    $table->decimal('delivered_qty', 15, 2)->default(0)->after('quantity');
                }
                if (!Schema::hasColumn('sales_order_items', 'discount')) {
                    $table->decimal('discount', 15, 2)->default(0)->after('unit_price');
                }
            });
        }

        // 3. Ensure invoices table has invoice_type column
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('invoices', 'invoice_type')) {
                    $table->string('invoice_type')->default('normal')->after('invoice_number');
                }
            });
        }

        // 4. Ensure delivery_notes table has print_count column
        if (Schema::hasTable('delivery_notes')) {
            Schema::table('delivery_notes', function (Blueprint $table) {
                if (!Schema::hasColumn('delivery_notes', 'print_count')) {
                    $table->integer('print_count')->default(0)->after('notes');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sales_orders')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                if (Schema::hasColumn('sales_orders', 'sales_type')) {
                    $table->dropColumn('sales_type');
                }
            });
        }

        if (Schema::hasTable('sales_order_items')) {
            Schema::table('sales_order_items', function (Blueprint $table) {
                if (Schema::hasColumn('sales_order_items', 'delivered_qty')) {
                    $table->dropColumn('delivered_qty');
                }
            });
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (Schema::hasColumn('invoices', 'invoice_type')) {
                    $table->dropColumn('invoice_type');
                }
            });
        }

        if (Schema::hasTable('delivery_notes')) {
            Schema::table('delivery_notes', function (Blueprint $table) {
                if (Schema::hasColumn('delivery_notes', 'print_count')) {
                    $table->dropColumn('print_count');
                }
            });
        }
    }
};
