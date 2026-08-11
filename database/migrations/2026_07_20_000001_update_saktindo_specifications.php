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
        // 1. Update sales_orders table
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->enum('toko', ['js', 'sjb'])->nullable()->after('customer_id');
            $table->enum('jenis_invoice', ['normal', 'gabungan'])->default('normal')->after('toko');
        });

        // 2. Update raks table
        Schema::table('raks', function (Blueprint $table) {
            $table->enum('gudang', ['js', 'sjb'])->default('js')->after('location');
            $table->boolean('is_temporary')->default(false)->after('gudang');
        });

        // 3. Update warehouse_tasks table
        Schema::table('warehouse_tasks', function (Blueprint $table) {
            $table->enum('toko', ['js', 'sjb', 'ruko'])->nullable()->after('invoice_id');
            $table->enum('task_type', ['warehouse', 'finance'])->default('warehouse')->after('toko');
        });

        // 4. Create proforma_invoices table
        Schema::create('proforma_invoices', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('sales_order_id');
            $table->string('pi_number')->unique();
            $table->date('pi_date');
            $table->enum('status', ['draft', 'printed', 'paid'])->default('draft');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->cascadeOnDelete();
        });

        // 5. Create returs table
        Schema::create('returs', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('sales_order_id');
            $table->date('return_date');
            $table->date('received_date')->nullable();
            $table->enum('status', ['pending', 'received', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->cascadeOnDelete();
        });

        // 6. Create rekenings table
        Schema::create('rekenings', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_number');
            $table->enum('toko', ['js', 'sjb'])->default('js');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekenings');
        Schema::dropIfExists('returs');
        Schema::dropIfExists('proforma_invoices');

        Schema::table('warehouse_tasks', function (Blueprint $table) {
            $table->dropColumn(['toko', 'task_type']);
        });

        Schema::table('raks', function (Blueprint $table) {
            $table->dropColumn(['gudang', 'is_temporary']);
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn(['toko', 'jenis_invoice']);
        });
    }
};
