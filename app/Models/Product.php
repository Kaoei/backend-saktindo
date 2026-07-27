<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'product_id',
        'category',
        'product_name',
        'sku_id',
        'variation_value',
        'product_description',
        'brand',
        'price',
        'pre_order_time',
        'quantity',
        'seller_sku',
        'minimum_order_quantity',
        'parcel_weight',
        'parcel_length',
        'parcel_width',
        'parcel_height',
        'cod',
        'main_image',
        'image_2',
        'image_3',
        'image_4',
        'image_5',
        'image_6',
        'image_7',
        'image_8',
        'image_9',
        'size_chart',
        'tts_product_url',
        'toko_product_url',
        'warranty_type',
        'with_battery',
        'battery_in_the_product',
        'with_magnet',
        'plug_type',
        'product_condition',
        'materials',
        'run_time',
        'robot_vacuum_features',
        'iron_features',
        'contains_dangerous_goods',
        'sni_certificate',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'pre_order_time' => 'integer',
        'quantity' => 'integer',
        'minimum_order_quantity' => 'integer',
        'parcel_weight' => 'integer',
        'parcel_length' => 'integer',
        'parcel_width' => 'integer',
        'parcel_height' => 'integer',
    ];

    /**
     * Map TikTok Sheet Headers (Row 1 keys) to Database column names
     */
    public static function getHeaderMap(): array
    {
        return [
            'product_id' => 'product_id',
            'category' => 'category',
            'product_name' => 'product_name',
            'sku_id' => 'sku_id',
            'variation_value' => 'variation_value',
            'product_description' => 'product_description',
            'brand' => 'brand',
            'price' => 'price',
            'pre_order_time' => 'pre_order_time',
            'quantity' => 'quantity',
            'seller_sku' => 'seller_sku',
            'minimum_order_quantity' => 'minimum_order_quantity',
            'parcel_weight' => 'parcel_weight',
            'parcel_length' => 'parcel_length',
            'parcel_width' => 'parcel_width',
            'parcel_height' => 'parcel_height',
            'cod' => 'cod',
            'main_image' => 'main_image',
            'image_2' => 'image_2',
            'image_3' => 'image_3',
            'image_4' => 'image_4',
            'image_5' => 'image_5',
            'image_6' => 'image_6',
            'image_7' => 'image_7',
            'image_8' => 'image_8',
            'image_9' => 'image_9',
            'size_chart' => 'size_chart',
            'tts_product_url' => 'tts_product_url',
            'toko_product_url' => 'toko_product_url',
            'product_property/100107' => 'warranty_type',
            'product_property/100108' => 'with_battery',
            'product_property/100110' => 'battery_in_the_product',
            'product_property/100112' => 'with_magnet',
            'product_property/100113' => 'plug_type',
            'product_property/100115' => 'product_condition',
            'product_property/100157' => 'materials',
            'product_property/100293' => 'run_time',
            'product_property/100294' => 'robot_vacuum_features',
            'product_property/100305' => 'iron_features',
            'product_property/101734' => 'contains_dangerous_goods',
            'qualification/6956786487312533254' => 'sni_certificate',
        ];
    }
}
