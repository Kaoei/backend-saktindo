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
        'subtotal',
        'additional_discount',
        'tax_type',
        'tax_amount',
        'total_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'order_date' => 'date',
        'subtotal' => 'decimal:2',
        'additional_discount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function getDppAttribute(): float
    {
        $subtotal = (float) ($this->subtotal > 0 ? $this->subtotal : $this->total_amount);
        $discount = (float) ($this->additional_discount ?? 0);
        return max(0, $subtotal - $discount);
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
}
