<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'company_name')) {
                $table->string('company_name')->nullable()->after('vendor_type');
            }

            if (! Schema::hasColumn('suppliers', 'pic_name')) {
                $table->string('pic_name')->nullable()->after('company_name');
            }

            if (! Schema::hasColumn('suppliers', 'pic_phone')) {
                $table->string('pic_phone')->nullable()->after('pic_name');
            }

            if (! Schema::hasColumn('suppliers', 'pic_email')) {
                $table->string('pic_email')->nullable()->after('pic_phone');
            }

            if (! Schema::hasColumn('suppliers', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('tax_number');
            }

            if (! Schema::hasColumn('suppliers', 'bank_account_name')) {
                $table->string('bank_account_name')->nullable()->after('bank_name');
            }

            if (! Schema::hasColumn('suppliers', 'bank_account_number')) {
                $table->string('bank_account_number')->nullable()->after('bank_account_name');
            }

            if (! Schema::hasColumn('suppliers', 'payment_due_days')) {
                $table->unsignedInteger('payment_due_days')->default(0)->after('bank_account_number');
            }

            if (! Schema::hasColumn('suppliers', 'debt_limit')) {
                $table->decimal('debt_limit', 15, 2)->default(0)->after('payment_due_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            foreach ([
                'debt_limit',
                'payment_due_days',
                'bank_account_number',
                'bank_account_name',
                'bank_name',
                'pic_email',
                'pic_phone',
                'pic_name',
                'company_name',
            ] as $column) {
                if (Schema::hasColumn('suppliers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
