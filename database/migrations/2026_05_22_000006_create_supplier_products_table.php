<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_products', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('supplier_id', 30)->index();
            $table->string('sku')->nullable()->index();
            $table->string('part_number')->nullable()->index();
            $table->string('item_name');
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('last_purchase_price', 15, 2)->default(0);
            $table->unsignedInteger('minimum_order_qty')->default(1);
            $table->unsignedInteger('lead_time_days')->default(0);
            $table->string('status')->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_products');
    }
};
