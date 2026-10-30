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
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('invoices', 'transferred_from_invoice_id')) {
                    $table->string('transferred_from_invoice_id')->nullable()->after('faktur_number');
                }
                if (!Schema::hasColumn('invoices', 'transferred_from_invoice_number')) {
                    $table->string('transferred_from_invoice_number')->nullable()->after('transferred_from_invoice_id');
                }
                if (!Schema::hasColumn('invoices', 'transferred_to_invoice_id')) {
                    $table->string('transferred_to_invoice_id')->nullable()->after('transferred_from_invoice_number');
                }
                if (!Schema::hasColumn('invoices', 'transferred_to_invoice_number')) {
                    $table->string('transferred_to_invoice_number')->nullable()->after('transferred_to_invoice_id');
                }
                if (!Schema::hasColumn('invoices', 'transfer_reason')) {
                    $table->text('transfer_reason')->nullable()->after('transferred_to_invoice_number');
                }
                if (!Schema::hasColumn('invoices', 'transferred_at')) {
                    $table->dateTime('transferred_at')->nullable()->after('transfer_reason');
                }
                if (!Schema::hasColumn('invoices', 'transferred_by')) {
                    $table->string('transferred_by')->nullable()->after('transferred_at');
                }
            });
        }

        if (Schema::hasTable('sales_orders')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('sales_orders', 'transfer_reason')) {
                    $table->text('transfer_reason')->nullable()->after('notes');
                }
                if (!Schema::hasColumn('sales_orders', 'transferred_at')) {
                    $table->dateTime('transferred_at')->nullable()->after('transfer_reason');
                }
                if (!Schema::hasColumn('sales_orders', 'transferred_by')) {
                    $table->string('transferred_by')->nullable()->after('transferred_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                $columns = [
                    'transferred_from_invoice_id',
                    'transferred_from_invoice_number',
                    'transferred_to_invoice_id',
                    'transferred_to_invoice_number',
                    'transfer_reason',
                    'transferred_at',
                    'transferred_by',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('invoices', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('sales_orders')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $columns = ['transfer_reason', 'transferred_at', 'transferred_by'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('sales_orders', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};

