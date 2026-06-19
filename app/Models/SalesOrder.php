<?php

namespace App\Models;

use App\Traits\HasCustomCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesOrder extends Model
{
    use HasFactory, HasCustomCode;

    public const CODE_PREFIX = 'SO';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'customer_id',
        'customer_name',
        'customer_po_number',
        'po_date',
        'order_date',
        'order_status',
        'stock_status',
        'warehouse_task_reference',
        'notes',
        'subtotal',
        'tax_amount',
        'grand_total',
    ];

    protected $casts = [
        'po_date' => 'date',
        'order_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Master_customer::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }
}
