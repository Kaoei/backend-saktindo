<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Master_customer extends Model
{
    protected $table = 'master_customers';
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'nama_customer',
        'nama_pic',
        'nomor_hp',
        'email',
        'alamat',
        'kota',
        'npwp',
        'tipe_customer',
        'termin',
        'limit_piutang',
    ];
}
