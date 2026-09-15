<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'customer_id');
    }

    /**
     * Get unpaid / partial invoices query for this customer
     */
    public function getUnpaidInvoicesQuery()
    {
        $customerId = $this->id;
        $customerName = $this->nama_customer;

        return Invoice::query()
            ->where('status', '!=', 'paid')
            ->where(function ($q) {
                $q->where('outstanding_amount', '>', 0)
                  ->orWhereNull('outstanding_amount');
            })
            ->where(function ($q) use ($customerId, $customerName) {
                $q->whereHas('salesOrder', function ($soQ) use ($customerId, $customerName) {
                    $soQ->where('customer_id', $customerId)
                        ->orWhere('customer_name', $customerName);
                })->orWhereHas('salesOrders', function ($soQ) use ($customerId, $customerName) {
                    $soQ->where('customer_id', $customerId)
                        ->orWhere('customer_name', $customerName);
                });
            });
    }

    public function getUnpaidInvoicesAttribute()
    {
        return $this->getUnpaidInvoicesQuery()->latest('invoice_date')->get();
    }

    public function getUnpaidInvoicesCountAttribute(): int
    {
        return $this->getUnpaidInvoicesQuery()->count();
    }

    public function getUnpaidInvoicesTotalAttribute(): float
    {
        return (float) $this->getUnpaidInvoicesQuery()->sum('outstanding_amount');
    }

    public function getIsBlockedForSoAttribute(): bool
    {
        return $this->unpaid_invoices_count >= 3;
    }
}

