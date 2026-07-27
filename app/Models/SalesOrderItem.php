<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'product_code',
        'product_name',
        'unit',
        'quantity',
        'delivered_qty',
        'available_stock',
        'unit_price',
        'discount',
        'line_total',
        'stock_status',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'delivered_qty' => 'decimal:2',
        'available_stock' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function deliveryNoteItems()
    {
        return $this->hasMany(DeliveryNoteItem::class);
    }
}
