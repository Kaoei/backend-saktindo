<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Goods_receipt extends Model
{
    //
        use SoftDeletes;

    protected $primaryKey = 'id_goods_receipt';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_goods_receipt',
        'receipt_no',
        'id_supplier',
        'id_product',
        'qty_received',
        'id_rack',
        'status',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier', 'id_supplier');
    }

    public function product()
    {
        return $this->belongsTo(product::class, 'id_product', 'id_product');
    }

    public function rack()
    {
        return $this->belongsTo(Rak::class, 'id_rack', 'id_rack');
    }
}
