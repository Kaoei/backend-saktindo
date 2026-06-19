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
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->string('id_goods_receipt')->primary();

            $table->string('receipt_no')->unique();

            $table->string('id_supplier');
            $table->string('id_product');
            $table->integer('qty_received');
            $table->string('rak_kode')->nullable();

            $table->enum('status', [
                'draft_receiving',
                'pending_placement',
                'completed'
            ])->default('draft_receiving');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_supplier')
                ->references('id_supplier')
                ->on('suppliers');

            $table->foreign('id_product')
                ->references('id_product')
                ->on('products');

            $table->foreign('rak_kode')
            ->references('rak_kode')
            ->on('racks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};
