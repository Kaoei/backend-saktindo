<?php

namespace App\Models;

use App\Traits\HasCustomCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    use HasFactory, HasCustomCode;

    public const CODE_PREFIX = 'INV';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'sales_order_id',
        'invoice_number',
        'tax_type',
        'faktur_number',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'tax_amount',
        'grand_total',
        'paid_amount',
        'outstanding_amount',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function deliveryNote(): HasOne
    {
        return $this->hasOne(DeliveryNote::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }
}
