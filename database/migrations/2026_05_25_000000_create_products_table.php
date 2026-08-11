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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            
            // Primary TikTok identifiers
            $table->string('product_id')->nullable()->index();
            $table->string('sku_id')->nullable()->index();
            $table->string('seller_sku')->nullable()->index();
            
            // Core Product Data
            $table->string('category')->nullable();
            $table->string('product_name');
            $table->string('variation_value')->nullable();
            $table->text('product_description')->nullable();
            $table->string('brand')->nullable();
            $table->decimal('price', 15, 2)->default(0.00);
            $table->integer('pre_order_time')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('minimum_order_quantity')->default(1);
            
            // Shipping and dimensions
            $table->integer('parcel_weight')->nullable(); // in grams
            $table->integer('parcel_length')->nullable(); // in cm
            $table->integer('parcel_width')->nullable();  // in cm
            $table->integer('parcel_height')->nullable(); // in cm
            
            // Flags
            $table->string('cod', 10)->default('Y'); // 'Y' or 'N'
            
            // Images
            $table->text('main_image')->nullable();
            $table->text('image_2')->nullable();
            $table->text('image_3')->nullable();
            $table->text('image_4')->nullable();
            $table->text('image_5')->nullable();
            $table->text('image_6')->nullable();
            $table->text('image_7')->nullable();
            $table->text('image_8')->nullable();
            $table->text('image_9')->nullable();
            $table->text('size_chart')->nullable();
            
            // URLs
            $table->text('tts_product_url')->nullable();
            $table->text('toko_product_url')->nullable();
            
            // Properties (Custom TikTok Attributes)
            $table->string('warranty_type')->nullable();            // product_property/100107
            $table->string('with_battery')->nullable();             // product_property/100108
            $table->string('battery_in_the_product')->nullable();   // product_property/100110
            $table->string('with_magnet')->nullable();              // product_property/100112
            $table->string('plug_type')->nullable();                // product_property/100113
            $table->string('product_condition')->nullable();        // product_property/100115
            $table->string('materials')->nullable();                // product_property/100157
            $table->string('run_time')->nullable();                 // product_property/100293
            $table->string('robot_vacuum_features')->nullable();    // product_property/100294
            $table->string('iron_features')->nullable();            // product_property/100305
            $table->string('contains_dangerous_goods')->nullable(); // product_property/101734
            
            // Qualifications
            $table->text('sni_certificate')->nullable();            // qualification/6956786487312533254
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
