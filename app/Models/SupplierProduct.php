<?php

namespace App\Models;

use App\Traits\HasCustomCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierProduct extends Model
{
    use HasFactory, HasCustomCode;

    public const CODE_PREFIX = 'SPR';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'supplier_id',
        'sku',
        'part_number',
        'item_name',
        'category',
        'brand',
        'unit',
        'last_purchase_price',
        'minimum_order_qty',
        'lead_time_days',
        'status',
        'notes',
    ];

    protected $casts = [
        'last_purchase_price' => 'decimal:2',
        'minimum_order_qty' => 'integer',
        'lead_time_days' => 'integer',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
