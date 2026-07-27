<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPOItem extends Model
{
    use HasFactory;

    protected $table = 'supplier_po_items';

    protected $fillable = [
        'supplier_po_id',
        'supplier_product_id',
        'qty',
        'price',
        'discount',
        'discount_1',
        'discount_2',
        'discount_3',
        'discount_4',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
        'discount_1' => 'decimal:2',
        'discount_2' => 'decimal:2',
        'discount_3' => 'decimal:2',
        'discount_4' => 'decimal:2',
    ];

    public function supplierPo(): BelongsTo
    {
        return $this->belongsTo(SupplierPO::class, 'supplier_po_id', 'id');
    }

    public function supplierProduct(): BelongsTo
    {
        return $this->belongsTo(SupplierProduct::class);
    }

    /**
     * Calculate unit net price after applying 4-tier compounded discount.
     */
    public function getNetUnitPriceAttribute(): float
    {
        $d1 = (float) ($this->discount_1 > 0 ? $this->discount_1 : ($this->discount > 0 ? $this->discount : 0));
        $d2 = (float) ($this->discount_2 ?? 0);
        $d3 = (float) ($this->discount_3 ?? 0);
        $d4 = (float) ($this->discount_4 ?? 0);

        $price = (float) $this->price;
        $net = $price * (1 - $d1 / 100) * (1 - $d2 / 100) * (1 - $d3 / 100) * (1 - $d4 / 100);
        return $net;
    }

    /**
     * Calculate line subtotal.
     */
    public function getSubtotalAttribute(): float
    {
        return $this->net_unit_price * (int) $this->qty;
    }

    /**
     * Get formatted discount string e.g. "15+10+5+5%" or "15%".
     */
    public function getFormattedDiscountAttribute(): string
    {
        $discounts = [];
        $d1 = (float) ($this->discount_1 > 0 ? $this->discount_1 : ($this->discount > 0 ? $this->discount : 0));
        if ($d1 > 0) $discounts[] = (float)$d1;
        if ((float)$this->discount_2 > 0) $discounts[] = (float)$this->discount_2;
        if ((float)$this->discount_3 > 0) $discounts[] = (float)$this->discount_3;
        if ((float)$this->discount_4 > 0) $discounts[] = (float)$this->discount_4;

        if (empty($discounts)) {
            return '0%';
        }

        return implode('+', $discounts) . '%';
    }
}
