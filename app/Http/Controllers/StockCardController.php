<?php

namespace App\Http\Controllers;

use App\Models\Rak;
use App\Models\StockMutation;
use App\Models\SupplierProduct;
use App\Services\StockMutationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockCardController extends Controller
{
    /**
     * Display the stock card for a specific product and location.
     */
    public function index(Request $request)
    {
        // 1. Resolve selected product
        $productId = $request->get('product_id') ?: $request->get('supplier_product_id');
        
        // If sku provided, resolve by SKU
        if (!$productId && $request->filled('sku')) {
            $prod = SupplierProduct::where('sku', $request->sku)->first();
            $productId = $prod?->id;
        }

        // If still empty, pick first active product with stock or first product
        if (!$productId) {
            $firstProduct = SupplierProduct::whereHas('gudangProducts', function ($q) {
                $q->where('qty', '>', 0);
            })->first() ?: SupplierProduct::first();

            $productId = $firstProduct?->id;
        }

        $currentProduct = $productId ? SupplierProduct::with(['gudangProducts.rack', 'supplier'])->find($productId) : null;

        // 2. Resolve filters
        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date)->startOfDay() 
            : Carbon::now()->subDays(60)->startOfDay();

        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date)->endOfDay() 
            : Carbon::now()->endOfDay();

        $rackId = $request->get('rack_id');
        $mutationType = $request->get('mutation_type');

        // 3. Compute Saldo Awal Periode (Before start_date)
        $saldoAwalQuery = StockMutation::where('supplier_product_id', $productId)
            ->where('transaction_date', '<', $startDate);

        if ($rackId) {
            $saldoAwalQuery->where('rack_id', $rackId);
        }

        $saldoAwalQty = (int)$saldoAwalQuery->select(DB::raw('COALESCE(SUM(qty_in - qty_out), 0) as balance'))->value('balance');
        if ($saldoAwalQty < 0) {
            $saldoAwalQty = 0;
        }

        $unitCost = (float)($currentProduct?->last_purchase_price ?? 0);
        if ($unitCost <= 0 && $currentProduct) {
            $unitCost = (float)($currentProduct->gudangProducts->max('price') ?? 0);
        }
        $saldoAwalValue = $saldoAwalQty * $unitCost;

        // 4. Query mutations in current period
        $mutationsQuery = StockMutation::with(['rack', 'user'])
            ->where('supplier_product_id', $productId)
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($rackId) {
            $mutationsQuery->where('rack_id', $rackId);
        }

        if ($mutationType) {
            $mutationsQuery->where('mutation_type', $mutationType);
        }

        // Clone query for period summary stats
        $summaryQuery = clone $mutationsQuery;
        $totalMasukPeriode = (int)$summaryQuery->sum('qty_in');
        $totalKeluarPeriode = (int)$summaryQuery->sum('qty_out');
        $totalNilaiMasuk = (float)$summaryQuery->sum(DB::raw('qty_in * unit_cost'));
        $totalNilaiKeluar = (float)$summaryQuery->sum(DB::raw('qty_out * unit_cost'));

        $saldoAkhirPeriode = max(0, $saldoAwalQty + $totalMasukPeriode - $totalKeluarPeriode);
        $totalValuasiSaldo = $saldoAkhirPeriode * $unitCost;

        // Get chronological mutation records for table (pagination or full for card)
        $mutations = $mutationsQuery->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(50)
            ->withQueryString();

        // Compute running balance dynamically for paginated rows
        // Calculate offset balance before current page
        $page = $mutations->currentPage();
        $perPage = $mutations->perPage();
        $offset = ($page - 1) * $perPage;

        $previousRowsQuery = clone $mutationsQuery;
        $prevOffsetDiff = 0;
        if ($offset > 0) {
            $prevRows = $previousRowsQuery->orderBy('transaction_date', 'asc')
                ->orderBy('id', 'asc')
                ->take($offset)
                ->get();
            $prevOffsetDiff = (int)$prevRows->sum(function ($r) {
                return $r->qty_in - $r->qty_out;
            });
        }

        $currentRunningBal = $saldoAwalQty + $prevOffsetDiff;
        foreach ($mutations as $mut) {
            $currentRunningBal += ($mut->qty_in - $mut->qty_out);
            if ($currentRunningBal < 0) $currentRunningBal = 0;
            $mut->calculated_balance = $currentRunningBal;
            $mut->calculated_balance_value = $currentRunningBal * (float)($mut->unit_cost ?: $unitCost);
        }

        // 5. Lookups for dropdowns
        $allProducts = SupplierProduct::select('id', 'item_name', 'sku', 'brand')
            ->orderBy('item_name')
            ->get();

        $allRacks = Rak::orderBy('rak_kode')->get();

        // Current physical stock in warehouse
        $currentPhysicalStock = (int)($currentProduct?->gudangProducts->sum('qty') ?? 0);
        $rackAllocations = $currentProduct?->gudangProducts ?? collect();

        return view('stock_cards.index', compact(
            'currentProduct',
            'allProducts',
            'allRacks',
            'mutations',
            'startDate',
            'endDate',
            'rackId',
            'mutationType',
            'saldoAwalQty',
            'saldoAwalValue',
            'totalMasukPeriode',
            'totalKeluarPeriode',
            'totalNilaiMasuk',
            'totalNilaiKeluar',
            'saldoAkhirPeriode',
            'totalValuasiSaldo',
            'unitCost',
            'currentPhysicalStock',
            'rackAllocations'
        ));
    }

    /**
     * Print / PDF preview of the stock card.
     */
    public function print(Request $request)
    {
        $productId = $request->get('product_id');
        $currentProduct = SupplierProduct::with(['gudangProducts.rack', 'supplier'])->findOrFail($productId);

        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date)->startOfDay() 
            : Carbon::now()->subDays(60)->startOfDay();

        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date)->endOfDay() 
            : Carbon::now()->endOfDay();

        $rackId = $request->get('rack_id');
        $mutationType = $request->get('mutation_type');

        // Saldo Awal
        $saldoAwalQuery = StockMutation::where('supplier_product_id', $productId)
            ->where('transaction_date', '<', $startDate);

        if ($rackId) {
            $saldoAwalQuery->where('rack_id', $rackId);
        }

        $saldoAwalQty = (int)$saldoAwalQuery->select(DB::raw('COALESCE(SUM(qty_in - qty_out), 0) as balance'))->value('balance');
        if ($saldoAwalQty < 0) $saldoAwalQty = 0;

        $unitCost = (float)($currentProduct->last_purchase_price ?? 0);
        if ($unitCost <= 0) {
            $unitCost = (float)($currentProduct->gudangProducts->max('price') ?? 0);
        }
        $saldoAwalValue = $saldoAwalQty * $unitCost;

        $mutationsQuery = StockMutation::with(['rack', 'user'])
            ->where('supplier_product_id', $productId)
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($rackId) {
            $mutationsQuery->where('rack_id', $rackId);
        }
        if ($mutationType) {
            $mutationsQuery->where('mutation_type', $mutationType);
        }

        $mutations = $mutationsQuery->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $totalMasukPeriode = (int)$mutations->sum('qty_in');
        $totalKeluarPeriode = (int)$mutations->sum('qty_out');
        $saldoAkhirPeriode = max(0, $saldoAwalQty + $totalMasukPeriode - $totalKeluarPeriode);
        $totalValuasiSaldo = $saldoAkhirPeriode * $unitCost;

        $currentRunningBal = $saldoAwalQty;
        foreach ($mutations as $mut) {
            $currentRunningBal += ($mut->qty_in - $mut->qty_out);
            if ($currentRunningBal < 0) $currentRunningBal = 0;
            $mut->calculated_balance = $currentRunningBal;
            $mut->calculated_balance_value = $currentRunningBal * (float)($mut->unit_cost ?: $unitCost);
        }

        return view('stock_cards.print', compact(
            'currentProduct',
            'mutations',
            'startDate',
            'endDate',
            'rackId',
            'saldoAwalQty',
            'saldoAwalValue',
            'totalMasukPeriode',
            'totalKeluarPeriode',
            'saldoAkhirPeriode',
            'totalValuasiSaldo',
            'unitCost'
        ));
    }

    /**
     * Export stock card to CSV / Excel spreadsheet.
     */
    public function exportExcel(Request $request)
    {
        $productId = $request->get('product_id');
        $currentProduct = SupplierProduct::findOrFail($productId);

        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date)->startOfDay() 
            : Carbon::now()->subDays(60)->startOfDay();

        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date)->endOfDay() 
            : Carbon::now()->endOfDay();

        $rackId = $request->get('rack_id');
        $mutationType = $request->get('mutation_type');

        $saldoAwalQuery = StockMutation::where('supplier_product_id', $productId)
            ->where('transaction_date', '<', $startDate);
        if ($rackId) $saldoAwalQuery->where('rack_id', $rackId);
        $saldoAwalQty = max(0, (int)$saldoAwalQuery->select(DB::raw('COALESCE(SUM(qty_in - qty_out), 0) as balance'))->value('balance'));

        $unitCost = (float)($currentProduct->last_purchase_price ?? 0);
        if ($unitCost <= 0) $unitCost = (float)($currentProduct->gudangProducts()->max('price') ?? 0);

        $mutationsQuery = StockMutation::with(['rack', 'user'])
            ->where('supplier_product_id', $productId)
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($rackId) $mutationsQuery->where('rack_id', $rackId);
        if ($mutationType) $mutationsQuery->where('mutation_type', $mutationType);

        $mutations = $mutationsQuery->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $filename = 'Kartu_Stok_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $currentProduct->sku ?: $currentProduct->item_name) . '_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($currentProduct, $startDate, $endDate, $saldoAwalQty, $unitCost, $mutations) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel

            fputcsv($handle, ['KARTU STOK PERSEDIAAN BARANG']);
            fputcsv($handle, ['Produk', $currentProduct->item_name]);
            fputcsv($handle, ['SKU', $currentProduct->sku]);
            fputcsv($handle, ['Periode', $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'No',
                'Tanggal',
                'Kode Mutasi',
                'No Referensi',
                'Tipe Mutasi',
                'Rak / Lokasi',
                'Masuk (Qty)',
                'Keluar (Qty)',
                'Saldo (Qty)',
                'HPP Satuan (Rp)',
                'Total Nilai Transaksi (Rp)',
                'Valuasi Saldo (Rp)',
                'Keterangan',
            ]);

            // Saldo Awal row
            fputcsv($handle, [
                '-',
                $startDate->format('d/m/Y'),
                '-',
                'SALDO AWAL',
                'Saldo Awal',
                '-',
                0,
                0,
                $saldoAwalQty,
                $unitCost,
                0,
                $saldoAwalQty * $unitCost,
                'Saldo Awal Periode',
            ]);

            $runningBal = $saldoAwalQty;
            $no = 1;
            foreach ($mutations as $m) {
                $runningBal += ($m->qty_in - $m->qty_out);
                if ($runningBal < 0) $runningBal = 0;
                $rowHpp = (float)($m->unit_cost ?: $unitCost);

                fputcsv($handle, [
                    $no++,
                    $m->transaction_date ? $m->transaction_date->format('d/m/Y H:i') : '-',
                    $m->mutation_code,
                    $m->reference_number ?: '-',
                    $m->type_badge['label'] ?? $m->mutation_type,
                    $m->rack_id ?: 'Semua Rak',
                    $m->qty_in,
                    $m->qty_out,
                    $runningBal,
                    $rowHpp,
                    ($m->qty_in > 0 ? $m->qty_in : $m->qty_out) * $rowHpp,
                    $runningBal * $rowHpp,
                    $m->notes ?: '-',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Recalculate balances manually.
     */
    public function recalculate(Request $request)
    {
        $productId = $request->get('product_id');
        if ($productId) {
            StockMutationService::recalculateBalances($productId);
            return redirect()->back()->with('status', 'Saldo berjalan kartu stok berhasil dihitung ulang dan disinkronkan.');
        }

        // Reconcile all
        $stats = StockMutationService::backfillHistoricalMovements();
        return redirect()->back()->with('status', 'Sinkronisasi seluruh kartu stok berhasil diperbarui. Inbound: ' . $stats['inbound'] . ', Saldo Awal: ' . $stats['initial_stock']);
    }
}
