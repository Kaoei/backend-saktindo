<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_pos', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('supplier_id', 30);
            $table->string('po_number')->unique();
            $table->date('order_date');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('status', ['pending', 'received', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
        });

        Schema::create('supplier_po_items', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_po_id', 30);
            $table->string('supplier_product_id', 30);
            $table->integer('qty');
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('discount', 5, 2)->default(0); // discount in percentage
            $table->timestamps();

            $table->foreign('supplier_po_id')->references('id')->on('supplier_pos')->cascadeOnDelete();
            $table->foreign('supplier_product_id')->references('id')->on('supplier_products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_po_items');
        Schema::dropIfExists('supplier_pos');
    }
};
