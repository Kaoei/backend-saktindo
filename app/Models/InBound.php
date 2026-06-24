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
    'received_date',
    'status',
]; 
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
}
