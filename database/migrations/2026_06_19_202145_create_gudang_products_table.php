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
     Schema::create('gudang_products', function (Blueprint $table) {
    $table->string('id', 30)->primary();

    $table->string('supplier_product_id', 30);
    $table->string('rack_id')->nullable();

    $table->unsignedInteger('qty')->default(0);
    $table->string('status')->default('stored')->index();

    $table->timestamps();
    $table->softDeletes();

    $table->foreign('supplier_product_id')
        ->references('id')
        ->on('supplier_products');

    $table->foreign('rack_id')
        ->references('rak_kode')
        ->on('raks')
        ->nullOnDelete();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gudang_products');
    }
};
