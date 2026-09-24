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
        if (!Schema::hasTable('variants')) {
            Schema::create('variants', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        } else {
            Schema::table('variants', function (Blueprint $table) {
                if (!Schema::hasColumn('variants', 'name')) {
                    $table->string('name')->nullable()->after('id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('variants') && Schema::hasColumn('variants', 'name')) {
            Schema::table('variants', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }
};
