<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StockMutation extends Model
{
    use HasFactory;

    protected $fillable = [
        'mutation_code',
        'supplier_product_id',
        'gudang_product_id',
        'rack_id',
        'gudang_type',
        'transaction_date',
        'mutation_type',
        'reference_type',
        'reference_id',
        'reference_number',
        'qty_in',
        'qty_out',
        'balance',
        'rack_balance',
        'unit_cost',
        'total_cost',
        'balance_value',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'qty_in'           => 'integer',
        'qty_out'          => 'integer',
        'balance'          => 'integer',
        'rack_balance'     => 'integer',
        'unit_cost'        => 'decimal:2',
        'total_cost'       => 'decimal:2',
        'balance_value'    => 'decimal:2',
    ];

    public static function generateMutationCode(): string
    {
        $prefix = 'MUT-' . date('Ymd') . '-';
        $random = strtoupper(Str::random(4));
        $count = self::whereDate('created_at', today())->count() + 1;
        return $prefix . str_pad((string)$count, 4, '0', STR_PAD_LEFT) . '-' . $random;
    }

    public function supplierProduct(): BelongsTo
    {
        return $this->belongsTo(SupplierProduct::class, 'supplier_product_id', 'id');
    }

    public function gudangProduct(): BelongsTo
    {
        return $this->belongsTo(GudangProduct::class, 'gudang_product_id', 'id');
    }

    public function rack(): BelongsTo
    {
        return $this->belongsTo(Rak::class, 'rack_id', 'rak_kode');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getTypeBadgeAttribute(): array
    {
        return match ($this->mutation_type) {
            'initial_stock' => ['label' => 'Saldo Awal', 'class' => 'bg-secondary text-white'],
            'inbound'       => ['label' => 'Barang Masuk', 'class' => 'bg-success text-white'],
            'outbound'      => ['label' => 'Barang Keluar', 'class' => 'bg-danger text-white'],
            'transfer'      => ['label' => 'Pindah Rak', 'class' => 'bg-info text-white'],
            'adjustment'    => ['label' => 'Penyesuaian', 'class' => 'bg-warning text-dark'],
            'return'        => ['label' => 'Retur', 'class' => 'bg-primary text-white'],
            default         => ['label' => ucfirst($this->mutation_type), 'class' => 'bg-light text-dark border'],
        };
    }
}
