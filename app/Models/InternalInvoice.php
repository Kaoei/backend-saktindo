<?php

namespace App\Models;

use App\Traits\HasCustomCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternalInvoice extends Model
{
    use HasFactory, HasCustomCode;

    public const CODE_PREFIX = 'INV-INT';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'from_store',
        'to_store',
        'invoice_date',
        'amount',
        'description',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'amount' => 'decimal:2',
    ];
}
