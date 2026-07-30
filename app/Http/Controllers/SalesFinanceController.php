<?php

namespace App\Http\Controllers;

use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\GudangProduct;
use App\Models\Master_customer;
use App\Models\SalesReturn;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SupplierProduct;
use App\Models\WarehouseTask;
use App\Support\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SalesFinanceController extends Controller
{
    public function index()
    {
        // Menggunakan relasi salesOrders (Many to Many via Pivot)
        $orders = SalesOrder::query()
            ->with(['invoices.deliveryNotes', 'invoices.payments', 'invoices.warehouseTask'])
            ->latest()
            ->get();

        return view('sales-finance.index', [
            'orders' => $orders,
            'customers' => $this->customerOptions(),
        ]);
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

        $order = DB::transaction(function () use ($data) {
            $items = $data['items'];
            unset($data['items']);

            $order = SalesOrder::query()->create($this->calculateOrderTotals($data, $items));
            $this->syncItems($order, $items);

            return $order;
        });

        ActivityLogger::log('create', 'sales_order', $order);

        return redirect()->route('sales-finance.show', $order)->with('status', 'Sales Order berhasil dibuat.');
    }

    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load([
            'customer',
            'items',
            'invoices.salesOrders.items',
            'invoices.deliveryNotes.items.salesOrderItem',
            'invoices.deliveryNotes.returns.items.salesOrderItem',
            'invoices.payments',
            'invoices.warehouseTask',
        ]);

        return view('sales-finance.show', ['order' => $salesOrder]);
    }

    public function edit(SalesOrder $salesOrder)
    {
        $salesOrder->load('items');

        return view('sales-finance.form', [
            'order' => $salesOrder,
            'customers' => $this->customerOptions(),
            'productOptions' => $this->productOptions(),
            'action' => route('sales-finance.update', $salesOrder),
            'method' => 'PUT',
            'submitLabel' => 'Update Sales Order',
        ]);
    }

    public function update(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $data = $this->validatedOrder($request, $salesOrder);

        DB::transaction(function () use ($data, $salesOrder) {
            $items = $data['items'];
            unset($data['items']);

            $salesOrder->update($this->calculateOrderTotals($data, $items));
            $salesOrder->items()->delete();
            $this->syncItems($salesOrder, $items);
        });

        ActivityLogger::log('update', 'sales_order', $salesOrder);

        return redirect()->route('sales-finance.show', $salesOrder)->with('status', 'Sales Order berhasil diupdate.');
    }

    public function destroy(SalesOrder $salesOrder): RedirectResponse
    {
        $salesOrder->update(['order_status' => 'cancelled']);

        foreach ($salesOrder->invoices as $invoice) {
            $invoice->update(['status' => 'cancelled']);
        }

        ActivityLogger::log('cancel', 'sales_order', $salesOrder, ['customer_po_number' => $salesOrder->customer_po_number]);

        return redirect()->route('sales-finance.index')->with('status', 'Sales Order berhasil dicancel.');
    }

    public function checkStock(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        DB::transaction(function () use ($salesOrder) {
            $salesOrder->load('items');
            $stockByProduct = GudangProduct::query()
                ->select('supplier_product_id', DB::raw('SUM(qty) as total_qty'))
                ->whereIn('supplier_product_id', $salesOrder->items->pluck('product_code')->filter()->values())
                ->where('qty', '>', 0)
                ->where('status', 'stored')
                ->groupBy('supplier_product_id')
                ->pluck('total_qty', 'supplier_product_id');

            $hasPendingStock = false;

            foreach ($salesOrder->items as $item) {
                $availableStock = (float) ($stockByProduct[$item->product_code] ?? 0);
                $stockStatus = $availableStock >= (float) $item->quantity ? 'available' : 'pending';
                $hasPendingStock = $hasPendingStock || $stockStatus === 'pending';

                $item->update([
                    'available_stock' => $availableStock,
                    'stock_status' => $stockStatus,
                ]);
            }

            $salesOrder->update([
                'stock_status' => $hasPendingStock ? 'pending' : 'available',
                'order_status' => $hasPendingStock ? 'pending_stock' : 'ready_invoice',
            ]);

            if ($hasPendingStock) {
                $warehouseTask = WarehouseTask::query()->firstOrCreate(
                    ['sales_order_id' => $salesOrder->id, 'invoice_id' => null],
                    [
                        'id' => WarehouseTask::generateId(),
                        'assigned_to' => null,
                        'status' => 'waiting',
                        'note' => 'Auto task stok kosong dari Sales Order '.$salesOrder->id,
                    ]
                );

                $salesOrder->update(['warehouse_task_reference' => $warehouseTask->id]);
            }
        });

        ActivityLogger::log('stock_check', 'sales_order', $salesOrder);

        return back()->with('status', 'Pengecekan stok berhasil disimpan.');
    }

    public function generateInvoice(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $data = $request->validate([
            'invoice_type' => ['required', 'in:normal,gabungan'],
            'tax_type' => ['required', 'in:js,sjb_non_pajak,sjb_pajak'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
        ]);

        // Cek jika sudah memiliki invoice berjenis normal
        if ($salesOrder->invoices()->where('invoice_type', 'normal')->exists()) {
            return back()->with('status', 'Invoice untuk Sales Order ini sudah ada.');
        }

        if ($salesOrder->stock_status !== 'available') {
            return back()->with('status', 'Invoice belum bisa dibuat karena stok belum available.');
        }

        $invoice = DB::transaction(function () use ($data, $salesOrder) {
            $taxAmount = $data['tax_type'] === 'sjb_pajak' ? ((float) $salesOrder->subtotal * 0.11) : 0;
            $grandTotal = (float) $salesOrder->subtotal + $taxAmount;

            // FIX: Penghapusan kolom sales_order_id dari tabel invoices langsung
            $invoice = Invoice::query()->create([
                'id' => (string) Str::uuid(),
                'invoice_number' => $this->nextDocumentNumber('INV'),
                'invoice_type' => $data['invoice_type'],
                'tax_type' => $data['tax_type'],
                'faktur_number' => $this->nextFakturNumber($data['tax_type']),
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'status' => 'outstanding',
                'subtotal' => $salesOrder->subtotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'paid_amount' => 0,
                'outstanding_amount' => $grandTotal,
            ]);

            // Hubungkan via tabel pivot invoice_sales_orders
            $invoice->salesOrders()->attach($salesOrder->id);

            $salesOrder->update([
                'order_status' => 'invoiced',
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
            ]);

            $warehouseTask = WarehouseTask::query()->firstOrCreate(
                ['invoice_id' => $invoice->id],
                [
                    'id' => WarehouseTask::generateId(),
                    'sales_order_id' => $salesOrder->id,
                    'assigned_to' => null,
                    'status' => 'waiting',
                    'note' => 'Auto task dari Sales Order '.$salesOrder->id,
                ]
            );

            $salesOrder->update([
                'warehouse_task_reference' => $warehouseTask->id,
            ]);

            return $invoice;
        });

        ActivityLogger::log('create', 'invoice', $invoice);

        return redirect()->route('sales-finance.show', $salesOrder)->with('status', 'Invoice berhasil digenerate.');
    }

    public function consolidateInvoice(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:master_customers,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'tax_type' => ['required', 'in:js,sjb_non_pajak,sjb_pajak'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
        ]);

        $orders = SalesOrder::query()
            ->where('customer_id', $data['customer_id'])
            ->whereBetween('order_date', [$data['period_start'], $data['period_end']])
            ->where('stock_status', 'available')
            ->whereNotIn('order_status', ['cancelled', 'completed'])
            ->whereDoesntHave('invoices', fn ($query) => $query->where('status', '!=', 'cancelled'))
            ->with('items')
            ->get();

        if ($orders->isEmpty()) {
            return back()->with('status', 'Tidak ada Sales Order eligible untuk digabung pada periode ini.');
        }

        $invoice = DB::transaction(function () use ($data, $orders) {
            $subtotal = (float) $orders->sum(fn ($order) => (float) $order->subtotal);
            $taxAmount = $data['tax_type'] === 'sjb_pajak' ? $subtotal * 0.11 : 0;
            $grandTotal = $subtotal + $taxAmount;

            $invoice = Invoice::query()->create([
                'id' => (string) Str::uuid(),
                'invoice_number' => $this->nextDocumentNumber('INV-G'),
                'invoice_type' => 'gabungan',
                'tax_type' => $data['tax_type'],
                'faktur_number' => $this->nextFakturNumber($data['tax_type']),
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'status' => 'outstanding',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'paid_amount' => 0,
                'outstanding_amount' => $grandTotal,
            ]);

            $invoice->salesOrders()->attach($orders->pluck('id'));

            foreach ($orders as $order) {
                $order->update([
                    'order_status' => 'invoiced',
                    'tax_amount' => $taxAmount * ((float) $order->subtotal / max(1, $subtotal)),
                    'grand_total' => (float) $order->subtotal + ($taxAmount * ((float) $order->subtotal / max(1, $subtotal))),
                ]);
            }

            $firstOrder = $orders->first();
            $warehouseTask = WarehouseTask::query()->create([
                'id' => WarehouseTask::generateId(),
                'sales_order_id' => $firstOrder->id,
                'invoice_id' => $invoice->id,
                'assigned_to' => null,
                'status' => 'waiting',
                'note' => 'Auto task dari Invoice Gabungan '.$invoice->invoice_number,
            ]);

            foreach ($orders as $order) {
                $order->update(['warehouse_task_reference' => $warehouseTask->id]);
            }

            return $invoice;
        });

        ActivityLogger::log('create', 'consolidated_invoice', $invoice);

        return redirect()->route('sales-finance.show', $invoice->salesOrders()->first())->with('status', 'Invoice gabungan berhasil dibuat.');
    }

    /**
     * FIX: Implementasi Logika Partial Delivery (Pengiriman Bertahap) & Pengisian Surat Jalan Item
     */
    public function storeDeliveryNote(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'delivery_date' => ['required', 'date'],
            'status' => ['required', 'in:draft,process,delivered,cancelled'],
            'pic_sales' => ['nullable', 'string', 'max:255'],
            'pic_gudang' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            // Validasi data item yang akan dikirim pada tahapan ini
            'items' => ['required', 'array', 'min:1'],
            'items.*.sales_order_item_id' => ['required', 'exists:sales_order_items,id'],
            'items.*.qty_sent' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data, $invoice) {
            $deliveryNote = DeliveryNote::query()->create([
                'id' => (string) Str::uuid(),
                'invoice_id' => $invoice->id,
                'delivery_note_number' => $this->nextDocumentNumber('SJ'),
                'delivery_date' => $data['delivery_date'],
                'status' => $data['status'],
                'pic_sales' => $data['pic_sales'],
                'pic_gudang' => $data['pic_gudang'],
                'notes' => $data['notes'],
                'print_count' => 0,
            ]);

            $invoice->load('salesOrders.items');
            $allowedItemIds = $invoice->salesOrders->flatMap->items->pluck('id')->all();
            $hasSentItem = false;

            foreach ($data['items'] as $itemData) {
                if (! in_array((int) $itemData['sales_order_item_id'], $allowedItemIds, true)) {
                    abort(422, 'Item Surat Jalan tidak sesuai dengan invoice.');
                }

                $soItem = SalesOrderItem::findOrFail($itemData['sales_order_item_id']);
                $qtySent = (float) $itemData['qty_sent'];
                $remainingQty = max(0, (float) $soItem->quantity - (float) $soItem->delivered_qty);

                if ($qtySent <= 0) {
                    continue;
                }

                $hasSentItem = true;

                if ($qtySent > $remainingQty) {
                    abort(422, 'Qty kirim '.$soItem->product_name.' melebihi sisa barang yang belum dikirim.');
                }

                // 1. Simpan item ke delivery_note_items
                $deliveryNote->items()->create([
                    'sales_order_item_id' => $itemData['sales_order_item_id'],
                    'qty_sent' => $qtySent,
                ]);

                // 2. Akumulasikan total barang dikirim di tabel sales_order_items
                $newDeliveredQty = (float) $soItem->delivered_qty + $qtySent;

                $soItem->update([
                    'delivered_qty' => $newDeliveredQty
                ]);

                if ($data['status'] === 'delivered') {
                    $this->decrementWarehouseStock($soItem->product_code, $qtySent);
                }
            }

            if (! $hasSentItem) {
                abort(422, 'Minimal satu item Surat Jalan harus memiliki qty kirim.');
            }

            foreach ($invoice->salesOrders as $so) {
                $so->load('items');
                $isAllItemsFullyDelivered = $so->items->every(fn ($item) => (float) $item->delivered_qty >= (float) $item->quantity);
                $hasAnyDelivered = $so->items->contains(fn ($item) => (float) $item->delivered_qty > 0);
                $targetStatus = $isAllItemsFullyDelivered ? 'delivered' : ($hasAnyDelivered ? 'partial_delivery' : 'invoiced');

                $so->update(['order_status' => $targetStatus]);
            }

            if ($data['status'] === 'delivered') {
                $invoice->warehouseTask?->update(['status' => 'completed']);
            }

            ActivityLogger::log('save', 'delivery_note', $deliveryNote);
        });

        return back()->with('status', 'Surat Jalan tahap ini berhasil diterbitkan.');
    }

    /**
     * FITUR BARU: Audit Trail untuk Fitur Print & Re-print Cetak A7/Surat Jalan
     */
    public function printDeliveryNote(DeliveryNote $deliveryNote)
    {
        $deliveryNote->load(['invoice.salesOrders.items', 'items.salesOrderItem']);
        $deliveryNote->increment('print_count');

        ActivityLogger::log('print', 'delivery_note', $deliveryNote, [
            'print_count' => $deliveryNote->print_count
        ]);

        // Arahkan ke view cetak struk/kertas A7 Anda
        return view('sales-finance.print-delivery-note', compact('deliveryNote'));
    }

    public function deliveryNotePdf(DeliveryNote $deliveryNote)
    {
        $deliveryNote->load(['invoice.salesOrders', 'items.salesOrderItem']);
        $deliveryNote->increment('print_count');

        ActivityLogger::log('print', 'delivery_note', $deliveryNote, [
            'print_count' => $deliveryNote->print_count,
            'format' => 'pdf_a7',
        ]);

        return Pdf::loadView('sales-finance.pdf.delivery-note', compact('deliveryNote'))
            ->setPaper([0, 0, 209.76, 297.64], 'portrait')
            ->stream($deliveryNote->delivery_note_number.'.pdf');
    }

    public function invoicePdf(Invoice $invoice)
    {
        $invoice->load(['salesOrders.items', 'payments']);

        return Pdf::loadView('sales-finance.pdf.invoice', compact('invoice'))
            ->setPaper('a4', 'portrait')
            ->stream($invoice->invoice_number.'.pdf');
    }

    public function storeReturn(Request $request, DeliveryNote $deliveryNote): RedirectResponse
    {
        $data = $request->validate([
            'return_date' => ['required', 'date'],
            'received_date' => ['nullable', 'date', 'after_or_equal:return_date'],
            'status' => ['required', 'in:requested,approved,received,cancelled'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sales_order_item_id' => ['required', 'exists:sales_order_items,id'],
            'items.*.qty_returned' => ['required', 'numeric', 'min:0'],
            'items.*.reason' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $deliveryNote) {
            $deliveryNote->load('items');
            $allowedItemIds = $deliveryNote->items->pluck('sales_order_item_id')->all();

            $salesReturn = SalesReturn::query()->create([
                'id' => (string) Str::uuid(),
                'delivery_note_id' => $deliveryNote->id,
                'return_date' => $data['return_date'],
                'received_date' => $data['received_date'] ?? null,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $itemData) {
                $qtyReturned = (float) $itemData['qty_returned'];

                if ($qtyReturned <= 0) {
                    continue;
                }

                if (! in_array((int) $itemData['sales_order_item_id'], $allowedItemIds, true)) {
                    abort(422, 'Item retur tidak sesuai dengan Surat Jalan.');
                }

                $sentQty = (float) $deliveryNote->items
                    ->where('sales_order_item_id', $itemData['sales_order_item_id'])
                    ->sum('qty_sent');

                if ($qtyReturned > $sentQty) {
                    abort(422, 'Qty retur melebihi qty Surat Jalan.');
                }

                $salesReturn->items()->create([
                    'sales_order_item_id' => $itemData['sales_order_item_id'],
                    'qty_returned' => $qtyReturned,
                    'reason' => $itemData['reason'] ?? null,
                ]);

                if ($data['status'] === 'received') {
                    $soItem = SalesOrderItem::findOrFail($itemData['sales_order_item_id']);
                    $soItem->update([
                        'delivered_qty' => max(0, (float) $soItem->delivered_qty - $qtyReturned),
                    ]);
                }
            }

            ActivityLogger::log('create', 'sales_return', $salesReturn);
        });

        return back()->with('status', 'Retur barang berhasil disimpan.');
    }

    public function storePayment(Request $request, Invoice $invoice): RedirectResponse
    {
        $invoice->load('warehouseTask');

        if ($invoice->warehouseTask?->status !== 'completed') {
            return back()->with('status', 'Pembayaran belum bisa diproses karena barang keluar gudang belum completed.');
        }

        if ((float) $invoice->outstanding_amount <= 0) {
            return back()->with('status', 'Invoice sudah lunas.');
        }

        $data = $request->validate([
            'payment_date' => ['required', 'date'],
            'method' => ['required', 'in:cash,transfer_bank,qris,giro'],
            'receiving_account' => ['required', 'in:js,sjb'],
            'amount' => ['required', 'numeric', 'min:1', 'max:' . max(1, (float) $invoice->outstanding_amount)],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $payment = DB::transaction(function () use ($data, $invoice) {
            $payment = InvoicePayment::query()->create(array_merge($data, [
                'id' => (string) Str::uuid(),
                'invoice_id' => $invoice->id,
                'payment_number' => $this->nextDocumentNumber('PAY'),
            ]));

            $paidAmount = (float) $invoice->paid_amount + (float) $data['amount'];
            $outstandingAmount = max(0, (float) $invoice->grand_total - $paidAmount);

            $invoice->update([
                'paid_amount' => $paidAmount,
                'outstanding_amount' => $outstandingAmount,
                'status' => $outstandingAmount <= 0 ? 'paid' : 'outstanding',
            ]);

            if ($outstandingAmount <= 0) {
                foreach ($invoice->salesOrders as $so) {
                    $so->update(['order_status' => 'completed']);
                }
            }

            return $payment;
        });

        ActivityLogger::log('create', 'invoice_payment', $payment);

        return back()->with('status', 'Pembayaran invoice berhasil disimpan.');
    }

    private function validatedOrder(Request $request, ?SalesOrder $order = null): array
    {
        return $request->validate([
            'customer_id' => ['nullable', 'exists:master_customers,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_po_number' => ['required', 'string', 'max:100', Rule::unique('sales_orders', 'customer_po_number')->ignore($order?->id)],
            'po_date' => ['nullable', 'date'],
            'order_date' => ['required', 'date'],
            'sales_type' => ['required', 'in:js,sjb,nearby_store'], // Tambahan tipe SO
            'order_status' => ['required', 'in:draft,stock_check,ready_invoice,pending_stock,invoiced,partial_delivery,delivered,completed,cancelled'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_code' => ['required', 'exists:supplier_products,id'],
            'items.*.product_name' => ['nullable', 'string', 'max:255'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_1' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_2' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_3' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_4' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
    }

    private function calculateOrderTotals(array $data, array $items): array
    {
        $subtotal = collect($items)->sum(function ($item) {
            $qty = (float) $item['quantity'];
            $price = (float) $item['unit_price'];
            $d1 = (float) ($item['discount_1'] ?? 0);
            $d2 = (float) ($item['discount_2'] ?? 0);
            $d3 = (float) ($item['discount_3'] ?? 0);
            $d4 = (float) ($item['discount_4'] ?? 0);

            $netPrice = $price * (1 - $d1 / 100) * (1 - $d2 / 100) * (1 - $d3 / 100) * (1 - $d4 / 100);

            return $qty * $netPrice;
        });

        return array_merge($data, [
            'subtotal' => $subtotal,
            'tax_amount' => $data['tax_amount'] ?? 0,
            'grand_total' => $subtotal + (float) ($data['tax_amount'] ?? 0),
        ]);
    }

    private function syncItems(SalesOrder $order, array $items): void
    {
        $order->items()->delete();

        $products = SupplierProduct::query()
            ->whereIn('id', collect($items)->pluck('product_code')->filter()->values())
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $product = $products->get($item['product_code']);
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $d1 = (float) ($item['discount_1'] ?? 0);
            $d2 = (float) ($item['discount_2'] ?? 0);
            $d3 = (float) ($item['discount_3'] ?? 0);
            $d4 = (float) ($item['discount_4'] ?? 0);

            $netUnitPrice = $unitPrice * (1 - $d1 / 100) * (1 - $d2 / 100) * (1 - $d3 / 100) * (1 - $d4 / 100);
            $lineTotal = $quantity * $netUnitPrice;

            $order->items()->create([
                'product_code' => $product?->id ?? $item['product_code'],
                'product_name' => $product?->item_name ?? $item['product_name'] ?? '',
                'unit' => $product?->unit ?: ($item['unit'] ?? 'pcs'),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_1' => $d1,
                'discount_2' => $d2,
                'discount_3' => $d3,
                'discount_4' => $d4,
                'line_total' => $lineTotal,
                'stock_status' => 'unchecked',
            ]);
        }
    }

    private function customerOptions()
    {
        return Master_customer::query()->orderBy('nama_customer')->get(['id', 'nama_customer', 'termin']);
    }

    private function productOptions()
    {
        return SupplierProduct::query()
            ->withSum(['gudangProducts as available_stock' => function ($query) {
                $query->where('qty', '>', 0)
                    ->where('status', 'stored');
            }], 'qty')
            ->orderBy('item_name')
            ->get(['id', 'sku', 'part_number', 'item_name', 'unit', 'last_purchase_price']);
    }

    private function decrementWarehouseStock(?string $productId, float $qty): void
    {
        if (! $productId || $qty <= 0) {
            return;
        }

        $remaining = $qty;
        $stocks = GudangProduct::query()
            ->where('supplier_product_id', $productId)
            ->where('status', 'stored')
            ->where('qty', '>', 0)
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();

        foreach ($stocks as $stock) {
            if ($remaining <= 0) {
                break;
            }

            $deductedQty = min((float) $stock->qty, $remaining);
            $newQty = (float) $stock->qty - $deductedQty;

            $stock->update([
                'qty' => $newQty,
                'status' => $newQty <= 0 ? 'out' : $stock->status,
            ]);

            $remaining -= $deductedQty;
        }

        if ($remaining > 0) {
            abort(422, 'Stok gudang tidak cukup saat menyelesaikan Surat Jalan.');
        }
    }

    private function nextDocumentNumber(string $prefix): string
    {
        return $prefix . '-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }

    private function nextFakturNumber(string $taxType): string
    {
        $prefix = match ($taxType) {
            'sjb_non_pajak' => 'SJB-NP',
            'sjb_pajak' => 'SJB-P',
            default => 'JS',
        };

        return $this->nextDocumentNumber($prefix);
    }
}
