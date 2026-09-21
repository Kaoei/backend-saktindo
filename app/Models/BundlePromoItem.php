<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BundlePromoItem extends Model
{
    use HasFactory;

    protected $table = 'bundle_promo_items';

    protected $fillable = [
        'bundle_promo_id',
        'supplier_product_id',
        'qty',
        'unit_price',
    ];

    protected $casts = [
        'qty' => 'integer',
        'unit_price' => 'decimal:2',
    ];

    public function bundlePromo(): BelongsTo
    {
        return $this->belongsTo(BundlePromo::class, 'bundle_promo_id', 'id');
    }

    public function supplierProduct(): BelongsTo
    {
        return $this->belongsTo(SupplierProduct::class, 'supplier_product_id', 'id');
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->unit_price * (int) $this->qty;
    }
}
