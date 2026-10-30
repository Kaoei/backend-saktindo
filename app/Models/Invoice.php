<?php

namespace App\Models;

use App\Traits\HasCustomCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    use HasFactory, HasCustomCode;

    public const CODE_PREFIX = 'INV';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'sales_order_id',
        'invoice_number',
        'invoice_type',
        'tax_type',
        'faktur_number',
        'faktur_checked',
        'faktur_checked_at',
        'transferred_from_invoice_id',
        'transferred_from_invoice_number',
        'transferred_to_invoice_id',
        'transferred_to_invoice_number',
        'transfer_reason',
        'transferred_at',
        'transferred_by',
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
        'faktur_checked' => 'boolean',
        'faktur_checked_at' => 'datetime',
        'transferred_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
    ];

    public function transferredFromInvoice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'transferred_from_invoice_id');
    }

    public function transferredToInvoice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'transferred_to_invoice_id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function salesOrders(): BelongsToMany
    {
        return $this->belongsToMany(SalesOrder::class, 'invoice_sales_orders');
    }

    public function deliveryNote(): HasOne
    {
        return $this->hasOne(DeliveryNote::class);
    }

    public function deliveryNotes(): HasMany
    {
        return $this->hasMany(DeliveryNote::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function warehouseTask(): HasOne
    {
        return $this->hasOne(WarehouseTask::class);
    }
}
