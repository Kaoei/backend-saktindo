<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('in_bounds', function (Blueprint $table) {
            $table->decimal('hpp', 15, 2)->default(0)->after('qty_received');
            $table->string('supplier_po_id', 30)->nullable()->after('hpp');
        });
    }

    public function down(): void
    {
        Schema::table('in_bounds', function (Blueprint $table) {
            $table->dropColumn(['hpp', 'supplier_po_id']);
        });
    }
};
