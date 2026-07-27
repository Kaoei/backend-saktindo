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
        Schema::create('internal_invoices', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->enum('from_store', ['js', 'sjb']);
            $table->enum('to_store', ['js', 'sjb']);
            $table->date('invoice_date');
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_invoices');
    }
};
