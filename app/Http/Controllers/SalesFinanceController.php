<?php

namespace App\Http\Controllers;

use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\GudangProduct;
use App\Models\Master_customer;
use App\Models\SalesOrder;
use App\Models\SupplierProduct;
use App\Models\WarehouseTask;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SalesFinanceController extends Controller
{
    public function index()
    {
        $orders = SalesOrder::query()
            ->with(['invoice.deliveryNote', 'invoice.payments', 'invoice.warehouseTask'])
            ->latest()
            ->get();

        return view('sales-finance.index', compact('orders'));
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
        $salesOrder->load(['customer', 'items', 'invoice.deliveryNote', 'invoice.payments', 'invoice.warehouseTask']);

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
        ActivityLogger::log('delete', 'sales_order', $salesOrder, ['customer_po_number' => $salesOrder->customer_po_number]);
        $salesOrder->delete();

        return redirect()->route('sales-finance.index')->with('status', 'Sales Order berhasil dihapus.');
    }

    public function checkStock(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        DB::transaction(function () use ($salesOrder) {
            $salesOrder->load('items');
            $stockByProduct = GudangProduct::query()
                ->select('supplier_product_id', DB::raw('SUM(qty) as total_qty'))
                ->whereIn('supplier_product_id', $salesOrder->items->pluck('product_code')->filter()->values())
                ->where('qty', '>', 0)
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
                'order_status' => $hasPendingStock ? 'pending_stock' : 'ready_to_invoice',
            ]);
        });

        ActivityLogger::log('stock_check', 'sales_order', $salesOrder);

        return back()->with('status', 'Pengecekan stok berhasil disimpan.');
    }

    public function generateInvoice(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $data = $request->validate([
            'tax_type' => ['required', 'in:js,sjb_non_pajak,sjb_pajak'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
        ]);

        if ($salesOrder->invoice) {
            return back()->with('status', 'Invoice untuk Sales Order ini sudah ada.');
        }



        $invoice = DB::transaction(function () use ($data, $salesOrder) {
            $taxAmount = $data['tax_type'] === 'sjb_pajak' ? ((float) $salesOrder->subtotal * 0.11) : 0;
            $grandTotal = (float) $salesOrder->subtotal + $taxAmount;

            $invoice = Invoice::query()->create([
                'sales_order_id' => $salesOrder->id,
                'invoice_number' => $this->nextDocumentNumber('INV'),
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

    public function generateProformaInvoice(SalesOrder $salesOrder): RedirectResponse
    {
        if ($salesOrder->proformaInvoice) {
            return back()->with('status', 'Proforma Invoice untuk Sales Order ini sudah ada.');
        }

        $pi = DB::transaction(function () use ($salesOrder) {
            $taxAmount = $salesOrder->toko === 'sjb' ? ((float) $salesOrder->subtotal * 0.11) : 0;
            $grandTotal = (float) $salesOrder->subtotal + $taxAmount;

            $pi = \App\Models\ProformaInvoice::query()->create([
                'sales_order_id' => $salesOrder->id,
                'pi_number' => $this->nextDocumentNumber('PI'),
                'pi_date' => now(),
                'status' => 'printed',
                'subtotal' => $salesOrder->subtotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
            ]);

            return $pi;
        });

        ActivityLogger::log('create', 'proforma_invoice', $pi);

        return back()->with('status', 'Proforma Invoice berhasil digenerate.');
    }

    public function printProformaInvoice(SalesOrder $salesOrder)
    {
        $salesOrder->load(['customer', 'items', 'proformaInvoice']);

        if (!$salesOrder->proformaInvoice) {
            return back()->with('error', 'Proforma Invoice belum digenerate.');
        }

        if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
                'proforma-invoice.print',
                compact('salesOrder')
            );

            return $pdf->download(
                'ProformaInvoice-' . $salesOrder->proformaInvoice->pi_number . '.pdf'
            );
        }

        return view('proforma-invoice.print', compact('salesOrder'));
    }

    public function mergeInvoices(Request $request): RedirectResponse
    {
        $request->validate([
            'sales_order_ids' => ['required', 'array', 'min:1'],
            'sales_order_ids.*' => ['required', 'exists:sales_orders,id'],
            'tax_type' => ['required', 'in:js,sjb_non_pajak,sjb_pajak'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
        ]);

        $salesOrders = SalesOrder::whereIn('id', $request->sales_order_ids)->get();

        // Validate all belong to same customer
        $customerIds = $salesOrders->pluck('customer_id')->unique();
        if ($customerIds->count() > 1) {
            return back()->with('error', 'Semua Sales Order harus milik customer yang sama.');
        }

        try {
            $invoice = DB::transaction(function () use ($request, $salesOrders) {
                $subtotal = 0;
                foreach ($salesOrders as $so) {
                    $subtotal += (float) $so->subtotal;
                }

                $taxAmount = $request->tax_type === 'sjb_pajak' ? ($subtotal * 0.11) : 0;
                $grandTotal = $subtotal + $taxAmount;

                // Create combined Invoice
                $invoice = Invoice::query()->create([
                    'sales_order_id' => $salesOrders->first()->id, // fallback reference
                    'invoice_number' => $this->nextDocumentNumber('INV-COMB'),
                    'tax_type' => $request->tax_type,
                    'faktur_number' => $this->nextFakturNumber($request->tax_type),
                    'invoice_date' => $request->invoice_date,
                    'due_date' => $request->due_date ?? null,
                    'status' => 'outstanding',
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'grand_total' => $grandTotal,
                    'paid_amount' => 0,
                    'outstanding_amount' => $grandTotal,
                ]);

                foreach ($salesOrders as $so) {
                    $so->update([
                        'invoice_id' => $invoice->id,
                        'order_status' => 'invoiced',
                        'tax_amount' => $so->subtotal * ($request->tax_type === 'sjb_pajak' ? 0.11 : 0),
                        'grand_total' => $so->subtotal * (1 + ($request->tax_type === 'sjb_pajak' ? 0.11 : 0)),
                    ]);

                    // Generate warehouse task for each sales order in this invoice
                    WarehouseTask::query()->firstOrCreate(
                        ['invoice_id' => $invoice->id, 'sales_order_id' => $so->id],
                        [
                            'id' => WarehouseTask::generateId(),
                            'sales_order_id' => $so->id,
                            'assigned_to' => null,
                            'status' => 'waiting',
                            'note' => 'Combined invoice task dari Sales Order ' . $so->id,
                        ]
                    );
                }

                return $invoice;
            });

            ActivityLogger::log('create', 'invoice', $invoice);

            return back()->with('status', 'Berhasil menggabungkan ' . $salesOrders->count() . ' Sales Order ke dalam Invoice: ' . $invoice->invoice_number);

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menggabungkan Sales Order: ' . $e->getMessage());
        }
    }

    public function storeDeliveryNote(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'delivery_date' => ['required', 'date'],
            'status' => ['required', 'in:draft,process,delivered,cancelled'],
            'pic_sales' => ['nullable', 'string', 'max:255'],
            'pic_gudang' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $deliveryNote = DeliveryNote::query()->updateOrCreate(
            ['invoice_id' => $invoice->id],
            array_merge($data, [
                'delivery_note_number' => $invoice->deliveryNote?->delivery_note_number ?? $this->nextDocumentNumber('SJ'),
            ])
        );

        if ($data['status'] === 'delivered') {
            $invoice->salesOrder()->update(['order_status' => 'delivered']);
        }

        ActivityLogger::log('save', 'delivery_note', $deliveryNote);

        return back()->with('status', 'Surat Jalan berhasil disimpan.');
    }

    public function storePayment(Request $request, Invoice $invoice): RedirectResponse
    {
        $invoice->load('warehouseTask');



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
                $invoice->salesOrder()->update(['order_status' => 'completed']);
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
            'toko' => ['required', 'in:js,sjb'],
            'jenis_invoice' => ['required', 'in:normal,gabungan'],
            'po_date' => ['nullable', 'date'],
            'order_date' => ['required', 'date'],
            'order_status' => ['required', 'in:draft,stock_check,ready_to_invoice,pending_stock,invoiced,delivered,completed,cancelled'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_code' => ['required', 'exists:supplier_products,id'],
            'items.*.product_name' => ['nullable', 'string', 'max:255'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
    }

    private function calculateOrderTotals(array $data, array $items): array
    {
        $subtotal = collect($items)->sum(function ($item) {
            $qty = (float) $item['quantity'];
            $price = (float) $item['unit_price'];
            $discount = (float) ($item['discount'] ?? 0);
            return $qty * $price * (1 - $discount / 100);
        });

        return array_merge($data, [
            'subtotal' => $subtotal,
            'tax_amount' => $data['tax_amount'] ?? 0,
            'grand_total' => $subtotal + (float) ($data['tax_amount'] ?? 0),
        ]);
    }

    private function syncItems(SalesOrder $order, array $items): void
    {
        $products = SupplierProduct::query()
            ->whereIn('id', collect($items)->pluck('product_code')->filter()->values())
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $product = $products->get($item['product_code']);
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $discount = (float) ($item['discount'] ?? 0);
            $lineTotal = $quantity * $unitPrice * (1 - $discount / 100);

            $order->items()->create([
                'product_code' => $product->id,
                'product_name' => $product->item_name,
                'unit' => $product->unit ?: $item['unit'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount' => $discount,
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
            ->where('status', 'active')
            ->orderBy('item_name')
            ->get(['id', 'sku', 'part_number', 'item_name', 'unit', 'last_purchase_price'])
            ->map(function ($product) {
                // Find first gudang product to get custom price and discount
                $gProduct = GudangProduct::where('supplier_product_id', $product->id)
                    ->where('qty', '>', 0)
                    ->orderBy('id', 'desc')
                    ->first();

                if (!$gProduct) {
                    $gProduct = GudangProduct::where('supplier_product_id', $product->id)
                        ->orderBy('id', 'desc')
                        ->first();
                }

                $product->custom_price = $gProduct && floatval($gProduct->price) > 0 ? floatval($gProduct->price) : floatval($product->last_purchase_price);
                $product->discount_percent = $gProduct ? floatval($gProduct->discount) : 0;
                
                return $product;
            });
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
