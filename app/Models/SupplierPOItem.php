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
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    public function supplierPo(): BelongsTo
    {
        return $this->belongsTo(SupplierPO::class, 'supplier_po_id', 'id');
    }

    public function supplierProduct(): BelongsTo
    {
        return $this->belongsTo(SupplierProduct::class);
    }
}
