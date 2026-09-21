<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BundlePromo extends Model
{
    use HasFactory;

    protected $table = 'bundle_promos';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'bundle_code',
        'name',
        'description',
        'original_price',
        'bundle_price',
        'discount_type',
        'discount_value',
        'status',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'bundle_price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public static function generateId(): string
    {
        $year = now()->format('y');
        $month = now()->format('m');
        $prefix = "BDL-{$year}{$month}-";

        $last = self::where('id', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        if (!$last) {
            return $prefix . '0001';
        }

        $lastNumber = (int) substr($last->id, strlen($prefix));
        return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    public static function generateCode(): string
    {
        $count = self::count();
        return 'PROMO-BDL-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BundlePromoItem::class, 'bundle_promo_id', 'id');
    }

    public function getSavingsAmountAttribute(): float
    {
        return max(0, (float) $this->original_price - (float) $this->bundle_price);
    }

    public function getSavingsPercentageAttribute(): float
    {
        if ((float) $this->original_price <= 0) {
            return 0;
        }
        return round((($this->savings_amount / (float) $this->original_price) * 100), 1);
    }
}
