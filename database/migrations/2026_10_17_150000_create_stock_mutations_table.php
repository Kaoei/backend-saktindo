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
        Schema::create('stock_mutations', function (Blueprint $table) {
            $table->id();
            $table->string('mutation_code', 50)->unique();
            $table->string('supplier_product_id', 30)->index();
            $table->string('gudang_product_id', 100)->nullable()->index();
            $table->string('rack_id', 50)->nullable()->index();
            $table->string('gudang_type', 20)->nullable();
            $table->dateTime('transaction_date')->index();
            $table->string('mutation_type', 30)->index(); // initial_stock, inbound, outbound, transfer, adjustment, return
            $table->string('reference_type', 100)->nullable();
            $table->string('reference_id', 100)->nullable();
            $table->string('reference_number', 100)->nullable()->index();
            $table->integer('qty_in')->default(0);
            $table->integer('qty_out')->default(0);
            $table->integer('balance')->default(0);
            $table->integer('rack_balance')->nullable();
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('balance_value', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('user_id', 30)->nullable();
            $table->timestamps();

            $table->foreign('supplier_product_id')
                ->references('id')
                ->on('supplier_products')
                ->cascadeOnDelete();

            $table->foreign('rack_id')
                ->references('rak_kode')
                ->on('raks')
                ->nullOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['supplier_product_id', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_mutations');
    }
};
