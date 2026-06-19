<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OutBound extends Model
{
    use SoftDeletes;

    protected $table = 'out_bounds';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'warehouse_task_id',
        'gudang_product_id',
        'qty',
        'outbound_date',
        'delivery_type',
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
            return 'OUT-000001';
        }

        $number = (int) str_replace('OUT-', '', $last->id);

        return 'OUT-' . str_pad(
            $number + 1,
            6,
            '0',
            STR_PAD_LEFT
        );
    }

    public function warehouseTask()
    {
        return $this->belongsTo(
            WarehouseTask::class,
            'warehouse_task_id',
            'id'
        );
    }

    public function gudangProduct()
    {
        return $this->belongsTo(
            GudangProduct::class,
            'gudang_product_id',
            'id'
        );
    }
}