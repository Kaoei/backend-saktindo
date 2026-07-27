<?php

namespace App\Models;

use App\Traits\HasCustomCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Retur extends Model
{
    use HasFactory, HasCustomCode;

    public const CODE_PREFIX = 'RET';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'sales_order_id',
        'return_date',
        'received_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'return_date' => 'date',
        'received_date' => 'date',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }
}
