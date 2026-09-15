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
        if (!Schema::hasTable('invoice_sales_orders')) {
            Schema::create('invoice_sales_orders', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_id');
                $table->string('sales_order_id');

                $table->foreign('invoice_id')
                    ->references('id')
                    ->on('invoices')
                    ->cascadeOnDelete();

                $table->foreign('sales_order_id')
                    ->references('id')
                    ->on('sales_orders')
                    ->cascadeOnDelete();

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_sales_orders');
    }
};
