<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class product extends Model
{
    //
       use SoftDeletes;

    protected $primaryKey = 'id_product';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_product',
        'sku',
        'name',
        'desc',
        'stock',
        'Tgl_masuk',
    ];

    public function goodsReceipts()
    {
        return $this->hasMany(Goods_receipt::class, 'id_product', 'id_product');
    }
}
