<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. SALES ORDERS
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('sales_orders')) {
            Schema::create('sales_orders', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('customer_id')->nullable();
                $table->string('customer_name');
                $table->string('customer_po_number')->unique();
                $table->date('po_date')->nullable();
                $table->date('order_date');

                // JS / SJB / Toko sekitar
                $table->enum('sales_type', [
                    'js',
                    'sjb',
                    'nearby_store'
                ])->default('js');

                $table->enum('order_status', [
                    'draft',
                    'stock_check',
                    'pending_stock',
                    'ready_invoice',
                    'invoiced',
                    'partial_delivery',
                    'delivered',
                    'completed',
                    'cancelled'
                ])->default('draft');

                $table->enum('stock_status', [
                    'unchecked',
                    'available',
                    'pending'
                ])->default('unchecked');

                // task otomatis gudang
                $table->string('warehouse_task_reference')->nullable();

                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('grand_total', 15, 2)->default(0);

                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes(); // Mengamankan data SO jika di-cancel/delete

                $table->foreign('customer_id')
                    ->references('id')
                    ->on('master_customers')
                    ->nullOnDelete();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 2. SALES ORDER ITEMS
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('sales_order_items')) {
            Schema::create('sales_order_items', function (Blueprint $table) {
                $table->id();
                $table->string('sales_order_id');
                $table->string('product_code')->nullable();
                $table->string('product_name');
                $table->string('unit')->default('pcs');
                $table->decimal('quantity', 15, 2);

                // Pengiriman bertahap & cek stok
                $table->decimal('delivered_qty', 15, 2)->default(0);
                $table->decimal('available_stock', 15, 2)->default(0);

                $table->enum('stock_status', [
                    'unchecked',
                    'available',
                    'pending'
                ])->default('unchecked');

                $table->decimal('unit_price', 15, 2)->default(0);
                $table->decimal('line_total', 15, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('sales_order_id')
                    ->references('id')
                    ->on('sales_orders')
                    ->cascadeOnDelete();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 3. INVOICES
        |--------------------------------------------------------------------------
        */
        if (Schema::hasTable('invoices') && !Schema::hasColumn('invoices', 'due_date')) {
            Schema::disableForeignKeyConstraints();
            Schema::dropIfExists('invoices');
            Schema::enableForeignKeyConstraints();
        }

        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('sales_order_id')->nullable();
                $table->string('invoice_number')->unique();
                $table->enum('invoice_type', ['normal', 'gabungan'])->default('normal');
                $table->enum('tax_type', ['js', 'sjb_non_pajak', 'sjb_pajak'])->default('js');
                $table->string('faktur_number')->nullable()->unique();
                $table->date('invoice_date');
                $table->date('due_date')->nullable();

                $table->enum('status', [
                    'draft',
                    'outstanding',
                    'paid',
                    'cancelled'
                ])->default('draft');

                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('grand_total', 15, 2)->default(0);
                $table->decimal('paid_amount', 15, 2)->default(0);
                $table->decimal('outstanding_amount', 15, 2)->default(0);

                $table->timestamps();
                $table->softDeletes();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 4. INVOICE SALES ORDERS (Pivot untuk Invoice Gabungan)
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('invoice_sales_orders')) {
            Schema::create('invoice_sales_orders', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_id');
                $table->string('sales_order_id');

                $table->foreign('invoice_id')
                    ->references('id')
                    ->on('invoices')
                    ->cascadeOnDelete();

                $table->foreign('sales_order_id')
                    ->references('id')
                    ->on('sales_orders')
                    ->cascadeOnDelete();

                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 5. DELIVERY NOTES (Surat Jalan)
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('delivery_notes')) {
            Schema::create('delivery_notes', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('invoice_id');
                $table->string('delivery_note_number')->unique();
                $table->date('delivery_date');

                $table->enum('status', [
                    'draft',
                    'process',
                    'delivered',
                    'cancelled'
                ])->default('draft');

                $table->string('pic_sales')->nullable();
                $table->string('pic_gudang')->nullable();
                $table->text('notes')->nullable();

                // Audit Log: Menghitung berapa kali Surat Jalan/A7 di-reprint
                $table->unsignedInteger('print_count')->default(0);

                $table->timestamps();
                $table->softDeletes();

                $table->foreign('invoice_id')
                    ->references('id')
                    ->on('invoices')
                    ->cascadeOnDelete();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 6. DELIVERY NOTE ITEMS
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('delivery_note_items')) {
            Schema::create('delivery_note_items', function (Blueprint $table) {
                $table->id();
                $table->string('delivery_note_id');
                $table->foreignId('sales_order_item_id')
                    ->constrained('sales_order_items')
                    ->cascadeOnDelete();
                $table->decimal('qty_sent', 15, 2);
                $table->timestamps();

                $table->foreign('delivery_note_id')
                    ->references('id')
                    ->on('delivery_notes')
                    ->cascadeOnDelete();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 7. SALES RETURNS (Dokumen Retur Utama)
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('sales_returns')) {
            Schema::create('sales_returns', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('delivery_note_id');
                $table->date('return_date');
                $table->date('received_date')->nullable();

                $table->enum('status', [
                    'requested',
                    'approved',
                    'received',
                    'cancelled'
                ])->default('requested');

                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('delivery_note_id')
                    ->references('id')
                    ->on('delivery_notes')
                    ->cascadeOnDelete();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 8. SALES RETURN ITEMS (Detail Barang yang Diretur)
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('sales_return_items')) {
            Schema::create('sales_return_items', function (Blueprint $table) {
                $table->id();
                $table->string('sales_return_id');

                // Relasi ke item SO asal untuk kalkulasi sisa/stok
                $table->foreignId('sales_order_item_id')
                    ->constrained('sales_order_items')
                    ->cascadeOnDelete();

                $table->decimal('qty_returned', 15, 2);
                $table->string('reason')->nullable(); // Alasan retur (cacat, salah ukuran, dll)
                $table->timestamps();

                $table->foreign('sales_return_id')
                    ->references('id')
                    ->on('sales_returns')
                    ->cascadeOnDelete();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 9. INVOICE PAYMENTS
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('invoice_payments')) {
            Schema::create('invoice_payments', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('invoice_id');
                $table->string('payment_number')->unique();
                $table->date('payment_date');

                $table->enum('method', [
                    'cash',
                    'transfer_bank',
                    'qris',
                    'giro'
                ]);

                $table->enum('receiving_account', ['js', 'sjb']);
                $table->decimal('amount', 15, 2);
                $table->string('reference_number')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('invoice_id')
                    ->references('id')
                    ->on('invoices')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Drop dilakukan dengan urutan terbalik untuk menghindari Foreign Key Constraint error
        Schema::dropIfExists('invoice_payments');
        Schema::dropIfExists('sales_return_items');
        Schema::dropIfExists('sales_returns');
        Schema::dropIfExists('delivery_note_items');
        Schema::dropIfExists('delivery_notes');
        Schema::dropIfExists('invoice_sales_orders');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
    }
};