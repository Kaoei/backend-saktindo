<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gudang_products', function (Blueprint $table) {
            $table->string('id', 150)->change();
        });
    }

    public function down(): void
    {
        Schema::table('gudang_products', function (Blueprint $table) {
            $table->string('id', 30)->change();
        });
    }
};
