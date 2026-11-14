<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('supplier_po_payments')) {
            Schema::table('supplier_po_payments', function (Blueprint $table) {
                $table->string('created_by', 50)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('supplier_po_payments')) {
            Schema::table('supplier_po_payments', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable()->change();
            });
        }
    }
};
