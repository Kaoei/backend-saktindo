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
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('faktur_checked')->default(false)->after('faktur_number');
            $table->timestamp('faktur_checked_at')->nullable()->after('faktur_checked');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['faktur_checked', 'faktur_checked_at']);
        });
    }
};
