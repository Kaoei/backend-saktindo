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
        Schema::create('warehouse_tasks', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->string('sales_order_id');
            $table->string('invoice_id');

            $table->string('assigned_to')->nullable();

            $table->enum('status', [
                'waiting',
                'process',
                'completed'
            ])->default('waiting');

            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_tasks');
    }
};
