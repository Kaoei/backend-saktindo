<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('customer_id')->nullable();
            $table->string('customer_name');
            $table->string('customer_po_number')->unique();
            $table->date('po_date')->nullable();
            $table->date('order_date');
            $table->enum('order_status', ['draft', 'stock_check', 'ready_to_invoice', 'pending_stock', 'invoiced', 'delivered', 'completed', 'cancelled'])->default('draft');
            $table->enum('stock_status', ['unchecked', 'available', 'pending'])->default('unchecked');
            $table->string('warehouse_task_reference')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('master_customers')->nullOnDelete();
        });

        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->string('sales_order_id');
            $table->string('product_code')->nullable();
            $table->string('product_name');
            $table->string('unit')->default('pcs');
            $table->decimal('quantity', 15, 2);
            $table->decimal('available_stock', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->enum('stock_status', ['unchecked', 'available', 'pending'])->default('unchecked');
            $table->timestamps();

            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->cascadeOnDelete();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('sales_order_id');
            $table->string('invoice_number')->unique();
            $table->enum('tax_type', ['js', 'sjb_non_pajak', 'sjb_pajak'])->default('js');
            $table->string('faktur_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->enum('status', ['draft', 'outstanding', 'paid'])->default('draft');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('outstanding_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->cascadeOnDelete();
        });

        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('invoice_id');
            $table->string('delivery_note_number')->unique();
            $table->date('delivery_date');
            $table->enum('status', ['draft', 'process', 'delivered', 'cancelled'])->default('draft');
            $table->string('pic_sales')->nullable();
            $table->string('pic_gudang')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
        });

        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('invoice_id');
            $table->string('payment_number')->unique();
            $table->date('payment_date');
            $table->enum('method', ['cash', 'transfer_bank', 'qris', 'giro']);
            $table->enum('receiving_account', ['js', 'sjb']);
            $table->decimal('amount', 15, 2);
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
        Schema::dropIfExists('delivery_notes');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
    }
};
