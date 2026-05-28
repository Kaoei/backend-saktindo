<?php

namespace App\Models;

use App\Traits\HasCustomCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPurchaseHistory extends Model
{
    use HasFactory, HasCustomCode;

    public const CODE_PREFIX = 'SPH';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'supplier_id',
        'purchase_date',
        'invoice_number',
        'item_name',
        'quantity',
        'unit_price',
        'total_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
