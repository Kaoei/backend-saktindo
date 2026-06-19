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
        Schema::create('in_bounds', function (Blueprint $table) {
            $table->string('id', 30)->primary();

            $table->string('supplier_id', 30);
            $table->string('supplier_product_id', 30);

            $table->integer('qty_received');

            $table->date('received_date');

            $table->string('status')->default('pending');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('supplier_id')
                ->references('id')
                ->on('suppliers');

            $table->foreign('supplier_product_id')
                ->references('id')
                ->on('supplier_products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('in_bounds');
    }
};
