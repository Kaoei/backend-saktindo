<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseTask extends Model
{
    use SoftDeletes;

    protected $table = 'warehouse_tasks';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'sales_order_id',
        'invoice_id',
        'assigned_to',
        'status',
        'note',
    ];

   public static function generateId()
{
    $last = self::withTrashed()
        ->select('id')
        ->orderByDesc('id')
        ->first();

    if (!$last) {
        return 'WTASK-000001';
    }

    $number = (int) str_replace('WTASK-', '', $last->id);

    return 'WTASK-' . str_pad(
        $number + 1,
        6,
        '0',
        STR_PAD_LEFT
    );
}

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }
}