<?php

namespace App\Services;

use App\Models\GudangProduct;
use App\Models\InBound;
use App\Models\OutBound;
use App\Models\StockMutation;
use App\Models\SupplierProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockMutationService
{
    /**
     * Record a new stock mutation (inbound, outbound, adjustment, transfer, etc.)
     */
    public static function recordMutation(array $data): ?StockMutation
    {
        return DB::transaction(function () use ($data) {
            $supplierProductId = $data['supplier_product_id'];
            $supplierProduct = SupplierProduct::find($supplierProductId);
            if (!$supplierProduct) {
                return null;
            }

            $qtyIn = max(0, (int)($data['qty_in'] ?? 0));
            $qtyOut = max(0, (int)($data['qty_out'] ?? 0));
            $rackId = $data['rack_id'] ?? null;
            $gudangProductId = $data['gudang_product_id'] ?? null;
            $gudangType = $data['gudang_type'] ?? null;

            if ($gudangProductId && !$rackId) {
                $gp = GudangProduct::find($gudangProductId);
                if ($gp) {
                    $rackId = $gp->rack_id;
                    $gudangType = $gp->gudang_type;
                }
            }

            $unitCost = (float)($data['unit_cost'] ?? 0);
            if ($unitCost <= 0) {
                $unitCost = (float)$supplierProduct->last_purchase_price;
            }
            if ($unitCost <= 0 && $gudangProductId) {
                $gp = GudangProduct::find($gudangProductId);
                $unitCost = (float)($gp?->price ?? 0);
            }

            $qtyDiff = $qtyIn - $qtyOut;
            $totalCost = ($qtyIn > 0 ? $qtyIn : $qtyOut) * $unitCost;

            // Get previous latest balance for this product
            $lastMutation = StockMutation::where('supplier_product_id', $supplierProductId)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $prevBalance = $lastMutation ? $lastMutation->balance : (int)$supplierProduct->gudangProducts()->sum('qty');
            $newBalance = max(0, $prevBalance + $qtyDiff);
            $balanceValue = $newBalance * $unitCost;

            // Previous balance for this specific rack if rack is specified
            $rackBalance = null;
            if ($rackId) {
                $lastRackMutation = StockMutation::where('supplier_product_id', $supplierProductId)
                    ->where('rack_id', $rackId)
                    ->orderBy('transaction_date', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();
                $prevRackBal = $lastRackMutation && $lastRackMutation->rack_balance !== null
                    ? $lastRackMutation->rack_balance
                    : (int)GudangProduct::where('supplier_product_id', $supplierProductId)->where('rack_id', $rackId)->sum('qty');
                $rackBalance = max(0, $prevRackBal + $qtyDiff);
            }

            $mutationCode = StockMutation::generateMutationCode();

            return StockMutation::create([
                'mutation_code'       => $mutationCode,
                'supplier_product_id' => $supplierProductId,
                'gudang_product_id'   => $gudangProductId,
                'rack_id'             => $rackId,
                'gudang_type'         => $gudangType,
                'transaction_date'    => $data['transaction_date'] ?? now(),
                'mutation_type'       => $data['mutation_type'] ?? 'adjustment',
                'reference_type'      => $data['reference_type'] ?? null,
                'reference_id'        => $data['reference_id'] ?? null,
                'reference_number'    => $data['reference_number'] ?? null,
                'qty_in'              => $qtyIn,
                'qty_out'             => $qtyOut,
                'balance'             => $newBalance,
                'rack_balance'        => $rackBalance,
                'unit_cost'           => $unitCost,
                'total_cost'          => $totalCost,
                'balance_value'       => $balanceValue,
                'notes'               => $data['notes'] ?? null,
                'user_id'             => $data['user_id'] ?? (Auth::check() ? Auth::id() : null),
            ]);
        });
    }

    /**
     * Recalculate and synchronize all chronological running balances for a product.
     */
    public static function recalculateBalances(string $supplierProductId): void
    {
        DB::transaction(function () use ($supplierProductId) {
            $mutations = StockMutation::where('supplier_product_id', $supplierProductId)
                ->orderBy('transaction_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $runningBalance = 0;
            $rackRunningBalances = [];

            foreach ($mutations as $mut) {
                $runningBalance += ($mut->qty_in - $mut->qty_out);
                if ($runningBalance < 0) {
                    $runningBalance = 0;
                }

                $rackBal = null;
                if ($mut->rack_id) {
                    if (!isset($rackRunningBalances[$mut->rack_id])) {
                        $rackRunningBalances[$mut->rack_id] = 0;
                    }
                    $rackRunningBalances[$mut->rack_id] += ($mut->qty_in - $mut->qty_out);
                    if ($rackRunningBalances[$mut->rack_id] < 0) {
                        $rackRunningBalances[$mut->rack_id] = 0;
                    }
                    $rackBal = $rackRunningBalances[$mut->rack_id];
                }

                $mut->balance = $runningBalance;
                $mut->rack_balance = $rackBal;
                $mut->balance_value = $runningBalance * (float)$mut->unit_cost;
                $mut->save();
            }
        });
    }

    /**
     * Backfill historical movements from existing Inbound, Outbound, and GudangProduct records.
     * Idempotent: Only backfills records that have not been recorded yet.
     */
    public static function backfillHistoricalMovements(): array
    {
        $stats = [
            'initial_stock' => 0,
            'inbound'       => 0,
            'outbound'      => 0,
        ];

        DB::transaction(function () use (&$stats) {
            // 1. Existing stored InBound records
            $inbounds = InBound::with('supplierProduct')->where('status', 'stored')->get();
            foreach ($inbounds as $inb) {
                if (!$inb->supplier_product_id) continue;

                $exists = StockMutation::where('reference_type', 'inbound')
                    ->where('reference_id', $inb->id)
                    ->exists();

                if (!$exists) {
                    $unitCost = (float)$inb->hpp > 0 ? (float)$inb->hpp : (float)($inb->supplierProduct?->last_purchase_price ?? 0);
                    StockMutation::create([
                        'mutation_code'       => StockMutation::generateMutationCode(),
                        'supplier_product_id' => $inb->supplier_product_id,
                        'rack_id'             => null,
                        'transaction_date'    => $inb->received_date ? date('Y-m-d 09:00:00', strtotime($inb->received_date)) : $inb->created_at,
                        'mutation_type'       => 'inbound',
                        'reference_type'      => 'inbound',
                        'reference_id'        => $inb->id,
                        'reference_number'    => $inb->po_number ?: $inb->id,
                        'qty_in'              => (int)$inb->qty_received,
                        'qty_out'             => 0,
                        'balance'             => 0, // Recalculated at the end
                        'unit_cost'           => $unitCost,
                        'total_cost'          => (int)$inb->qty_received * $unitCost,
                        'balance_value'       => 0,
                        'notes'               => $inb->notes ?: 'Pencatatan Barang Masuk',
                    ]);
                    $stats['inbound']++;
                }
            }

            // 2. Existing OutBound records
            $outbounds = OutBound::with(['gudangProduct.supplierProduct', 'warehouseTask'])->get();
            foreach ($outbounds as $out) {
                $spId = $out->gudangProduct?->supplier_product_id;
                if (!$spId) continue;

                $exists = StockMutation::where('reference_type', 'outbound')
                    ->where('reference_id', $out->id)
                    ->exists();

                if (!$exists) {
                    $unitCost = (float)($out->gudangProduct?->price ?? 0);
                    if ($unitCost <= 0) {
                        $unitCost = (float)($out->gudangProduct?->supplierProduct?->last_purchase_price ?? 0);
                    }

                    $refNum = $out->warehouseTask?->task_number ?: ($out->warehouseTask?->sales_order_id ?: $out->id);

                    StockMutation::create([
                        'mutation_code'       => StockMutation::generateMutationCode(),
                        'supplier_product_id' => $spId,
                        'gudang_product_id'   => $out->gudang_product_id,
                        'rack_id'             => $out->gudangProduct?->rack_id,
                        'gudang_type'         => $out->gudangProduct?->gudang_type,
                        'transaction_date'    => $out->outbound_date ? date('Y-m-d 14:00:00', strtotime($out->outbound_date)) : $out->created_at,
                        'mutation_type'       => 'outbound',
                        'reference_type'      => 'outbound',
                        'reference_id'        => $out->id,
                        'reference_number'    => $refNum,
                        'qty_in'              => 0,
                        'qty_out'             => (int)$out->qty,
                        'balance'             => 0,
                        'unit_cost'           => $unitCost,
                        'total_cost'          => (int)$out->qty * $unitCost,
                        'balance_value'       => 0,
                        'notes'               => $out->note ?: 'Pencatatan Barang Keluar (Outbound)',
                    ]);
                    $stats['outbound']++;
                }
            }

            // 3. For any product that has GudangProduct with stock but no mutations yet, create Initial Stock
            $supplierProducts = SupplierProduct::with('gudangProducts')->get();
            foreach ($supplierProducts as $sp) {
                $hasMutations = StockMutation::where('supplier_product_id', $sp->id)->exists();
                if (!$hasMutations) {
                    $totalPhysicalStock = (int)$sp->gudangProducts->sum('qty');
                    if ($totalPhysicalStock > 0) {
                        $unitCost = (float)$sp->last_purchase_price > 0 ? (float)$sp->last_purchase_price : (float)($sp->gudangProducts->first()?->price ?? 0);
                        StockMutation::create([
                            'mutation_code'       => StockMutation::generateMutationCode(),
                            'supplier_product_id' => $sp->id,
                            'rack_id'             => $sp->gudangProducts->first()?->rack_id,
                            'gudang_type'         => $sp->gudangProducts->first()?->gudang_type,
                            'transaction_date'    => $sp->created_at ?? now()->subDays(30),
                            'mutation_type'       => 'initial_stock',
                            'reference_type'      => 'manual',
                            'reference_number'    => 'SALDO-AWAL',
                            'qty_in'              => $totalPhysicalStock,
                            'qty_out'             => 0,
                            'balance'             => $totalPhysicalStock,
                            'unit_cost'           => $unitCost,
                            'total_cost'          => $totalPhysicalStock * $unitCost,
                            'balance_value'       => $totalPhysicalStock * $unitCost,
                            'notes'               => 'Saldo Awal Sistem',
                        ]);
                        $stats['initial_stock']++;
                    }
                }

                // Recalculate this product's running balance
                self::recalculateBalances($sp->id);
            }
        });

        return $stats;
    }
}
