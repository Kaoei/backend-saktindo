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
        Schema::create('master_customers', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('nama_customer');
            $table->string('nama_pic');
            $table->string('nomor_hp', 20);
            $table->string('email')->nullable();

            $table->text('alamat');
            $table->string('kota');
            $table->string('npwp')->nullable();

            $table->enum('tipe_customer', [
                'perorangan',
                'perusahaan'
            ])->default('perusahaan');

            $table->integer('termin')->default(0);

            $table->decimal('limit_piutang', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_customers');
    }
};
