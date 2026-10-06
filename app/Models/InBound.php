<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InBound extends Model
{
    protected $table = 'in_bounds';

protected $primaryKey = 'id';
public $incrementing = false;
protected $keyType = 'string';

protected $fillable = [
    'id',
    'supplier_id',
    'supplier_product_id',
    'qty_received',
    'qty_allocated',
    'qty_damaged',
    'qty_missing',
    'hpp',
    'supplier_po_id',
    'invoice_number',
    'received_date',
    'status',
    'notes',
]; 

protected $casts = [
    'qty_received' => 'integer',
    'qty_allocated' => 'integer',
    'qty_damaged' => 'integer',
    'qty_missing' => 'integer',
    'hpp' => 'decimal:2',
];

public function getRemainingQtyAttribute()
{
    return max(0, (int)$this->qty_received - (int)($this->qty_allocated ?? 0));
} 
    public static function generateId()
    {
        $last = self::orderBy('id', 'desc')->first();

        if (!$last) {
            return 'INB-000001';
        }

        $number = (int) substr($last->id, 4);

        return 'INB-' . str_pad($number + 1, 6, '0', STR_PAD_LEFT);
    }


public function supplier()
{
    return $this->belongsTo(Supplier::class);
}

public function supplierProduct()
{
    return $this->belongsTo(SupplierProduct::class);
}

public function supplierPo()
{
    return $this->belongsTo(SupplierPO::class, 'supplier_po_id', 'id');
}
}
