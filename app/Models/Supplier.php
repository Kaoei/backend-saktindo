<?php

namespace App\Models;

use App\Traits\HasCustomCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory, HasCustomCode;

    public const CODE_PREFIX = 'SUP';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'vendor_code',
        'vendor_type',
        'company_name',
        'pic_name',
        'pic_phone',
        'pic_email',
        'email',
        'phone',
        'tax_number',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'payment_due_days',
        'debt_limit',
        'address',
        'city',
        'province',
        'postal_code',
        'status',
        'notes',
    ];

    protected $casts = [
        'payment_due_days' => 'integer',
        'debt_limit' => 'decimal:2',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(SupplierProduct::class);
    }

    public function purchaseHistories(): HasMany
    {
        return $this->hasMany(SupplierPurchaseHistory::class);
    }

    public function paymentTerms(): HasMany
    {
        return $this->hasMany(SupplierPaymentTerm::class);
    }
}
