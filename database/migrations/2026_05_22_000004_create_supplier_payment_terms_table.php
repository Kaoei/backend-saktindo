<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payment_terms', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('supplier_id', 30)->index();
            $table->string('name');
            $table->unsignedInteger('due_days')->default(0);
            $table->decimal('down_payment_percent', 5, 2)->default(0);
            $table->decimal('late_fee_percent', 5, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payment_terms');
    }
};
