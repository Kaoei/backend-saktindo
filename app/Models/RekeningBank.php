<?php

namespace App\Models;

use App\Traits\HasCustomCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekeningBank extends Model
{
    use HasFactory, HasCustomCode;

    protected $table = 'rekenings';

    public const CODE_PREFIX = 'REK';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'bank_name',
        'account_name',
        'account_number',
        'toko',
    ];
}
