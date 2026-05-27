<?php

namespace App\Models;

use App\Traits\HasCustomCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPaymentTerm extends Model
{
    use HasFactory, HasCustomCode;

    public const CODE_PREFIX = 'SPT';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'supplier_id',
        'name',
        'due_days',
        'down_payment_percent',
        'late_fee_percent',
        'is_default',
        'notes',
    ];

    protected $casts = [
        'due_days' => 'integer',
        'down_payment_percent' => 'decimal:2',
        'late_fee_percent' => 'decimal:2',
        'is_default' => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
