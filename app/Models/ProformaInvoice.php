<?php

namespace App\Models;

use App\Traits\HasCustomCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProformaInvoice extends Model
{
    use HasFactory, HasCustomCode;

    public const CODE_PREFIX = 'PI';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'sales_order_id',
        'pi_number',
        'pi_date',
        'status',
        'subtotal',
        'tax_amount',
        'grand_total',
    ];

    protected $casts = [
        'pi_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }
}
