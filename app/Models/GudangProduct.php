<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GudangProduct extends Model
{
    use SoftDeletes;

    protected $table = 'gudang_products';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'supplier_product_id',
        'rack_id',
        'gudang_type',
        'qty',
        'status',
    ];

public static function generateId()
{
    $last = self::withTrashed()
        ->orderBy('id', 'desc')
        ->first();

    if (!$last) {
        return 'GPROD-000001';
    }

    $number = (int) substr($last->id, 6);

    return 'GPROD-' . str_pad(
        $number + 1,
        6,
        '0',
        STR_PAD_LEFT
    );
}
    public function supplierProduct()
    {
        return $this->belongsTo(
            SupplierProduct::class,
            'supplier_product_id',
            'id'
        );
    }

    public function rack()
    {
        return $this->belongsTo(
            Rak::class,
            'rack_id',
            'rak_kode'
        );
    }
}