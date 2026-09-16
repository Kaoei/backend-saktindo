<?php

namespace App\Http\Controllers;

use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\GudangProduct;
use App\Models\Master_customer;
use App\Models\ProformaInvoice;
use App\Models\SalesOrder;
use App\Models\SupplierProduct;
use App\Models\WarehouseTask;
use App\Support\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SalesFinanceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | SALES ORDER
    |--------------------------------------------------------------------------
    */

   public function index()
{
    $orders = SalesOrder::query()
        ->with([
            'invoice.deliveryNote',
            'invoice.payments',
            'invoice.warehouseTask',
        ])
        ->whereNull('merged_into_sales_order_id')
        ->latest()
        ->get();

    $customers = Master_customer::orderBy('nama_customer')->get();

    $totalSalesOrders = SalesOrder::query()->count();

    $outstandingInvoices = Invoice::query()
        ->whereNull('merged_into_invoice_id')
        ->where('status', 'outstanding')
        ->where('outstanding_amount', '>', 0);

    $invoiceOutstandingCount = (clone $outstandingInvoices)->count();

    $totalOutstanding = (clone $outstandingInvoices)->sum('outstanding_amount');

    $pendingStockOrders = SalesOrder::query()
        ->whereNull('merged_into_sales_order_id')
        ->where('stock_status', 'pending')
        ->count();

    return view('sales-finance.index', compact(
        'orders',
        'customers',
        'totalSalesOrders',
        'invoiceOutstandingCount',
        'totalOutstanding',
        'pendingStockOrders'
    ));
}
 public function create()
    {
        return view('sales-finance.form', [
            'order' => new SalesOrder(['order_date' => now()]),
            'customers' => $this->customerOptions(),
            'productOptions' => $this->productOptions(),
            'action' => route('sales-finance.store'),
            'method' => 'POST',
            'submitLabel' => 'Simpan Sales Order',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedOrder($request);

        $customerId = $data['customer_id'] ?? null;
        $customerName = $data['customer_name'] ?? null;

        $unpaidInvoicesCount = 0;

        if ($customerId) {
            $customer = Master_customer::find($customerId);

            if ($customer) {
                $unpaidInvoicesCount = $customer->unpaid_invoices_count;
                $customerName = $customer->nama_customer;
            }
        } elseif ($customerName) {
            $unpaidInvoicesCount = Invoice::query()
                ->where('status', '!=', 'paid')
                ->where(function ($q) {
                    $q->where('outstanding_amount', '>', 0)
                        ->orWhereNull('outstanding_amount');
                })
                ->where(function ($q) use ($customerName) {
                    $q->whereHas('salesOrder', function ($soQ) use ($customerName) {
                        $soQ->where('customer_name', $customerName);
                    })->orWhereHas('salesOrders', function ($soQ) use ($customerName) {
                        $soQ->where('customer_name', $customerName);
                    });
                })
                ->count();
        }

        if ($unpaidInvoicesCount >= 3) {
            return back()->withInput()->withErrors([
                'customer_id' => "Client '{$customerName}' masih memiliki {$unpaidInvoicesCount} tagihan/invoice yang belum lunas. Pembuatan Sales Order baru diblokir sampai dilakukan pelunasan (maksimal 2 tagihan belum lunas)."
            ]);
        }

        $order = DB::transaction(function () use ($data) {
            $items = $data['items'];

            unset($data['items']);

            $order = SalesOrder::query()->create(
                $this->calculateOrderTotals($data, $items)
            );

            $this->syncItems($order, $items);

            return $order;
        });

        ActivityLogger::log(
            'create',
            'sales_order',
            $order
        );

        return redirect()
            ->route('sales-finance.show', $order)
            ->with(
                'status',
                'Sales Order berhasil dibuat.'
            );
    }

    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load([
            'customer',
            'items',
            'invoice.deliveryNote',
            'invoice.payments',
            'invoice.warehouseTask',
        ]);

        $invoice = $salesOrder->invoice;

        $sourceSalesOrders = collect();
        $sourceWarehouseTasks = collect();
        $allSourceTasksCompleted = true;

        if ($invoice && $invoice->invoice_type === 'gabungan') {
            $sourceSalesOrderIds = DB::table('invoice_sales_orders')
                ->where('invoice_id', $invoice->id)
                ->pluck('sales_order_id');

            $sourceSalesOrders = SalesOrder::query()
                ->with('invoice')
                ->whereIn('id', $sourceSalesOrderIds)
                ->get();

            if ($sourceSalesOrders->isEmpty()) {
                $allSourceTasksCompleted = false;
            } else {
                foreach ($sourceSalesOrders as $sourceSalesOrder) {
                    $task = WarehouseTask::query()
                        ->where('sales_order_id', $sourceSalesOrder->id)
                        ->latest('created_at')
                        ->first();

                    $sourceWarehouseTasks->put($sourceSalesOrder->id, $task);

                    if (!$task || $task->status !== 'completed') {
                        $allSourceTasksCompleted = false;
                    }
                }
            }
        }

        $warehouseReady = $invoice
            ? ($invoice->invoice_type === 'gabungan'
                ? $allSourceTasksCompleted
                : optional($invoice->warehouseTask)->status === 'completed')
            : false;

        $activeMergeInvoice = null;
        $isHistoricalInvoice = false;

        if ($invoice) {
            $foundActiveMerge = $this->findActiveMergedInvoice($invoice);

            if ($foundActiveMerge && $foundActiveMerge->id !== $invoice->id) {
                $activeMergeInvoice = $foundActiveMerge;
                $isHistoricalInvoice = true;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | INVOICE NORMAL SUDAH MENJADI HISTORI MERGE
        |--------------------------------------------------------------------------
        */

        $isHistoricalInvoice =
            $invoice &&
            $activeMergeInvoice &&
            $activeMergeInvoice->id !== $invoice->id;

        return view('sales-finance.show', [
            'order' => $salesOrder,

            'sourceSalesOrders' => $sourceSalesOrders,

            'sourceWarehouseTasks' => $sourceWarehouseTasks,

            'allSourceTasksCompleted' => $allSourceTasksCompleted,

            'warehouseReady' => $warehouseReady,

            'activeMergeInvoice' => $activeMergeInvoice,

            'isHistoricalInvoice' => $isHistoricalInvoice,
        ]);
    }

    public function edit(SalesOrder $salesOrder)
    {
        $salesOrder->load('items');

        return view('sales-finance.form', [
            'order' => $salesOrder,
            'customers' => $this->customerOptions(),
            'productOptions' => $this->productOptions(),
            'action' => route(
                'sales-finance.update',
                $salesOrder
            ),
            'method' => 'PUT',
            'submitLabel' => 'Update Sales Order',
        ]);
    }

    public function update(
        Request $request,
        SalesOrder $salesOrder
    ): RedirectResponse {
        $data = $this->validatedOrder(
            $request,
            $salesOrder
        );

        DB::transaction(function () use (
            $data,
            $salesOrder
        ) {
            $items = $data['items'];

            unset($data['items']);

            $salesOrder->update(
                $this->calculateOrderTotals(
                    $data,
                    $items
                )
            );

            $salesOrder->items()->delete();

            $this->syncItems(
                $salesOrder,
                $items
            );
        });

        ActivityLogger::log(
            'update',
            'sales_order',
            $salesOrder
        );

        return redirect()
            ->route(
                'sales-finance.show',
                $salesOrder
            )
            ->with(
                'status',
                'Sales Order berhasil diupdate.'
            );
    }

    public function destroy(
        SalesOrder $salesOrder
    ): RedirectResponse {
        ActivityLogger::log(
            'delete',
            'sales_order',
            $salesOrder,
            [
                'customer_po_number' =>
                    $salesOrder->customer_po_number,
            ]
        );

        $salesOrder->delete();

        return redirect()
            ->route('sales-finance.index')
            ->with(
                'status',
                'Sales Order berhasil dihapus.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | STOCK CHECK
    |--------------------------------------------------------------------------
    */

    public function checkStock(
        Request $request,
        SalesOrder $salesOrder
    ): RedirectResponse {
        DB::transaction(function () use ($salesOrder) {

            $salesOrder->load('items');

            $stockByProduct = GudangProduct::query()
                ->select(
                    'supplier_product_id',
                    DB::raw('SUM(qty) as total_qty')
                )
                ->whereIn(
                    'supplier_product_id',
                    $salesOrder
                        ->items
                        ->pluck('product_code')
                        ->filter()
                        ->values()
                )
                ->where('qty', '>', 0)
                ->groupBy('supplier_product_id')
                ->pluck(
                    'total_qty',
                    'supplier_product_id'
                );

            $hasPendingStock = false;

            foreach ($salesOrder->items as $item) {

                $availableStock = (float) (
                    $stockByProduct[
                        $item->product_code
                    ] ?? 0
                );

                $stockStatus =
                    $availableStock >=
                    (float) $item->quantity
                        ? 'available'
                        : 'pending';

                if ($stockStatus === 'pending') {
                    $hasPendingStock = true;
                }

                $item->update([
                    'available_stock' => $availableStock,
                    'stock_status' => $stockStatus,
                ]);
            }

            $salesOrder->update([
                'stock_status' =>
                    $hasPendingStock
                        ? 'pending'
                        : 'available',

                'order_status' =>
                    $hasPendingStock
                        ? 'pending_stock'
                        : 'ready_invoice',
            ]);
        });

        ActivityLogger::log(
            'stock_check',
            'sales_order',
            $salesOrder
        );

        return back()->with(
            'status',
            'Pengecekan stok berhasil disimpan.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE INVOICE
    |--------------------------------------------------------------------------
    */

    public function generateInvoice(
        Request $request,
        SalesOrder $salesOrder
    ): RedirectResponse {

        $data = $request->validate([
            'tax_type' => [
                'required',
                'in:js,sjb_non_pajak,sjb_pajak',
            ],

            'invoice_date' => [
                'required',
                'date',
            ],

            'due_date' => [
                'nullable',
                'date',
                'after_or_equal:invoice_date',
            ],
        ]);

        $existingInvoice = null;

        if (!empty($salesOrder->invoice_id)) {
            $existingInvoice = Invoice::query()
                ->find($salesOrder->invoice_id);
        }

        if (!$existingInvoice) {
            $existingInvoice = Invoice::query()
                ->where(
                    'sales_order_id',
                    $salesOrder->id
                )
                ->latest('id')
                ->first();
        }

        if ($existingInvoice) {

            if (
                empty($salesOrder->invoice_id) ||
                (string) $salesOrder->invoice_id !==
                (string) $existingInvoice->id
            ) {
                $salesOrder->update([
                    'invoice_id' => $existingInvoice->id,
                    'order_status' => 'invoiced',
                ]);
            }

            return redirect()
                ->route(
                    'sales-finance.show',
                    $salesOrder
                )
                ->with(
                    'status',
                    'Invoice sudah ada. Sales Order telah dihubungkan ke Invoice tersebut.'
                );
        }

        if ($salesOrder->stock_status !== 'available') {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Invoice tidak dapat dibuat karena stok Sales Order belum available.'
                );
        }

        try {

            $invoice = DB::transaction(
                function () use (
                    $data,
                    $salesOrder
                ) {

                    $taxAmount =
                        $data['tax_type'] === 'sjb_pajak'
                            ? (
                                (float) $salesOrder->subtotal
                                * 0.11
                            )
                            : 0;

                    $grandTotal =
                        (float) $salesOrder->subtotal
                        + $taxAmount;

                    $invoice = Invoice::query()->create([
                        'sales_order_id' => $salesOrder->id,
                        'invoice_number' =>
                            $this->nextDocumentNumber('INV'),
                        'invoice_type' => 'normal',
                        'tax_type' =>
                            $data['tax_type'],
                        'faktur_number' =>
                            $this->nextFakturNumber(
                                $data['tax_type']
                            ),
                        'invoice_date' =>
                            $data['invoice_date'],
                        'due_date' =>
                            $data['due_date'] ?? null,
                        'status' =>
                            'outstanding',
                        'subtotal' =>
                            $salesOrder->subtotal,
                        'tax_amount' =>
                            $taxAmount,
                        'grand_total' =>
                            $grandTotal,
                        'paid_amount' =>
                            0,
                        'outstanding_amount' =>
                            $grandTotal,
                    ]);

                    $salesOrder->update([
                        'invoice_id' =>
                            $invoice->id,
                        'order_status' =>
                            'invoiced',
                        'tax_amount' =>
                            $taxAmount,
                        'grand_total' =>
                            $grandTotal,
                    ]);

                    $warehouseTask =
                        WarehouseTask::query()
                            ->firstOrCreate(
                                [
                                    'invoice_id' =>
                                        $invoice->id,
                                ],
                                [
                                    'id' =>
                                        WarehouseTask::generateId(),
                                    'sales_order_id' =>
                                        $salesOrder->id,
                                    'assigned_to' =>
                                        null,
                                    'status' =>
                                        'waiting',
                                    'note' =>
                                        'Auto task dari Sales Order '
                                        . $salesOrder->id,
                                ]
                            );

                    $salesOrder->update([
                        'warehouse_task_reference' =>
                            $warehouseTask->id,
                    ]);

                    return $invoice;
                }
            );

            ActivityLogger::log(
                'create',
                'invoice',
                $invoice
            );

            return redirect()
                ->route(
                    'sales-finance.show',
                    $salesOrder
                )
                ->with(
                    'status',
                    'Invoice berhasil digenerate.'
                );

        } catch (\Throwable $e) {

            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal generate invoice: '
                    . $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CONSOLIDATE INVOICE
    |--------------------------------------------------------------------------
    */

    public function consolidateInvoices(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:master_customers,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'tax_type' => ['required', 'in:js,sjb_non_pajak,sjb_pajak'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | AMBIL SO AKTIF
        |--------------------------------------------------------------------------
        |
        | Termasuk:
        | - SO normal yang belum pernah merge
        | - SO merge aktif
        |
        | Tidak mengambil:
        | - SO normal yang sudah masuk merge lama
        | - merge lama yang sudah dilanjutkan ke merge baru
        |
        */
        $salesOrders = SalesOrder::query()
            ->with([
                'customer',
                'items',
                'invoice.payments',
            ])
            ->where('customer_id', $data['customer_id'])
            ->whereNull('merged_into_sales_order_id')
            ->whereHas('invoice', function ($query) {
                $query->whereNull('merged_into_invoice_id');
            })
            ->where(function ($query) use ($data) {
                $query->whereBetween('order_date', [
                    $data['period_start'],
                    $data['period_end'],
                ])->orWhereBetween('created_at', [
                    $data['period_start'] . ' 00:00:00',
                    $data['period_end'] . ' 23:59:59',
                ]);
            })
            ->orderBy('order_date')
            ->orderBy('id')
            ->get();

        if ($salesOrders->count() < 2) {
            return back()->withInput()->with(
                'error',
                'Minimal harus ada 2 Sales Order yang dapat digabung.'
            );
        }

        $request->merge([
            'sales_order_ids' =>
                $salesOrders
                    ->pluck('id')
                    ->values()
                    ->toArray(),
        ]);

        return $this->mergeInvoices($request);
    }

    public function preOrders(Request $request)
    {
        $query = SalesOrder::query()
            ->where(function ($q) {
                $q->where('is_pre_order', true)
                    ->orWhere('order_status', 'pending_stock');
            })
            ->with([
                'customer',
                'proformaInvoice',
                'items',
                'invoice',
            ]);

        if ($request->filled('dp_status')) {
            $query->where(
                'dp_status',
                $request->dp_status
            );
        }

        if ($request->filled('customer_id')) {
            $query->where(
                'customer_id',
                $request->customer_id
            );
        }

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where(
                    'customer_name',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'customer_po_number',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'id',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        $preOrders =
            $query
                ->latest('order_date')
                ->paginate(15);

        $customers =
            Master_customer::orderBy('nama_customer')
                ->get([
                    'id',
                    'nama_customer',
                ]);

        $allPreOrders =
            SalesOrder::query()
                ->where(function ($q) {
                    $q->where(
                        'is_pre_order',
                        true
                    )
                        ->orWhere(
                            'order_status',
                            'pending_stock'
                        );
                })
                ->get();

        $totalPreOrders =
            $allPreOrders->count();

        $totalPendingDp =
            $allPreOrders
                ->where(
                    'dp_status',
                    '!=',
                    'paid'
                )
                ->count();

        $totalDpCollected =
            $allPreOrders->sum('dp_paid');

        $upcomingEtaCount =
            $allPreOrders
                ->filter(function ($so) {
                    return $so->pre_order_eta &&
                        $so->pre_order_eta->isBetween(
                            now(),
                            now()->addDays(7)
                        );
                })
                ->count();

        return view(
            'sales-finance.pre-orders',
            compact(
                'preOrders',
                'customers',
                'totalPreOrders',
                'totalPendingDp',
                'totalDpCollected',
                'upcomingEtaCount'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PROFORMA INVOICE
    |--------------------------------------------------------------------------
    */

    public function generateProformaInvoice(
        Request $request,
        SalesOrder $salesOrder
    ): RedirectResponse {

        $subtotal =
            (float) $salesOrder->subtotal;

        $taxAmount =
            (float) (
                $salesOrder->tax_amount ?? 0
            );

        $grandTotal =
            (float) (
                $salesOrder->grand_total
                ?? ($subtotal + $taxAmount)
            );

        $pi =
            ProformaInvoice::updateOrCreate(
                [
                    'sales_order_id' =>
                        $salesOrder->id,
                ],
                [
                    'pi_number' =>
                        $salesOrder
                            ->proformaInvoice
                            ?->pi_number
                        ?? 'PI-'
                        . now()->format('Ymd')
                        . '-'
                        . random_int(100, 999),

                    'pi_date' =>
                        now(),

                    'status' =>
                        $salesOrder->dp_status === 'paid'
                            ? 'paid'
                            : (
                                $salesOrder->dp_paid > 0
                                    ? 'partial'
                                    : 'draft'
                            ),

                    'subtotal' =>
                        $subtotal,

                    'tax_amount' =>
                        $taxAmount,

                    'grand_total' =>
                        $grandTotal,
                ]
            );

        ActivityLogger::log(
            'create',
            'proforma_invoice',
            $pi
        );

        return back()->with(
            'status',
            'Proforma Invoice ('
            . $pi->pi_number
            . ') berhasil digenerate.'
        );
    }

    public function printProformaInvoice(
        SalesOrder $salesOrder
    ) {

        $salesOrder->load([
            'proformaInvoice',
            'items',
            'customer',
        ]);

        if (!$salesOrder->proformaInvoice) {

            $subtotal =
                (float) $salesOrder->subtotal;

            $taxAmount =
                (float) (
                    $salesOrder->tax_amount ?? 0
                );

            $grandTotal =
                (float) (
                    $salesOrder->grand_total
                    ?? ($subtotal + $taxAmount)
                );

            ProformaInvoice::create([
                'sales_order_id' =>
                    $salesOrder->id,

                'pi_number' =>
                    'PI-'
                    . now()->format('Ymd')
                    . '-'
                    . random_int(100, 999),

                'pi_date' =>
                    now(),

                'status' =>
                    $salesOrder->dp_status === 'paid'
                        ? 'paid'
                        : (
                            $salesOrder->dp_paid > 0
                                ? 'partial'
                                : 'draft'
                        ),

                'subtotal' =>
                    $subtotal,

                'tax_amount' =>
                    $taxAmount,

                'grand_total' =>
                    $grandTotal,
            ]);

            $salesOrder->load(
                'proformaInvoice'
            );
        }

        return view(
            'proforma-invoice.print',
            compact('salesOrder')
        );
    }

    public function recordDpPayment(
        Request $request,
        SalesOrder $salesOrder
    ): RedirectResponse {

        $request->validate([
            'dp_paid_amount' =>
                'required|numeric|min:1',

            'payment_date' =>
                'required|date',

            'notes' =>
                'nullable|string',
        ]);

        $amount =
            (float) $request->dp_paid_amount;

        $newDpPaid =
            (float) $salesOrder->dp_paid
            + $amount;

        $targetDp =
            (float) (
                $salesOrder->dp_amount > 0
                    ? $salesOrder->dp_amount
                    : $salesOrder->grand_total
            );

        $status =
            $newDpPaid >= $targetDp
                ? 'paid'
                : 'partial';

        $salesOrder->update([
            'dp_paid' =>
                $newDpPaid,

            'dp_status' =>
                $status,

            'pre_order_notes' =>
                trim(
                    ($salesOrder->pre_order_notes ?? '')
                    . "\nDP diterima: Rp "
                    . number_format(
                        $amount,
                        0,
                        ',',
                        '.'
                    )
                    . " pada "
                    . date(
                        'd M Y',
                        strtotime(
                            $request->payment_date
                        )
                    )
                    . (
                        $request->notes
                            ? ' ('
                            . $request->notes
                            . ')'
                            : ''
                    )
                ),
        ]);

        if ($salesOrder->proformaInvoice) {
            $salesOrder
                ->proformaInvoice
                ->update([
                    'status' =>
                        $status,
                ]);
        }

        ActivityLogger::log(
            'dp_payment',
            'sales_order',
            $salesOrder,
            [
                'amount' =>
                    $amount,
            ]
        );

        return back()->with(
            'status',
            'Pembayaran DP sebesar Rp '
            . number_format(
                $amount,
                0,
                ',',
                '.'
            )
            . ' berhasil dicatat.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | FAKTUR
    |--------------------------------------------------------------------------
    */

    public function checkFaktur(
        Request $request,
        Invoice $invoice
    ): RedirectResponse {

        if ($invoice->faktur_checked) {
            return back()->with(
                'status',
                'Faktur sudah pernah dicek dan tidak dapat diubah kembali.'
            );
        }

        $invoice->update([
            'faktur_checked' =>
                true,

            'faktur_checked_at' =>
                now(),
        ]);

        ActivityLogger::log(
            'check_faktur',
            'invoice',
            $invoice
        );

        return back()->with(
            'status',
            'Faktur '
            . $invoice->faktur_number
            . ' berhasil ditandai sudah dicek.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MERGE INVOICE
    |--------------------------------------------------------------------------
    */

    public function mergeInvoices(
        Request $request
    ): RedirectResponse {

        $request->validate([
            'sales_order_ids' =>
                ['required', 'array', 'min:2'],

            'sales_order_ids.*' =>
                [
                    'required',
                    'distinct',
                    'exists:sales_orders,id',
                ],

            'tax_type' =>
                [
                    'required',
                    'in:js,sjb_non_pajak,sjb_pajak',
                ],

            'invoice_date' =>
                [
                    'required',
                    'date',
                ],

            'due_date' =>
                [
                    'nullable',
                    'date',
                    'after_or_equal:invoice_date',
                ],
        ]);

        try {

            $result = DB::transaction(
                function () use ($request) {

                    $selectedOrders =
                        SalesOrder::query()
                            ->with([
                                'customer',
                                'items',
                                'invoice.payments',
                            ])
                            ->whereIn(
                                'id',
                                $request->sales_order_ids
                            )
                            ->whereNull(
                                'merged_into_sales_order_id'
                            )
                            ->orderBy('order_date')
                            ->orderBy('id')
                            ->lockForUpdate()
                            ->get();

                    if (
                        $selectedOrders->count()
                        !== count(
                            $request->sales_order_ids
                        )
                    ) {
                        throw new \RuntimeException(
                            'Sebagian Sales Order sudah tidak aktif atau sudah pernah digabung.'
                        );
                    }

                    if (
                        $selectedOrders->count() < 2
                    ) {
                        throw new \RuntimeException(
                            'Minimal harus ada 2 Sales Order.'
                        );
                    }

                    $customerIds =
                        $selectedOrders
                            ->pluck('customer_id')
                            ->map(
                                fn ($id) =>
                                    (string) $id
                            )
                            ->unique();

                    if (
                        $customerIds->count() !== 1
                    ) {
                        throw new \RuntimeException(
                            'Semua Sales Order harus memiliki customer yang sama.'
                        );
                    }

                    $customer =
                        $selectedOrders
                            ->first()
                            ->customer;

                    if (!$customer) {
                        throw new \RuntimeException(
                            'Customer tidak ditemukan.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | ORIGINAL ORDER
                    |--------------------------------------------------------------------------
                    */

                    $originalOrders =
                        collect();

                    $previousMergeOrders =
                        collect();

                    foreach (
                        $selectedOrders
                        as $selectedOrder
                    ) {

                        if (!$selectedOrder->invoice) {
                            throw new \RuntimeException(
                                'Sales Order '
                                . $selectedOrder->id
                                . ' belum memiliki invoice.'
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | JIKA MERGE LAMA
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $selectedOrder
                                ->invoice
                                ->invoice_type
                            === 'gabungan'
                        ) {

                            $previousMergeOrders
                                ->push(
                                    $selectedOrder
                                );

                            /*
                            |--------------------------------------------------------------------------
                            | AMBIL SOURCE DARI PIVOT
                            |--------------------------------------------------------------------------
                            */

                            $sourceIds =
                                DB::table(
                                    'invoice_sales_orders'
                                )
                                    ->where(
                                        'invoice_id',
                                        $selectedOrder
                                            ->invoice
                                            ->id
                                    )
                                    ->pluck(
                                        'sales_order_id'
                                    );

                            if (
                                $sourceIds->isEmpty()
                            ) {
                                throw new \RuntimeException(
                                    'Source Sales Order untuk merge '
                                    . $selectedOrder->id
                                    . ' tidak ditemukan.'
                                );
                            }

                            $sourceOrders =
                                SalesOrder::query()
                                    ->with([
                                        'customer',
                                        'items',
                                        'invoice.payments',
                                    ])
                                    ->whereIn(
                                        'id',
                                        $sourceIds
                                    )
                                    ->get();

                            foreach (
                                $sourceOrders
                                as $sourceOrder
                            ) {

                                if (
                                    !$sourceOrder->invoice
                                ) {
                                    throw new \RuntimeException(
                                        'Source Sales Order '
                                        . $sourceOrder->id
                                        . ' tidak memiliki invoice.'
                                    );
                                }

                                if (
                                    $sourceOrder
                                        ->invoice
                                        ->invoice_type
                                    === 'normal'
                                ) {
                                    $originalOrders
                                        ->push(
                                            $sourceOrder
                                        );
                                }
                            }

                        } else {

                            /*
                            |--------------------------------------------------------------------------
                            | SO NORMAL
                            |--------------------------------------------------------------------------
                            */

                            $originalOrders
                                ->push(
                                    $selectedOrder
                                );
                        }
                    }

                    $originalOrders =
                        $originalOrders
                            ->unique('id')
                            ->values();

                    if (
                        $originalOrders->count() < 2
                    ) {
                        throw new \RuntimeException(
                            'Tidak ditemukan minimal 2 transaksi asli untuk consolidation.'
                        );
                    }

                    $originalCustomerIds =
                        $originalOrders
                            ->pluck('customer_id')
                            ->map(
                                fn ($id) =>
                                    (string) $id
                            )
                            ->unique();

                    if (
                        $originalCustomerIds
                            ->count() !== 1
                    ) {
                        throw new \RuntimeException(
                            'Customer dari source Sales Order tidak sama.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | MERGE ITEMS
                    |--------------------------------------------------------------------------
                    */

                    $mergedItems =
                        collect();

                    foreach (
                        $originalOrders
                        as $sourceOrder
                    ) {

                        foreach (
                            $sourceOrder->items
                            as $item
                        ) {

                            $mergedItems->push([
                                'product_code' =>
                                    $item->product_code,

                                'product_name' =>
                                    $item->product_name,

                                'unit' =>
                                    $item->unit,

                                'quantity' =>
                                    (float) $item->quantity,

                                'unit_price' =>
                                    (float) $item->unit_price,

                                'discount_1' =>
                                    (float) (
                                        $item->discount_1
                                        ?? 0
                                    ),

                                'discount_2' =>
                                    (float) (
                                        $item->discount_2
                                        ?? 0
                                    ),

                                'discount_3' =>
                                    (float) (
                                        $item->discount_3
                                        ?? 0
                                    ),

                                'discount_4' =>
                                    (float) (
                                        $item->discount_4
                                        ?? 0
                                    ),

                                'line_total' =>
                                    (float) $item->line_total,
                            ]);
                        }
                    }

                    if (
                        $mergedItems->isEmpty()
                    ) {
                        throw new \RuntimeException(
                            'Tidak ada item yang dapat dimasukkan.'
                        );
                    }

                    $subtotal =
                        round(
                            $mergedItems
                                ->sum('line_total'),
                            2
                        );

                    $taxAmount =
                        $request->tax_type
                        === 'sjb_pajak'
                            ? round(
                                $subtotal * 0.11,
                                2
                            )
                            : 0;

                    $grandTotal =
                        round(
                            $subtotal
                            + $taxAmount,
                            2
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | HITUNG PAYMENT SOURCE
                    |--------------------------------------------------------------------------
                    |
                    | Payment normal tetap dihitung.
                    | Payment merge lama dihitung oleh
                    | syncMergePaymentState setelah invoice baru
                    | dibuat.
                    |--------------------------------------------------------------------------
                    */

                    $sourcePaid =
                        0;

                    foreach (
                        $originalOrders
                        as $sourceOrder
                    ) {

                        $sourceInvoice =
                            $sourceOrder->invoice;

                        $sourcePaid +=
                            (float)
                            $sourceInvoice
                                ->payments()
                                ->sum('amount');
                    }

                    $sourcePaid =
                        round(
                            $sourcePaid,
                            2
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | CREATE MERGED SALES ORDER
                    |--------------------------------------------------------------------------
                    */

                    $mergedSalesOrder =
                        new SalesOrder();

                    $mergedSalesOrder->customer_id =
                        $customer->id;

                    $mergedSalesOrder->customer_name =
                        $customer->nama_customer;

                    $mergedSalesOrder->customer_po_number =
                        'MERGE-'
                        . now()->format(
                            'YmdHis'
                        )
                        . '-'
                        . random_int(
                            100,
                            999
                        );

                    $mergedSalesOrder->po_date =
                        $request->invoice_date;

                    $mergedSalesOrder->order_date =
                        $request->invoice_date;

                    $mergedSalesOrder->sales_type =
                        $selectedOrders
                            ->first()
                            ->sales_type;

                    $mergedSalesOrder->order_status =
                        'invoiced';

                    $mergedSalesOrder->stock_status =
                        'available';

                    $mergedSalesOrder->subtotal =
                        $subtotal;

                    $mergedSalesOrder->tax_amount =
                        $taxAmount;

                    $mergedSalesOrder->grand_total =
                        $grandTotal;

                    $mergedSalesOrder->notes =
                        'Sales Order hasil consolidation dari source: '
                        . $originalOrders
                            ->pluck('id')
                            ->implode(', ');

                    $mergedSalesOrder->save();

                    /*
                    |--------------------------------------------------------------------------
                    | CREATE MERGED ITEMS
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $mergedItems
                        as $item
                    ) {

                        $mergedSalesOrder
                            ->items()
                            ->create([
                                'product_code' =>
                                    $item['product_code'],

                                'product_name' =>
                                    $item['product_name'],

                                'unit' =>
                                    $item['unit'],

                                'quantity' =>
                                    $item['quantity'],

                                'unit_price' =>
                                    $item['unit_price'],

                                'discount_1' =>
                                    $item['discount_1'],

                                'discount_2' =>
                                    $item['discount_2'],

                                'discount_3' =>
                                    $item['discount_3'],

                                'discount_4' =>
                                    $item['discount_4'],

                                'line_total' =>
                                    $item['line_total'],

                                'stock_status' =>
                                    'available',
                            ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | CREATE MERGED INVOICE
                    |--------------------------------------------------------------------------
                    */

                    $invoice =
                        Invoice::query()
                            ->create([
                                'sales_order_id' =>
                                    $mergedSalesOrder->id,

                                'invoice_number' =>
                                    $this->nextDocumentNumber(
                                        'INV-COMB'
                                    ),

                                'invoice_type' =>
                                    'gabungan',

                                'tax_type' =>
                                    $request->tax_type,

                                'faktur_number' =>
                                    $this->nextFakturNumber(
                                        $request->tax_type
                                    ),

                                'invoice_date' =>
                                    $request->invoice_date,

                                'due_date' =>
                                    $request->due_date
                                    ?? null,

                                'status' =>
                                    'outstanding',

                                'subtotal' =>
                                    $subtotal,

                                'tax_amount' =>
                                    $taxAmount,

                                'grand_total' =>
                                    $grandTotal,

                                'paid_amount' =>
                                    0,

                                'outstanding_amount' =>
                                    $grandTotal,
                            ]);

                    $mergedSalesOrder->update([
                        'invoice_id' =>
                            $invoice->id,
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | LINK ORIGINAL SO
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $originalOrders
                        as $sourceOrder
                    ) {

                        /*
                        |--------------------------------------------------------------------------
                        | JANGAN TIMPA POINTER LAMA
                        |--------------------------------------------------------------------------
                        |
                        | SO-001:
                        | merged_into = MERGE-001
                        |
                        | Jangan diganti menjadi MERGE-002.
                        |
                        */

                        if (
                            empty(
                                $sourceOrder
                                    ->merged_into_sales_order_id
                            )
                        ) {

                            $sourceOrder->update([
                                'merged_into_sales_order_id' =>
                                    $invoice
                                        ->salesOrder
                                        ->id,
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | SAMA UNTUK INVOICE NORMAL
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $sourceOrder->invoice &&
                            empty(
                                $sourceOrder
                                    ->invoice
                                    ->merged_into_invoice_id
                            )
                        ) {

                            $sourceOrder
                                ->invoice
                                ->update([
                                    'merged_into_invoice_id' =>
                                        $invoice->id,
                                ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | PIVOT
                        |--------------------------------------------------------------------------
                        */

                        DB::table(
                            'invoice_sales_orders'
                        )->insertOrIgnore([
                            'invoice_id' =>
                                $invoice->id,

                            'sales_order_id' =>
                                $sourceOrder->id,

                            'created_at' =>
                                now(),

                            'updated_at' =>
                                now(),
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | LINK MERGE SEBELUMNYA KE MERGE BARU
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $previousMergeOrders
                        as $previousMergeOrder
                    ) {

                        $previousMergeOrder->update([
                            'merged_into_sales_order_id' =>
                                $mergedSalesOrder->id,
                        ]);

                        if (
                            $previousMergeOrder->invoice
                        ) {

                            $previousMergeOrder
                                ->invoice
                                ->update([
                                    'merged_into_invoice_id' =>
                                        $invoice->id,
                                ]);
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | SYNC PAYMENT
                    |--------------------------------------------------------------------------
                    |
                    | Ini penting.
                    |
                    | Jika:
                    |
                    | MERGE-001 sudah punya payment 10.000
                    |
                    | lalu dibuat MERGE-002
                    |
                    | payment 10.000 tersebut tetap dihitung.
                    |
                    */

                    $this->syncMergePaymentState(
                        $invoice
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | WAREHOUSE TASK
                    |--------------------------------------------------------------------------
                    */

                    $warehouseTask =
                        WarehouseTask::query()
                            ->firstOrCreate(
                                [
                                    'invoice_id' =>
                                        $invoice->id,
                                ],
                                [
                                    'id' =>
                                        WarehouseTask::generateId(),

                                    'sales_order_id' =>
                                        $mergedSalesOrder->id,

                                    'assigned_to' =>
                                        null,

                                    'status' =>
                                        'waiting',

                                    'note' =>
                                        'Auto task dari Sales Order gabungan '
                                        . $mergedSalesOrder->id,
                                ]
                            );

                    $mergedSalesOrder->update([
                        'warehouse_task_reference' =>
                            $warehouseTask->id,
                    ]);

                    return [
                        'invoice' =>
                            $invoice,

                        'sales_order' =>
                            $mergedSalesOrder,

                        'source_orders' =>
                            $originalOrders,

                        'previous_merge_orders' =>
                            $previousMergeOrders,

                        'total_paid' =>
                            $invoice->fresh()->paid_amount,

                        'subtotal' =>
                            $subtotal,

                        'grand_total' =>
                            $grandTotal,
                    ];
                }
            );

            ActivityLogger::log(
                'create',
                'sales_order',
                $result['sales_order'],
                [
                    'type' =>
                        'consolidated',

                    'source_sales_orders' =>
                        $result['source_orders']
                            ->pluck('id')
                            ->values()
                            ->toArray(),

                    'previous_merge_sales_orders' =>
                        $result['previous_merge_orders']
                            ->pluck('id')
                            ->values()
                            ->toArray(),

                    'invoice_id' =>
                        $result['invoice']->id,

                    'total_previous_payment' =>
                        $result['total_paid'],
                ]
            );

            ActivityLogger::log(
                'create',
                'invoice',
                $result['invoice'],
                [
                    'type' =>
                        'consolidated',

                    'sales_order_id' =>
                        $result['sales_order']->id,

                    'source_sales_orders' =>
                        $result['source_orders']
                            ->pluck('id')
                            ->values()
                            ->toArray(),

                    'previous_payment_total' =>
                        $result['total_paid'],
                ]
            );

            return redirect()
                ->route(
                    'sales-finance.show',
                    $result['sales_order']
                )
                ->with(
                    'status',
                    'Berhasil menggabungkan '
                    . $result['source_orders']->count()
                    . ' Sales Order. Total Rp '
                    . number_format(
                        $result['grand_total'],
                        0,
                        ',',
                        '.'
                    )
                    . '. Pembayaran sebelumnya Rp '
                    . number_format(
                        $result['total_paid'],
                        0,
                        ',',
                        '.'
                    )
                    . ' tetap diperhitungkan.'
                );

        } catch (\Throwable $e) {

            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal menggabungkan Sales Order: '
                    . $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELIVERY NOTE
    |--------------------------------------------------------------------------
    */

    public function storeDeliveryNote(
        Request $request,
        Invoice $invoice
    ): RedirectResponse {

        $data = $request->validate([
            'delivery_date' => [
                'required',
                'date',
            ],

            'status' => [
                'required',
                'in:draft,process,delivered,cancelled',
            ],

            'pic_sales' => [
                'nullable',
                'string',
                'max:255',
            ],

            'pic_gudang' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        $deliveryNote =
            DeliveryNote::query()
                ->updateOrCreate(
                    [
                        'invoice_id' =>
                            $invoice->id,
                    ],
                    array_merge(
                        $data,
                        [
                            'delivery_note_number' =>
                                $invoice
                                    ->deliveryNote
                                    ?->delivery_note_number
                                ?? $this->nextDocumentNumber(
                                    'SJ'
                                ),
                        ]
                    )
                );

        if (
            $data['status'] ===
            'delivered'
        ) {

            $invoice
                ->salesOrder()
                ->update([
                    'order_status' =>
                        'delivered',
                ]);
        }

        ActivityLogger::log(
            'save',
            'delivery_note',
            $deliveryNote
        );

        return back()->with(
            'status',
            'Surat Jalan berhasil disimpan.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENT
    |--------------------------------------------------------------------------
    */

    public function storePayment(
        Request $request,
        Invoice $invoice
    ): RedirectResponse {

        $activeMerge = $this->findActiveMergedInvoice($invoice);

        if ($activeMerge && $activeMerge->id !== $invoice->id) {
            return redirect()
                ->route('sales-finance.show', $activeMerge->salesOrder)
                ->with(
                    'error',
                    'Invoice ' . $invoice->invoice_number
                    . ' sudah digabung/diteruskan ke invoice combination '
                    . $activeMerge->invoice_number
                    . '. Silakan lakukan pembayaran pada invoice combination tersebut.'
                );
        }
        $invoice->refresh();

        if ((float) $invoice->outstanding_amount <= 0) {
            return back()->with('status', 'Invoice sudah lunas.');
        }

        if (!$this->isInvoiceWarehouseReady($invoice)) {
            return back()->with(
                'error',
                $invoice->invoice_type === 'gabungan'
                    ? 'Pembayaran combination belum bisa dilakukan karena masih ada Warehouse Task dari Sales Order sumber yang belum completed.'
                    : 'Pembayaran belum bisa dilakukan karena Warehouse Task untuk invoice ini belum completed.'
            );
        }
        /*
        |--------------------------------------------------------------------------
        | SYNC SEBELUM VALIDASI
        |--------------------------------------------------------------------------
        */

        if (
            $invoice->invoice_type ===
            'normal'
        ) {

            $this->syncPaymentChain(
                $invoice
            );

        } elseif (
            $invoice->invoice_type ===
            'gabungan'
        ) {

            $this->syncMergePaymentState(
                $invoice
            );
        }

        $invoice->refresh();

        if (
            (float) $invoice->outstanding_amount
            <= 0
        ) {
            return back()->with(
                'status',
                'Invoice sudah lunas.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDASI PAYMENT
        |--------------------------------------------------------------------------
        */

        $data = $request->validate([
            'payment_date' => [
                'required',
                'date',
            ],

            'method' => [
                'required',
                'in:cash,transfer_bank,qris,giro',
            ],

            'receiving_account' => [
                'required',
                'in:js,sjb',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:1',
                'max:' . max(
                    1,
                    (float) $invoice
                        ->outstanding_amount
                ),
            ],

            'bank_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'giro_number' => [
                'nullable',
                'string',
                'max:255',
            ],

            'giro_due_date' => [
                'nullable',
                'date',
            ],

            'giro_status' => [
                'nullable',
                'in:pending,cleared,rejected',
            ],

            'reference_number' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        if (
            $data['method'] === 'giro' &&
            empty($data['giro_status'])
        ) {

            $data['giro_status'] =
                'pending';
        }

        if (
            $data['method'] === 'giro' &&
            !empty($data['giro_number']) &&
            empty($data['reference_number'])
        ) {

            $data['reference_number'] =
                $data['giro_number'];
        }

        /*
        |--------------------------------------------------------------------------
        | CREATE PAYMENT
        |--------------------------------------------------------------------------
        */

        $payment =
            DB::transaction(
                function () use (
                    $data,
                    $invoice
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | PAYMENT HANYA DIBUAT DI INVOICE YANG DIBAYAR
                    |--------------------------------------------------------------------------
                    */

                    $payment =
                        InvoicePayment::query()
                            ->create([
                                'invoice_id' =>
                                    $invoice->id,

                                'payment_number' =>
                                    $this->nextDocumentNumber(
                                        'PAY'
                                    ),

                                'payment_date' =>
                                    $data['payment_date'],

                                'method' =>
                                    $data['method'],

                                'receiving_account' =>
                                    $data['receiving_account'],

                                'amount' =>
                                    $data['amount'],

                                'bank_name' =>
                                    $data['bank_name']
                                    ?? null,

                                'giro_number' =>
                                    $data['giro_number']
                                    ?? null,

                                'giro_due_date' =>
                                    $data['giro_due_date']
                                    ?? null,

                                'giro_status' =>
                                    $data['giro_status']
                                    ?? null,

                                'reference_number' =>
                                    $data['reference_number']
                                    ?? null,

                                'notes' =>
                                    $data['notes']
                                    ?? null,
                            ]);

                    /*
                    |--------------------------------------------------------------------------
                    | SYNC ULANG
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $invoice->invoice_type ===
                        'normal'
                    ) {

                        $this->syncPaymentChain(
                            $invoice
                        );

                    } elseif (
                        $invoice->invoice_type ===
                        'gabungan'
                    ) {

                        $this->syncMergePaymentState(
                            $invoice
                        );
                    }

                    return $payment;
                }
            );

        ActivityLogger::log(
            'create',
            'invoice_payment',
            $payment
        );

        return back()->with(
            'status',
            'Pembayaran berhasil dicatat dan invoice terkait telah disinkronkan.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENT CHAIN
    |--------------------------------------------------------------------------
    |
    | NORMAL INVOICE
    |
    | SO-001
    |   ↓
    | INV-001
    |   ↓
    | MERGE-001
    |   ↓
    | MERGE-002
    |
    */

    private function syncPaymentChain(
        Invoice $sourceInvoice
    ): void {

        /*
        |--------------------------------------------------------------------------
        | CARI MERGE AKTIF
        |--------------------------------------------------------------------------
        */

        $activeMerge =
            $this->findActiveMergedInvoice(
                $sourceInvoice
            );

        /*
        |--------------------------------------------------------------------------
        | HITUNG PAYMENT LANGSUNG SOURCE
        |--------------------------------------------------------------------------
        */

        $directPaid =
            round(
                (float) $sourceInvoice
                    ->payments()
                    ->sum('amount'),
                2
            );

        $grandTotal =
            (float) $sourceInvoice
                ->grand_total;

        /*
        |--------------------------------------------------------------------------
        | BELUM MERGE
        |--------------------------------------------------------------------------
        */

        if (!$activeMerge) {

            $paid =
                min(
                    $directPaid,
                    $grandTotal
                );

            $outstanding =
                max(
                    0,
                    round(
                        $grandTotal
                        - $paid,
                        2
                    )
                );

            $sourceInvoice->update([
                'paid_amount' =>
                    $paid,

                'outstanding_amount' =>
                    $outstanding,

                'status' =>
                    $outstanding <= 0
                        ? 'paid'
                        : 'outstanding',
            ]);

            if (
                $outstanding <= 0
            ) {

                $sourceInvoice
                    ->salesOrder()
                    ->update([
                        'order_status' =>
                            'completed',
                    ]);
            }

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | SUDAH MERGE
        |--------------------------------------------------------------------------
        */

        $this->syncMergePaymentState(
            $activeMerge
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SYNC MERGE PAYMENT
    |--------------------------------------------------------------------------
    |
    | Payment bisa datang dari:
    |
    | 1. Invoice normal
    | 2. MERGE-001
    | 3. MERGE-002
    | 4. MERGE-003
    |
    | Semua payment tersebut dihitung,
    | tetapi payment row TIDAK dipindahkan.
    |
    */

    private function syncMergePaymentState(
        Invoice $activeMerge
    ): void {

        if (
            $activeMerge->invoice_type
            !== 'gabungan'
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | SOURCE INVOICE ASLI
        |--------------------------------------------------------------------------
        */

        $sourceInvoices =
            $this->getOriginalInvoicesForMerge(
                $activeMerge
            );

        /*
        |--------------------------------------------------------------------------
        | PAYMENT LANGSUNG DARI SOURCE
        |--------------------------------------------------------------------------
        */

        $sourceDirectPaid =
            0;

        foreach (
            $sourceInvoices
            as $sourceInvoice
        ) {

            $sourceDirectPaid +=
                round(
                    (float) $sourceInvoice
                        ->payments()
                        ->sum('amount'),
                    2
                );
        }

        $sourceDirectPaid =
            round(
                $sourceDirectPaid,
                2
            );

        /*
        |--------------------------------------------------------------------------
        | PAYMENT DARI SEMUA MERGE HISTORY
        |--------------------------------------------------------------------------
        */

        $mergeInvoices =
            $this->getMergeInvoiceHistory(
                $activeMerge
            );

        $mergeDirectPaid =
            0;

        foreach (
            $mergeInvoices
            as $mergeInvoice
        ) {

            $mergeDirectPaid +=
                round(
                    (float) $mergeInvoice
                        ->payments()
                        ->sum('amount'),
                    2
                );
        }

        $mergeDirectPaid =
            round(
                $mergeDirectPaid,
                2
            );

        /*
        |--------------------------------------------------------------------------
        | TOTAL PAYMENT
        |--------------------------------------------------------------------------
        */

        $totalPaid =
            round(
                $sourceDirectPaid
                + $mergeDirectPaid,
                2
            );

        $totalPaid =
            min(
                $totalPaid,
                (float) $activeMerge
                    ->grand_total
            );

        $outstanding =
            max(
                0,
                round(
                    (float) $activeMerge
                        ->grand_total
                    - $totalPaid,
                    2
                )
            );

        /*
        |--------------------------------------------------------------------------
        | UPDATE MERGE AKTIF
        |--------------------------------------------------------------------------
        */

        $activeMerge->update([
            'paid_amount' =>
                $totalPaid,

            'outstanding_amount' =>
                $outstanding,

            'status' =>
                $outstanding <= 0
                    ? 'paid'
                    : 'outstanding',
        ]);

        /*
        |--------------------------------------------------------------------------
        | ALOKASI PAYMENT MERGE KE SOURCE
        |--------------------------------------------------------------------------
        |
        | CONTOH:
        |
        | SO-001 = 100.000
        | SO-002 = 100.000
        |
        | SO-001 sudah bayar 10.000
        |
        | MERGE bayar 20.000
        |
        | Maka:
        |
        | SO-001 = 30.000
        | SO-002 = 0
        |
        | Tidak ada payment row baru di SO-001.
        |
        */

        $remainingMergePayment =
            $mergeDirectPaid;

        foreach (
            $sourceInvoices
            as $sourceInvoice
        ) {

            $directPaid =
                round(
                    (float) $sourceInvoice
                        ->payments()
                        ->sum('amount'),
                    2
                );

            $sourceGrandTotal =
                (float) $sourceInvoice
                    ->grand_total;

            $remainingSource =
                max(
                    0,
                    round(
                        $sourceGrandTotal
                        - $directPaid,
                        2
                    )
                );

            $allocatedFromMerge =
                min(
                    $remainingSource,
                    $remainingMergePayment
                );

            $sourcePaid =
                round(
                    $directPaid
                    + $allocatedFromMerge,
                    2
                );

            $sourceOutstanding =
                max(
                    0,
                    round(
                        $sourceGrandTotal
                        - $sourcePaid,
                        2
                    )
                );

            /*
            |--------------------------------------------------------------------------
            | UPDATE REFLECTION SOURCE
            |--------------------------------------------------------------------------
            */

            $sourceInvoice->update([
                'paid_amount' =>
                    $sourcePaid,

                'outstanding_amount' =>
                    $sourceOutstanding,

                'status' =>
                    $sourceOutstanding <= 0
                        ? 'paid'
                        : 'outstanding',
            ]);

            if (
                $sourceOutstanding <= 0
            ) {

                $sourceInvoice
                    ->salesOrder()
                    ->update([
                        'order_status' =>
                            'completed',
                    ]);

            } else {

                /*
                |--------------------------------------------------------------------------
                | JIKA MASIH ADA OUTSTANDING
                |--------------------------------------------------------------------------
                */

                $sourceInvoice
                    ->salesOrder()
                    ->where(
                        'order_status',
                        'completed'
                    )
                    ->update([
                        'order_status' =>
                            'invoiced',
                    ]);
            }

            $remainingMergePayment =
                round(
                    $remainingMergePayment
                    - $allocatedFromMerge,
                    2
                );

            if (
                $remainingMergePayment <= 0
            ) {
                break;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE SALES ORDER MERGE
        |--------------------------------------------------------------------------
        */

        if (
            $activeMerge->salesOrder
        ) {

            $activeMerge
                ->salesOrder
                ->update([
                    'order_status' =>
                        $outstanding <= 0
                            ? 'completed'
                            : 'invoiced',
                ]);
        }
    }

private function isInvoiceWarehouseReady(Invoice $invoice): bool
{
    if ($invoice->invoice_type === 'gabungan') {

        $sourceSalesOrderIds = DB::table('invoice_sales_orders')
            ->where('invoice_id', $invoice->id)
            ->pluck('sales_order_id');

        if ($sourceSalesOrderIds->isEmpty()) {
            return false;
        }

        foreach ($sourceSalesOrderIds as $soId) {
            $task = WarehouseTask::query()
                ->where('sales_order_id', $soId)
                ->latest('created_at')
                ->first();

            if (!$task || $task->status !== 'completed') {
                return false;
            }
        }

        return true;
    }

    $invoice->loadMissing('warehouseTask');

    return optional($invoice->warehouseTask)->status === 'completed';
}

private function findActiveMergedInvoice(
    Invoice $invoice
): ?Invoice {
    $current = $invoice;
    $visited = [];

    while ($current) {
        if (in_array($current->id, $visited, true)) {
            return null;
        }

        $visited[] = $current->id;

        if (!empty($current->merged_into_invoice_id)) {
            $current = Invoice::query()
                ->find($current->merged_into_invoice_id);

            if (!$current) {
                return null;
            }

            continue;
        }

        break;
    }

    if (
        $current &&
        $current->id !== $invoice->id &&
        $current->invoice_type === 'gabungan'
    ) {
        return $current;
    }

    /*
    |--------------------------------------------------------------------------
    | FALLBACK: CEK MELALUI PIVOT
    |--------------------------------------------------------------------------
    */

    if ($invoice->sales_order_id) {
        $mergeInvoiceId = DB::table('invoice_sales_orders')
            ->join(
                'invoices',
                'invoices.id',
                '=',
                'invoice_sales_orders.invoice_id'
            )
            ->where(
                'invoice_sales_orders.sales_order_id',
                $invoice->sales_order_id
            )
            ->where(
                'invoices.invoice_type',
                'gabungan'
            )
            ->where(
                'invoices.id',
                '!=',
                $invoice->id
            )
            ->orderByDesc('invoices.id')
            ->value('invoices.id');

        if ($mergeInvoiceId) {
            return Invoice::query()->find($mergeInvoiceId);
        }
    }

    return null;
}

    private function getOriginalInvoicesForMerge(
        Invoice $mergeInvoice
    ) {

        $sourceIds =
            DB::table(
                'invoice_sales_orders'
            )
                ->where(
                    'invoice_id',
                    $mergeInvoice->id
                )
                ->pluck(
                    'sales_order_id'
                );

        if (
            $sourceIds->isEmpty()
        ) {
            return collect();
        }

        return Invoice::query()
            ->where(
                'invoice_type',
                'normal'
            )
            ->whereIn(
                'sales_order_id',
                $sourceIds
            )
            ->orderBy('id')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | MERGE HISTORY
    |--------------------------------------------------------------------------
    |
    | MERGE-001 -> MERGE-002 -> MERGE-003
    |
    | Jika aktif MERGE-003:
    |
    | return:
    | MERGE-003
    | MERGE-002
    | MERGE-001
    |
    */

    private function getMergeInvoiceHistory(
        Invoice $activeMerge
    ) {

        $result =
            collect();

        $current =
            $activeMerge;

        $visited =
            [];

        while ($current) {

            if (
                in_array(
                    $current->id,
                    $visited,
                    true
                )
            ) {
                break;
            }

            $visited[] =
                $current->id;

            $result->push(
                $current
            );

            /*
            |--------------------------------------------------------------------------
            | CARI MERGE SEBELUMNYA
            |--------------------------------------------------------------------------
            */

            $previous =
                Invoice::query()
                    ->where(
                        'invoice_type',
                        'gabungan'
                    )
                    ->where(
                        'merged_into_invoice_id',
                        $current->id
                    )
                    ->first();

            if (!$previous) {
                break;
            }

            $current =
                $previous;
        }

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATED ORDER
    |--------------------------------------------------------------------------
    */

    private function validatedOrder(
        Request $request,
        ?SalesOrder $order = null
    ): array {

        $data = $request->validate([
            'customer_id' =>
                [
                    'nullable',
                    'exists:master_customers,id',
                ],

            'customer_name' =>
                [
                    'nullable',
                    'string',
                    'max:255',
                ],

            'customer_po_number' =>
                [
                    'nullable',
                    'string',
                    'max:100',
                    Rule::unique(
                        'sales_orders',
                        'customer_po_number'
                    )->ignore(
                        $order?->id
                    ),
                ],

            'po_date' =>
                [
                    'nullable',
                    'date',
                ],

            'order_date' =>
                [
                    'required',
                    'date',
                ],

            'sales_type' =>
                [
                    'nullable',
                    'in:js,sjb',
                ],

            'order_status' =>
                [
                    'required',
                    'in:draft,stock_check,ready_to_invoice,pending_stock,invoiced,delivered,completed,cancelled',
                ],

            'is_pre_order' =>
                [
                    'nullable',
                ],

            'pre_order_eta' =>
                [
                    'nullable',
                    'date',
                ],

            'dp_amount' =>
                [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

            'pre_order_notes' =>
                [
                    'nullable',
                    'string',
                ],

            'notes' =>
                [
                    'nullable',
                    'string',
                ],

            'items' =>
                [
                    'required',
                    'array',
                    'min:1',
                ],

            'items.*.product_code' =>
                [
                    'required',
                    'exists:supplier_products,id',
                ],

            'items.*.product_name' =>
                [
                    'nullable',
                    'string',
                    'max:255',
                ],

            'items.*.unit' =>
                [
                    'required',
                    'string',
                    'max:50',
                ],

            'items.*.quantity' =>
                [
                    'required',
                    'numeric',
                    'min:0.01',
                ],

            'items.*.unit_price' =>
                [
                    'required',
                    'numeric',
                    'min:0',
                ],

            'items.*.discount_1' =>
                [
                    'nullable',
                    'numeric',
                    'min:0',
                    'max:100',
                ],

            'items.*.discount_2' =>
                [
                    'nullable',
                    'numeric',
                    'min:0',
                    'max:100',
                ],

            'items.*.discount_3' =>
                [
                    'nullable',
                    'numeric',
                    'min:0',
                    'max:100',
                ],

            'items.*.discount_4' =>
                [
                    'nullable',
                    'numeric',
                    'min:0',
                    'max:100',
                ],
        ]);

        $data['is_pre_order'] =
            $request->boolean(
                'is_pre_order'
            );

        $data['pre_order_eta'] =
            $request->input(
                'pre_order_eta'
            ) ?: null;

        $data['dp_amount'] =
            (float) (
                $request->input(
                    'dp_amount'
                ) ?? 0
            );

        $data['pre_order_notes'] =
            $request->input(
                'pre_order_notes'
            ) ?: null;

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | CALCULATE ORDER TOTALS
    |--------------------------------------------------------------------------
    */

    private function calculateOrderTotals(
        array $data,
        array $items
    ): array {

        $subtotal =
            collect($items)
                ->sum(
                    function ($item) {

                        $qty =
                            (float) (
                                $item['quantity']
                                ?? 0
                            );

                        $price =
                            (float) (
                                $item['unit_price']
                                ?? 0
                            );

                        $net =
                            $price
                            * (
                                1 -
                                (
                                    (float) (
                                        $item[
                                            'discount_1'
                                        ] ?? 0
                                    ) / 100
                                )
                            )
                            * (
                                1 -
                                (
                                    (float) (
                                        $item[
                                            'discount_2'
                                        ] ?? 0
                                    ) / 100
                                )
                            )
                            * (
                                1 -
                                (
                                    (float) (
                                        $item[
                                            'discount_3'
                                        ] ?? 0
                                    ) / 100
                                )
                            )
                            * (
                                1 -
                                (
                                    (float) (
                                        $item[
                                            'discount_4'
                                        ] ?? 0
                                    ) / 100
                                )
                            );

                        return $qty * $net;
                    }
                );

        return array_merge(
            $data,
            [
                'subtotal' =>
                    $subtotal,

                'tax_amount' =>
                    $data['tax_amount']
                    ?? 0,

                'grand_total' =>
                    $subtotal
                    + (
                        (float) (
                            $data['tax_amount']
                            ?? 0
                        )
                    ),
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SYNC ITEMS
    |--------------------------------------------------------------------------
    */

    private function syncItems(
        SalesOrder $order,
        array $items
    ): void {

        $products =
            SupplierProduct::query()
                ->whereIn(
                    'id',
                    collect($items)
                        ->pluck(
                            'product_code'
                        )
                        ->filter()
                        ->values()
                )
                ->get()
                ->keyBy('id');

        foreach (
            $items
            as $item
        ) {

            $product =
                $products->get(
                    $item['product_code']
                );

            $quantity =
                (float) $item['quantity'];

            $unitPrice =
                (float) $item['unit_price'];

            $d1 =
                (float) (
                    $item['discount_1']
                    ?? 0
                );

            $d2 =
                (float) (
                    $item['discount_2']
                    ?? 0
                );

            $d3 =
                (float) (
                    $item['discount_3']
                    ?? 0
                );

            $d4 =
                (float) (
                    $item['discount_4']
                    ?? 0
                );

            $netUnitPrice =
                $unitPrice
                * (1 - $d1 / 100)
                * (1 - $d2 / 100)
                * (1 - $d3 / 100)
                * (1 - $d4 / 100);

            $order->items()->create([
                'product_code' =>
                    $product->id,

                'product_name' =>
                    $product->item_name,

                'unit' =>
                    $product->unit
                    ?: $item['unit'],

                'quantity' =>
                    $quantity,

                'unit_price' =>
                    $unitPrice,

                'discount_1' =>
                    $d1,

                'discount_2' =>
                    $d2,

                'discount_3' =>
                    $d3,

                'discount_4' =>
                    $d4,

                'line_total' =>
                    round(
                        $quantity
                        * $netUnitPrice,
                        2
                    ),

                'stock_status' =>
                    'unchecked',
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CUSTOMER OPTIONS
    |--------------------------------------------------------------------------
    */

    private function customerOptions()
    {
        return Master_customer::query()
            ->orderBy('nama_customer')
            ->get([
                'id',
                'nama_customer',
                'termin',
            ])
            ->map(
                function ($c) {

                    $c->unpaid_count =
                        $c->unpaid_invoices_count;

                    $c->is_blocked =
                        $c->is_blocked_for_so;

                    return $c;
                }
            );
    }

    /*
    |--------------------------------------------------------------------------
    | PRODUCT OPTIONS
    |--------------------------------------------------------------------------
    */

    private function productOptions()
    {
        return SupplierProduct::query()
            ->where(
                'status',
                'active'
            )
            ->with([
                'gudangProducts.rack',
            ])
            ->orderBy(
                'item_name'
            )
            ->get([
                'id',
                'sku',
                'part_number',
                'item_name',
                'unit',
                'last_purchase_price',
            ])
            ->map(
                function ($product) {

                    $gProducts =
                        $product
                            ->gudangProducts;

                    $availableStock =
                        $gProducts->sum('qty');

                    $locations = [];

                    foreach (
                        $gProducts
                        as $gp
                    ) {

                        if (
                            $gp->qty > 0
                        ) {

                            $gType =
                                strtoupper(
                                    $gp->gudang_type
                                    ?: 'JS'
                                );

                            $rakKode =
                                $gp->rack
                                    ->rak_kode
                                ?? $gp->rack_id
                                ?? '-';

                            $unitStr =
                                $product->unit
                                ?: 'pcs';

                            $locations[] =
                                "{$gType}: {$rakKode} ({$gp->qty} {$unitStr})";
                        }
                    }

                    if (
                        empty($locations)
                    ) {

                        $firstGp =
                            $gProducts
                                ->first();

                        if ($firstGp) {

                            $gType =
                                strtoupper(
                                    $firstGp
                                        ->gudang_type
                                    ?: 'JS'
                                );

                            $rakKode =
                                $firstGp
                                    ->rack
                                    ->rak_kode
                                ?? $firstGp
                                    ->rack_id
                                ?? '-';

                            $unitStr =
                                $product->unit
                                ?: 'pcs';

                            $locations[] =
                                "{$gType}: {$rakKode} (0 {$unitStr})";

                        } else {

                            $locations[] =
                                '- Belum di Rak -';
                        }
                    }

                    $product->available_stock =
                        $availableStock;

                    $product->warehouse_location =
                        implode(
                            ' | ',
                            $locations
                        );

                    $gProduct =
                        $gProducts
                            ->where(
                                'price',
                                '>',
                                0
                            )
                            ->sortByDesc(
                                'qty'
                            )
                            ->first()
                        ?: (
                            $gProducts
                                ->firstWhere(
                                    'qty',
                                    '>',
                                    0
                                )
                            ?: $gProducts->first()
                        );

                    $price =
                        $gProduct &&
                        floatval(
                            $gProduct->price
                        ) > 0
                            ? floatval(
                                $gProduct->price
                            )
                            : floatval(
                                $product
                                    ->last_purchase_price
                            );

                    if (
                        $price <= 0
                    ) {

                        $masterProd =
                            \App\Models\Product::where(
                                function ($q)
                                use ($product) {

                                    if (
                                        $product->sku
                                    ) {

                                        $q->where(
                                            'seller_sku',
                                            $product->sku
                                        );
                                    }

                                    if (
                                        $product->item_name
                                    ) {

                                        $q->orWhere(
                                            'product_name',
                                            $product->item_name
                                        );
                                    }
                                }
                            )->first();

                        if (
                            $masterProd &&
                            floatval(
                                $masterProd->price
                            ) > 0
                        ) {

                            $price =
                                floatval(
                                    $masterProd
                                        ->price
                                );
                        }
                    }

                    $product->custom_price =
                        $price;

                    $product->discount_percent =
                        $gProduct
                            ? floatval(
                                $gProduct
                                    ->discount
                            )
                            : 0;

                    $product->unit =
                        $product->unit
                        ?: 'pcs';

                    return $product;
                }
            );
    }

    /*
    |--------------------------------------------------------------------------
    | DOCUMENT NUMBER
    |--------------------------------------------------------------------------
    */

    private function nextDocumentNumber(
        string $prefix
    ): string {

        return $prefix
            . '-'
            . now()->format(
                'YmdHis'
            )
            . '-'
            . random_int(
                100,
                999
            );
    }

    private function nextFakturNumber(
        string $taxType
    ): string {

        $year =
            now()->format('y');

        $month =
            now()->format('m');

        $prefix =
            match ($taxType) {

                'sjb_non_pajak',
                'sjb_pajak' =>
                    'SJB',

                default =>
                    'JS',
            };

        $count =
            \App\Models\Invoice::whereYear(
                'invoice_date',
                now()->year
            )
                ->whereMonth(
                    'invoice_date',
                    now()->month
                )
                ->where(
                    'faktur_number',
                    'like',
                    "{$prefix}/{$year}/{$month}/%"
                )
                ->count();

        $sequence =
            str_pad(
                $count + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

        return
            "{$prefix}/{$year}/{$month}/{$sequence}";
    }

    /*
    |--------------------------------------------------------------------------
    | INVOICE PDF
    |--------------------------------------------------------------------------
    */

    public function invoicePdf(
        Invoice $invoice
    ) {

        $invoice->load([
            'salesOrder.customer',
            'salesOrder.items',
            'payments',
            'deliveryNote',
            'warehouseTask',
        ]);

        $pdf =
            Pdf::loadView(
                'sales-finance.pdf.invoice',
                [
                    'invoice' =>
                        $invoice,
                ]
            );

        $pdf->setPaper(
            'a4',
            'landscape'
        );

        $canvas =
            $pdf
                ->getDomPDF()
                ->getCanvas();

        $canvas->page_text(
            750,
            570,
            'Page {PAGE_NUM} of {PAGE_COUNT}',
            null,
            7,
            [0, 0, 0]
        );

        return $pdf->download(
            'Invoice-'
            . (
                $invoice->invoice_number
                ?? $invoice->id
            )
            . '.pdf'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PRINT DELIVERY NOTE
    |--------------------------------------------------------------------------
    */

    public function printDeliveryNote(
        DeliveryNote $deliveryNote
    ) {

        $deliveryNote->load([
            'invoice.salesOrder.customer',
            'invoice.salesOrder.items',
            'invoice',
        ]);

        $pdf =
            Pdf::loadView(
                'sales-finance.pdf.delivery-note',
                [
                    'deliveryNote' =>
                        $deliveryNote,

                    'invoice' =>
                        $deliveryNote->invoice,

                    'order' =>
                        $deliveryNote
                            ->invoice
                            ?->salesOrder,
                ]
            );

        $pdf->setPaper(
            'a4',
            'portrait'
        );

        return $pdf->stream(
            'Surat-Jalan-'
            . $deliveryNote
                ->delivery_note_number
            . '.pdf'
        );
    }
}