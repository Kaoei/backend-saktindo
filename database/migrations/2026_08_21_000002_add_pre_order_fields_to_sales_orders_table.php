<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_orders', 'is_pre_order')) {
                $table->boolean('is_pre_order')->default(false)->after('order_status');
            }
            if (!Schema::hasColumn('sales_orders', 'pre_order_eta')) {
                $table->date('pre_order_eta')->nullable()->after('is_pre_order');
            }
            if (!Schema::hasColumn('sales_orders', 'dp_amount')) {
                $table->decimal('dp_amount', 15, 2)->default(0)->after('pre_order_eta');
            }
            if (!Schema::hasColumn('sales_orders', 'dp_paid')) {
                $table->decimal('dp_paid', 15, 2)->default(0)->after('dp_amount');
            }
            if (!Schema::hasColumn('sales_orders', 'dp_status')) {
                $table->enum('dp_status', ['unpaid', 'partial', 'paid'])->default('unpaid')->after('dp_paid');
            }
            if (!Schema::hasColumn('sales_orders', 'pre_order_notes')) {
                $table->text('pre_order_notes')->nullable()->after('dp_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn([
                'is_pre_order',
                'pre_order_eta',
                'dp_amount',
                'dp_paid',
                'dp_status',
                'pre_order_notes'
            ]);
        });
    }
};
