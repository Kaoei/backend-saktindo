<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('variants') && Schema::hasColumn('variants', 'produk_id')) {
            try {
                Schema::table('variants', function (Blueprint $table) {
                    $table->unsignedBigInteger('produk_id')->nullable()->default(null)->change();
                });
            } catch (\Throwable $e) {
                try {
                    DB::statement('ALTER TABLE `variants` MODIFY `produk_id` BIGINT UNSIGNED NULL DEFAULT NULL');
                } catch (\Throwable $e2) {
                    try {
                        DB::statement('ALTER TABLE `variants` MODIFY `produk_id` VARCHAR(255) NULL DEFAULT NULL');
                    } catch (\Throwable $e3) {
                        // Ignore if already nullable or unsupported
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep nullable for safety and compatibility
    }
};
