<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bundle_promos', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('bundle_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('original_price', 15, 2)->default(0);
            $table->decimal('bundle_price', 15, 2)->default(0);
            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('bundle_promo_items', function (Blueprint $table) {
            $table->id();
            $table->string('bundle_promo_id');
            $table->string('supplier_product_id');
            $table->integer('qty')->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('bundle_promo_id')
                ->references('id')
                ->on('bundle_promos')
                ->onDelete('cascade');

            $table->foreign('supplier_product_id')
                ->references('id')
                ->on('supplier_products')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bundle_promo_items');
        Schema::dropIfExists('bundle_promos');
    }
};
