<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierPO extends Model
{
    use HasFactory;

    protected $table = 'supplier_pos';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'supplier_id',
        'po_number',
        'reference_number',
        'order_date',
        'due_date',
        'subtotal',
        'additional_discount',
        'tax_type',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'outstanding_amount',
        'payment_status',
        'status',
        'notes',
    ];

    protected $casts = [
        'order_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'additional_discount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
    ];

    public function getDppAttribute(): float
    {
        $subtotal = (float) ($this->subtotal > 0 ? $this->subtotal : $this->total_amount);
        $discount = (float) ($this->additional_discount ?? 0);
        return max(0, $subtotal - $discount);
    }

    public function getInvoiceNumberAttribute(): string
    {
        return $this->reference_number ?: $this->po_number;
    }

    public function getEffectiveOutstandingAmountAttribute(): float
    {
        if ($this->outstanding_amount !== null && (float) $this->outstanding_amount >= 0) {
            return (float) $this->outstanding_amount;
        }
        return max(0, (float) $this->total_amount - (float) ($this->paid_amount ?? 0));
    }

    public static function generateId()
    {
        $last = self::orderBy('id', 'desc')->first();

        if (!$last) {
            return 'SPO-000001';
        }

        $number = (int) substr($last->id, 4);

        return 'SPO-' . str_pad($number + 1, 6, '0', STR_PAD_LEFT);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierPOItem::class, 'supplier_po_id', 'id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPoPayment::class, 'supplier_po_id', 'id');
    }
}
