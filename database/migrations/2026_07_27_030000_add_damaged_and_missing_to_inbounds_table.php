<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('in_bounds', function (Blueprint $table) {
            $table->integer('qty_damaged')->default(0)->after('qty_received');
            $table->integer('qty_missing')->default(0)->after('qty_damaged');
            $table->text('notes')->nullable()->after('supplier_po_id');
        });
    }

    public function down(): void
    {
        Schema::table('in_bounds', function (Blueprint $table) {
            $table->dropColumn(['qty_damaged', 'qty_missing', 'notes']);
        });
    }
};
