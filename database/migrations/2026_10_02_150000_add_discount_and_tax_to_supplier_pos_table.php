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
        Schema::table('supplier_pos', function (Blueprint $table) {
            $table->decimal('subtotal', 15, 2)->default(0)->after('order_date');
            $table->decimal('additional_discount', 15, 2)->default(0)->after('subtotal');
            $table->string('tax_type', 20)->default('non_pajak')->after('additional_discount');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('tax_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_pos', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'additional_discount', 'tax_type', 'tax_amount']);
        });
    }
};
