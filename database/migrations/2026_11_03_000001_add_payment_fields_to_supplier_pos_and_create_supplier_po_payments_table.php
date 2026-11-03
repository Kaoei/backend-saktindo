<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_pos', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_pos', 'due_date')) {
                $table->date('due_date')->nullable()->after('order_date');
            }
            if (!Schema::hasColumn('supplier_pos', 'paid_amount')) {
                $table->decimal('paid_amount', 15, 2)->default(0)->after('total_amount');
            }
            if (!Schema::hasColumn('supplier_pos', 'outstanding_amount')) {
                $table->decimal('outstanding_amount', 15, 2)->default(0)->after('paid_amount');
            }
            if (!Schema::hasColumn('supplier_pos', 'payment_status')) {
                $table->string('payment_status', 30)->default('unpaid')->after('outstanding_amount');
            }
        });

        // Initialize existing supplier_pos outstanding_amount to match total_amount
        DB::statement("UPDATE supplier_pos SET outstanding_amount = total_amount WHERE (outstanding_amount = 0 OR outstanding_amount IS NULL) AND total_amount > 0 AND (payment_status IS NULL OR payment_status = 'unpaid')");

        if (!Schema::hasTable('supplier_po_payments')) {
            Schema::create('supplier_po_payments', function (Blueprint $table) {
                $table->id();
                $table->string('supplier_po_id', 30)->index();
                $table->string('payment_number', 50)->unique();
                $table->date('payment_date');
                $table->decimal('amount', 15, 2);
                $table->string('payment_method', 50)->default('transfer_bank');
                $table->string('bank_name', 100)->nullable();
                $table->string('bank_account_number', 100)->nullable();
                $table->string('reference_number', 100)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('supplier_po_id')->references('id')->on('supplier_pos')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_po_payments');

        Schema::table('supplier_pos', function (Blueprint $table) {
            $colsToDrop = [];
            foreach (['payment_status', 'outstanding_amount', 'paid_amount', 'due_date'] as $col) {
                if (Schema::hasColumn('supplier_pos', $col)) {
                    $colsToDrop[] = $col;
                }
            }
            if (!empty($colsToDrop)) {
                $table->dropColumn($colsToDrop);
            }
        });
    }
};

