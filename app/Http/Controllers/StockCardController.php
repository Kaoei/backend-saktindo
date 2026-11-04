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
     * Display the stock card for a specific warehouse, rack, or product.
     */
    public function index(Request $request)
    {
        $gudangType = $request->get('gudang_type');
        $rackId = $request->get('rack_id');
        $productId = $request->get('product_id') ?: $request->get('supplier_product_id');
        $mutationType = $request->get('mutation_type');

        // If sku provided, resolve by SKU
        if (!$productId && $request->filled('sku')) {
            $prod = SupplierProduct::where('sku', $request->sku)->first();
            $productId = $prod?->id;
        }

        // Auto resolve gudang_type if rackId is provided
        if ($rackId && empty($gudangType)) {
            $selectedRackObj = Rak::where('rak_kode', $rackId)->first();
            if ($selectedRackObj && $selectedRackObj->gudang) {
                $gudangType = strtoupper($selectedRackObj->gudang);
            }
        }

        // Products stored in this rack (if rack_id is specified)
        $productsInRack = collect();
        if ($rackId) {
            $productsInRack = SupplierProduct::whereHas('gudangProducts', function ($q) use ($rackId) {
                $q->where('rack_id', $rackId);
            })->with(['gudangProducts' => function ($q) use ($rackId) {
                $q->where('rack_id', $rackId);
            }])->orderBy('item_name')->get();

            foreach ($productsInRack as $pItem) {
                $pItem->current_rack_qty = (int) $pItem->gudangProducts->where('rack_id', $rackId)->sum('qty');
            }
        }

        // Selected product model
        $currentProduct = $productId ? SupplierProduct::with(['gudangProducts.rack', 'supplier'])->find($productId) : null;
        $currentRack = $rackId ? Rak::where('rak_kode', $rackId)->first() : null;

        // Resolve date range filters
        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date)->startOfDay() 
            : Carbon::now()->subDays(60)->startOfDay();

        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date)->endOfDay() 
            : Carbon::now()->endOfDay();

        // 3. Compute Saldo Awal Periode (Before start_date)
        $saldoAwalQuery = StockMutation::where('transaction_date', '<', $startDate);

        if (!empty($gudangType)) {
            $gTypes = [strtoupper($gudangType), strtolower($gudangType)];
            $saldoAwalQuery->where(function ($q) use ($gTypes) {
                $q->whereIn('gudang_type', $gTypes)
                  ->orWhereHas('rack', function ($rq) use ($gTypes) {
                      $rq->whereIn('gudang', $gTypes);
                  });
            });
        }

        if (!empty($rackId)) {
            $saldoAwalQuery->where('rack_id', $rackId);
        }

        if (!empty($productId)) {
            $saldoAwalQuery->where('supplier_product_id', $productId);
        }

        $saldoAwalQty = (int) $saldoAwalQuery->select(DB::raw('COALESCE(SUM(qty_in - qty_out), 0) as balance'))->value('balance');
        if ($saldoAwalQty < 0) {
            $saldoAwalQty = 0;
        }

        $unitCost = (float)($currentProduct?->last_purchase_price ?? 0);
        if ($unitCost <= 0 && $currentProduct) {
            $unitCost = (float)($currentProduct->gudangProducts->max('price') ?? 0);
        }
        $saldoAwalValue = $saldoAwalQty * $unitCost;

        // 4. Query mutations in current period
        $mutationsQuery = StockMutation::with(['rack', 'supplierProduct', 'user'])
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if (!empty($gudangType)) {
            $gTypes = [strtoupper($gudangType), strtolower($gudangType)];
            $mutationsQuery->where(function ($q) use ($gTypes) {
                $q->whereIn('gudang_type', $gTypes)
                  ->orWhereHas('rack', function ($rq) use ($gTypes) {
                      $rq->whereIn('gudang', $gTypes);
                  });
            });
        }

        if (!empty($rackId)) {
            $mutationsQuery->where('rack_id', $rackId);
        }

        if (!empty($productId)) {
            $mutationsQuery->where('supplier_product_id', $productId);
        }

        if (!empty($mutationType)) {
            $mutationsQuery->where('mutation_type', $mutationType);
        }

        // Clone query for period summary stats
        $summaryQuery = clone $mutationsQuery;
        $totalMasukPeriode = (int) $summaryQuery->sum('qty_in');
        $totalKeluarPeriode = (int) $summaryQuery->sum('qty_out');
        $totalNilaiMasuk = (float) $summaryQuery->sum(DB::raw('qty_in * unit_cost'));
        $totalNilaiKeluar = (float) $summaryQuery->sum(DB::raw('qty_out * unit_cost'));

        $saldoAkhirPeriode = max(0, $saldoAwalQty + $totalMasukPeriode - $totalKeluarPeriode);
        $totalValuasiSaldo = $saldoAkhirPeriode * $unitCost;

        // Get chronological mutation records for table
        $mutations = $mutationsQuery->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(50)
            ->withQueryString();

        // Compute running balance dynamically for paginated rows
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
            $prevOffsetDiff = (int) $prevRows->sum(fn ($r) => $r->qty_in - $r->qty_out);
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

        // Current physical stock calculation
        $currentPhysicalStock = 0;
        if ($currentProduct) {
            if ($rackId) {
                $currentPhysicalStock = (int) $currentProduct->gudangProducts->where('rack_id', $rackId)->sum('qty');
            } else {
                $currentPhysicalStock = (int) $currentProduct->gudangProducts->sum('qty');
            }
        } elseif ($rackId) {
            $currentPhysicalStock = (int) \App\Models\GudangProduct::where('rack_id', $rackId)->sum('qty');
        } elseif (!empty($gudangType)) {
            $currentPhysicalStock = (int) \App\Models\GudangProduct::where('gudang_type', $gudangType)->sum('qty');
        } else {
            $currentPhysicalStock = (int) \App\Models\GudangProduct::sum('qty');
        }

        $rackAllocations = $currentProduct?->gudangProducts ?? collect();

        return view('stock_cards.index', compact(
            'currentProduct',
            'currentRack',
            'allProducts',
            'productsInRack',
            'allRacks',
            'mutations',
            'startDate',
            'endDate',
            'gudangType',
            'rackId',
            'productId',
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
     * AJAX endpoint: Get list of products stored in a specific rack.
     */
    public function productsByRack(Request $request)
    {
        $rackId = $request->get('rack_id');
        
        if (empty($rackId)) {
            $products = SupplierProduct::select('id', 'item_name', 'sku', 'brand')
                ->orderBy('item_name')
                ->get()
                ->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'item_name' => $p->item_name,
                        'sku' => $p->sku ?: 'No SKU',
                        'brand' => $p->brand,
                        'qty' => null,
                        'text' => $p->item_name . ' (' . ($p->sku ?: 'No SKU') . ')' . ($p->brand ? ' • ' . $p->brand : ''),
                    ];
                });

            return response()->json([
                'status' => 'success',
                'rack_id' => null,
                'count' => $products->count(),
                'products' => $products,
            ]);
        }

        $products = SupplierProduct::whereHas('gudangProducts', function ($q) use ($rackId) {
                $q->where('rack_id', $rackId);
            })
            ->with(['gudangProducts' => function ($q) use ($rackId) {
                $q->where('rack_id', $rackId);
            }])
            ->orderBy('item_name')
            ->get(['id', 'item_name', 'sku', 'brand'])
            ->map(function ($p) use ($rackId) {
                $qty = (int) $p->gudangProducts->where('rack_id', $rackId)->sum('qty');
                return [
                    'id' => $p->id,
                    'item_name' => $p->item_name,
                    'sku' => $p->sku ?: 'No SKU',
                    'brand' => $p->brand,
                    'qty' => $qty,
                    'text' => $p->item_name . ' (' . ($p->sku ?: 'No SKU') . ') [' . $qty . ' pcs]',
                ];
            });

        return response()->json([
            'status' => 'success',
            'rack_id' => $rackId,
            'count' => $products->count(),
            'products' => $products,
        ]);
    }

    /**
     * Print / PDF preview of the stock card.
     */
    public function print(Request $request)
    {
        $gudangType = $request->get('gudang_type');
        $rackId = $request->get('rack_id');
        $productId = $request->get('product_id');
        $currentProduct = $productId ? SupplierProduct::with(['gudangProducts.rack', 'supplier'])->find($productId) : null;

        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date)->startOfDay() 
            : Carbon::now()->subDays(60)->startOfDay();

        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date)->endOfDay() 
            : Carbon::now()->endOfDay();

        $mutationType = $request->get('mutation_type');

        // Saldo Awal
        $saldoAwalQuery = StockMutation::where('transaction_date', '<', $startDate);

        if (!empty($gudangType)) {
            $gTypes = [strtoupper($gudangType), strtolower($gudangType)];
            $saldoAwalQuery->where(function ($q) use ($gTypes) {
                $q->whereIn('gudang_type', $gTypes)
                  ->orWhereHas('rack', function ($rq) use ($gTypes) {
                      $rq->whereIn('gudang', $gTypes);
                  });
            });
        }
        if ($rackId) {
            $saldoAwalQuery->where('rack_id', $rackId);
        }
        if ($productId) {
            $saldoAwalQuery->where('supplier_product_id', $productId);
        }

        $saldoAwalQty = (int)$saldoAwalQuery->select(DB::raw('COALESCE(SUM(qty_in - qty_out), 0) as balance'))->value('balance');
        if ($saldoAwalQty < 0) $saldoAwalQty = 0;

        $unitCost = (float)($currentProduct->last_purchase_price ?? 0);
        if ($unitCost <= 0 && $currentProduct) {
            $unitCost = (float)($currentProduct->gudangProducts->max('price') ?? 0);
        }
        $saldoAwalValue = $saldoAwalQty * $unitCost;

        $mutationsQuery = StockMutation::with(['rack', 'supplierProduct', 'user'])
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if (!empty($gudangType)) {
            $gTypes = [strtoupper($gudangType), strtolower($gudangType)];
            $mutationsQuery->where(function ($q) use ($gTypes) {
                $q->whereIn('gudang_type', $gTypes)
                  ->orWhereHas('rack', function ($rq) use ($gTypes) {
                      $rq->whereIn('gudang', $gTypes);
                  });
            });
        }
        if ($rackId) {
            $mutationsQuery->where('rack_id', $rackId);
        }
        if ($productId) {
            $mutationsQuery->where('supplier_product_id', $productId);
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
            'gudangType',
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
        $gudangType = $request->get('gudang_type');
        $rackId = $request->get('rack_id');
        $productId = $request->get('product_id');
        $currentProduct = $productId ? SupplierProduct::find($productId) : null;

        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date)->startOfDay() 
            : Carbon::now()->subDays(60)->startOfDay();

        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date)->endOfDay() 
            : Carbon::now()->endOfDay();

        $mutationType = $request->get('mutation_type');

        $saldoAwalQuery = StockMutation::where('transaction_date', '<', $startDate);
        if (!empty($gudangType)) {
            $gTypes = [strtoupper($gudangType), strtolower($gudangType)];
            $saldoAwalQuery->where(function ($q) use ($gTypes) {
                $q->whereIn('gudang_type', $gTypes)
                  ->orWhereHas('rack', function ($rq) use ($gTypes) {
                      $rq->whereIn('gudang', $gTypes);
                  });
            });
        }
        if ($rackId) $saldoAwalQuery->where('rack_id', $rackId);
        if ($productId) $saldoAwalQuery->where('supplier_product_id', $productId);
        $saldoAwalQty = max(0, (int)$saldoAwalQuery->select(DB::raw('COALESCE(SUM(qty_in - qty_out), 0) as balance'))->value('balance'));

        $unitCost = (float)($currentProduct->last_purchase_price ?? 0);
        if ($unitCost <= 0 && $currentProduct) $unitCost = (float)($currentProduct->gudangProducts()->max('price') ?? 0);

        $mutationsQuery = StockMutation::with(['rack', 'supplierProduct', 'user'])
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if (!empty($gudangType)) {
            $gTypes = [strtoupper($gudangType), strtolower($gudangType)];
            $mutationsQuery->where(function ($q) use ($gTypes) {
                $q->whereIn('gudang_type', $gTypes)
                  ->orWhereHas('rack', function ($rq) use ($gTypes) {
                      $rq->whereIn('gudang', $gTypes);
                  });
            });
        }
        if ($rackId) $mutationsQuery->where('rack_id', $rackId);
        if ($productId) $mutationsQuery->where('supplier_product_id', $productId);
        if ($mutationType) $mutationsQuery->where('mutation_type', $mutationType);

        $mutations = $mutationsQuery->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $identifier = $currentProduct ? ($currentProduct->sku ?: $currentProduct->item_name) : ($rackId ? 'Rak_' . $rackId : ($gudangType ? 'Gudang_' . $gudangType : 'Global'));
        $filename = 'Kartu_Stok_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $identifier) . '_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($currentProduct, $startDate, $endDate, $saldoAwalQty, $unitCost, $mutations, $rackId, $gudangType) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel

            fputcsv($handle, ['KARTU STOK PERSEDIAAN BARANG']);
            if ($currentProduct) {
                fputcsv($handle, ['Produk', $currentProduct->item_name]);
                fputcsv($handle, ['SKU', $currentProduct->sku ?: '-']);
            } elseif ($rackId) {
                fputcsv($handle, ['Lokasi Rak', $rackId]);
                if ($gudangType) fputcsv($handle, ['Gudang', 'Gudang ' . $gudangType]);
            } elseif ($gudangType) {
                fputcsv($handle, ['Gudang', 'Gudang ' . $gudangType]);
            } else {
                fputcsv($handle, ['Cakupan', 'Semua Gudang & Rak']);
            }
            fputcsv($handle, ['Periode', $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'No',
                'Tanggal',
                'Barang / SKU',
                'Kode Mutasi',
                'No Referensi',
                'Tipe Mutasi',
                'Gudang / Lokasi Rak',
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
                $currentProduct ? ($currentProduct->item_name . ' (' . ($currentProduct->sku ?: '-') . ')') : 'Saldo Awal Periode',
                '-',
                'SALDO AWAL',
                'Saldo Awal',
                $rackId ?: ($gudangType ? 'Gudang ' . $gudangType : 'Semua Lokasi'),
                0,
                0,
                $saldoAwalQty,
                $unitCost,
                0,
                $saldoAwalQty * $unitCost,
                'Saldo Awal sebelum periode ' . $startDate->format('d/m/Y'),
            ]);

            $runningBal = $saldoAwalQty;
            $no = 1;
            foreach ($mutations as $m) {
                $runningBal += ($m->qty_in - $m->qty_out);
                if ($runningBal < 0) $runningBal = 0;
                $rowHpp = (float)($m->unit_cost ?: $unitCost);
                $productLabel = $m->supplierProduct ? ($m->supplierProduct->item_name . ' (' . ($m->supplierProduct->sku ?: '-') . ')') : '-';
                $locationLabel = ($m->rack_id ? 'Rak ' . $m->rack_id : '') . ($m->gudang_type ? ' [' . $m->gudang_type . ']' : '');

                fputcsv($handle, [
                    $no++,
                    $m->transaction_date ? $m->transaction_date->format('d/m/Y H:i') : '-',
                    $productLabel,
                    $m->mutation_code,
                    $m->reference_number ?: '-',
                    $m->type_badge['label'] ?? $m->mutation_type,
                    $locationLabel ?: 'Global',
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
