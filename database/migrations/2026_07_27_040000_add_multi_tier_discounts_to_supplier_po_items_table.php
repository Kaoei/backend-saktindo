<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_po_items', function (Blueprint $table) {
            $table->decimal('discount_1', 5, 2)->default(0)->after('price');
            $table->decimal('discount_2', 5, 2)->default(0)->after('discount_1');
            $table->decimal('discount_3', 5, 2)->default(0)->after('discount_2');
            $table->decimal('discount_4', 5, 2)->default(0)->after('discount_3');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_po_items', function (Blueprint $table) {
            $table->dropColumn(['discount_1', 'discount_2', 'discount_3', 'discount_4']);
        });
    }
};
