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
        Schema::table('master_customers', function (Blueprint $table) {
            $table->text('alamat')->nullable()->change();
            $table->string('kota')->nullable()->change();
            $table->string('nomor_hp', 30)->nullable()->change();
            $table->string('nama_pic')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_customers', function (Blueprint $table) {
            $table->text('alamat')->nullable(false)->change();
            $table->string('kota')->nullable(false)->change();
        });
    }
};
