<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_payments', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('receiving_account');
            }
            if (!Schema::hasColumn('invoice_payments', 'giro_number')) {
                $table->string('giro_number')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('invoice_payments', 'giro_due_date')) {
                $table->date('giro_due_date')->nullable()->after('giro_number');
            }
            if (!Schema::hasColumn('invoice_payments', 'giro_status')) {
                $table->enum('giro_status', ['pending', 'cleared', 'rejected'])->default('pending')->nullable()->after('giro_due_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'giro_number', 'giro_due_date', 'giro_status']);
        });
    }
};
