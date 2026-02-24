<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    public const CODE_PREFIX = 'INV';

    public $incrementing = false;
    protected $keyType = 'string';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID    = 'paid';

    protected $fillable = [
        'proposal_id',
        'invoice_number',
        'amount',
        'status',
        'paid_at',
        'created_by',
        'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function ($invoice) {
            if (empty($invoice->id)) {
                $count  = static::withoutGlobalScopes()->count() + 1;
                $code   = self::CODE_PREFIX . '-' . now()->format('Ym') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
                $invoice->id             = $code;
                $invoice->invoice_number = $code;
            } elseif (empty($invoice->invoice_number)) {
                $invoice->invoice_number = $invoice->id;
            }
        });
    }

    protected $casts = [
        'paid_at' => 'datetime',
        'amount'  => 'decimal:2',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PAID    => 'Paid',
            default              => 'Pending',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'bg-success',
            default           => 'bg-warning',
        };
    }
}
