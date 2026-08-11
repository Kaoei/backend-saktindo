<?php

namespace App\Models;

use App\Traits\HasCustomCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany; // PERBAIKAN: Import HasMany

class DeliveryNote extends Model
{
    use HasFactory, HasCustomCode;

    public const CODE_PREFIX = 'SJ';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'invoice_id',
        'delivery_note_number',
        'delivery_date',
        'status',
        'pic_sales',
        'pic_gudang',
        'notes',
        'print_count',
    ];

    protected $casts = [
        'delivery_date' => 'date',
    ];

    /**
     * Relasi ke model Invoice
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryNoteItem::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SalesReturn::class);
    }
}
