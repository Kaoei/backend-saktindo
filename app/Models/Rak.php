<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rak extends Model
{
    use SoftDeletes;

    protected $table = 'raks';

    protected $primaryKey = 'rak_kode';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'rak_kode',
        'location',
        'gudang',
        'is_temporary',
    ];

    protected $casts = [
        'is_temporary' => 'boolean',
    ];
}