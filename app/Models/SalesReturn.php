<?php

namespace App\Models;

use App\Traits\HasCustomCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesReturn extends Model
{
    use HasFactory, HasCustomCode, SoftDeletes;

    public const CODE_PREFIX = 'RET';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'delivery_note_id',
        'return_date',
        'received_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'return_date' => 'date',
        'received_date' => 'date',
    ];

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class);
    }
}
