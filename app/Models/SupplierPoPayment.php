<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPoPayment extends Model
{
    use HasFactory;

    protected $table = 'supplier_po_payments';

    protected $fillable = [
        'supplier_po_id',
        'payment_number',
        'payment_date',
        'amount',
        'payment_method',
        'bank_name',
        'bank_account_number',
        'reference_number',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function supplierPo(): BelongsTo
    {
        return $this->belongsTo(SupplierPO::class, 'supplier_po_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
