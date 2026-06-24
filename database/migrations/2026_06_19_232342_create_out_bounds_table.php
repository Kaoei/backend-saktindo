<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('out_bounds', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->string('warehouse_task_id');
            $table->string('gudang_product_id');

            $table->unsignedInteger('qty');

            $table->date('outbound_date');

            $table->enum('delivery_type', [
                'full',
                'partial'
            ])->default('full');

            $table->enum('status', [
                'draft',
                'completed'
            ])->default('draft');

            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('warehouse_task_id')
                ->references('id')
                ->on('warehouse_tasks')
                ->cascadeOnDelete();

            $table->foreign('gudang_product_id')
                ->references('id')
                ->on('gudang_products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('out_bounds');
    }
};