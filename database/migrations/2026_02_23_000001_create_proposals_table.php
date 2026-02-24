<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('client_id', 30);
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->string('created_by', 30);
            $table->foreign('created_by')->references('id')->on('users');
            $table->string('title');
            $table->text('description');
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending', 'follow_up', 'approved', 'declined'])->default('pending');
            $table->date('follow_up_deadline');
            $table->timestamp('email_sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
