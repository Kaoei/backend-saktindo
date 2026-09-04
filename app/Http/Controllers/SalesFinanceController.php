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

        $customers = Master_customer::orderBy('nama_customer')->get();

        return view('sales-finance.index', compact('orders', 'customers'));
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

    public function preOrders(Request $request)
    {
        $query = SalesOrder::query()
            ->where(function ($q) {
                $q->where('is_pre_order', true)
                  ->orWhere('order_status', 'pending_stock');
            })
            ->with(['customer', 'proformaInvoice', 'items', 'invoice']);

        if ($request->filled('dp_status')) {
            $query->where('dp_status', $request->dp_status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_po_number', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        $preOrders = $query->latest('order_date')->paginate(15);
        $customers = Master_customer::orderBy('nama_customer')->get(['id', 'nama_customer']);

        $allPreOrders = SalesOrder::query()
            ->where(function ($q) {
                $q->where('is_pre_order', true)
                  ->orWhere('order_status', 'pending_stock');
            })->get();

        $totalPreOrders = $allPreOrders->count();
        $totalPendingDp = $allPreOrders->where('dp_status', '!=', 'paid')->count();
        $totalDpCollected = $allPreOrders->sum('dp_paid');
        $upcomingEtaCount = $allPreOrders->filter(function ($so) {
            return $so->pre_order_eta && $so->pre_order_eta->isBetween(now(), now()->addDays(7));
        })->count();

        return view('sales-finance.pre-orders', compact(
            'preOrders',
            'customers',
            'totalPreOrders',
            'totalPendingDp',
            'totalDpCollected',
            'upcomingEtaCount'
        ));
    }

    public function generateProformaInvoice(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $subtotal = (float) $salesOrder->subtotal;
        $taxAmount = (float) ($salesOrder->tax_amount ?? 0);
        $grandTotal = (float) ($salesOrder->grand_total ?? ($subtotal + $taxAmount));

        $pi = ProformaInvoice::updateOrCreate(
            ['sales_order_id' => $salesOrder->id],
            [
                'pi_number' => $salesOrder->proformaInvoice?->pi_number ?? 'PI-' . now()->format('Ymd') . '-' . random_int(100, 999),
                'pi_date' => now(),
                'status' => $salesOrder->dp_status === 'paid' ? 'paid' : ($salesOrder->dp_paid > 0 ? 'partial' : 'draft'),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
            ]
        );

        ActivityLogger::log('create', 'proforma_invoice', $pi);

        return back()->with('status', 'Proforma Invoice (' . $pi->pi_number . ') berhasil digenerate.');
    }

    public function printProformaInvoice(SalesOrder $salesOrder)
    {
        $salesOrder->load(['proformaInvoice', 'items', 'customer']);

        if (!$salesOrder->proformaInvoice) {
            $subtotal = (float) $salesOrder->subtotal;
            $taxAmount = (float) ($salesOrder->tax_amount ?? 0);
            $grandTotal = (float) ($salesOrder->grand_total ?? ($subtotal + $taxAmount));

            ProformaInvoice::create([
                'sales_order_id' => $salesOrder->id,
                'pi_number' => 'PI-' . now()->format('Ymd') . '-' . random_int(100, 999),
                'pi_date' => now(),
                'status' => $salesOrder->dp_status === 'paid' ? 'paid' : ($salesOrder->dp_paid > 0 ? 'partial' : 'draft'),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
            ]);

            $salesOrder->load('proformaInvoice');
        }

        return view('proforma-invoice.print', compact('salesOrder'));
    }

    public function recordDpPayment(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $request->validate([
            'dp_paid_amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $amount = (float) $request->dp_paid_amount;
        $newDpPaid = (float) $salesOrder->dp_paid + $amount;
        $targetDp = (float) ($salesOrder->dp_amount > 0 ? $salesOrder->dp_amount : $salesOrder->grand_total);
        $status = $newDpPaid >= $targetDp ? 'paid' : 'partial';

        $salesOrder->update([
            'dp_paid' => $newDpPaid,
            'dp_status' => $status,
            'pre_order_notes' => trim(($salesOrder->pre_order_notes ?? '') . "\nDP diterima: Rp " . number_format($amount, 0, ',', '.') . " pada " . date('d M Y', strtotime($request->payment_date)) . ($request->notes ? ' (' . $request->notes . ')' : '')),
        ]);

        if ($salesOrder->proformaInvoice) {
            $salesOrder->proformaInvoice->update(['status' => $status]);
        }

        ActivityLogger::log('dp_payment', 'sales_order', $salesOrder, ['amount' => $amount]);

        return back()->with('status', 'Pembayaran DP sebesar Rp ' . number_format($amount, 0, ',', '.') . ' berhasil dicatat.');
    }

    public function checkFaktur(Request $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->faktur_checked) {
            return back()->with('status', 'Faktur sudah pernah dicek dan tidak dapat diubah kembali.');
        }

        $invoice->update([
            'faktur_checked' => true,
            'faktur_checked_at' => now(),
        ]);

        ActivityLogger::log('check_faktur', 'invoice', $invoice);

        return back()->with('status', 'Faktur ' . $invoice->faktur_number . ' berhasil ditandai sudah dicek.');
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
            'bank_name' => ['nullable', 'string', 'max:255'],
            'giro_number' => ['nullable', 'string', 'max:255'],
            'giro_due_date' => ['nullable', 'date'],
            'giro_status' => ['nullable', 'in:pending,cleared,rejected'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($data['method'] === 'giro' && empty($data['giro_status'])) {
            $data['giro_status'] = 'pending';
        }
        if ($data['method'] === 'giro' && !empty($data['giro_number']) && empty($data['reference_number'])) {
            $data['reference_number'] = $data['giro_number'];
        }

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
        $data = $request->validate([
            'customer_id' => ['nullable', 'exists:master_customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_po_number' => ['nullable', 'string', 'max:100', Rule::unique('sales_orders', 'customer_po_number')->ignore($order?->id)],
            'po_date' => ['nullable', 'date'],
            'order_date' => ['required', 'date'],
            'sales_type' => ['nullable', 'in:js,sjb'],
            'order_status' => ['required', 'in:draft,stock_check,ready_to_invoice,pending_stock,invoiced,delivered,completed,cancelled'],
            'is_pre_order' => ['nullable'],
            'pre_order_eta' => ['nullable', 'date'],
            'dp_amount' => ['nullable', 'numeric', 'min:0'],
            'pre_order_notes' => ['nullable', 'string'],
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

        $data['is_pre_order'] = $request->boolean('is_pre_order');
        $data['pre_order_eta'] = $request->input('pre_order_eta') ?: null;
        $data['dp_amount'] = (float) ($request->input('dp_amount') ?? 0);
        $data['pre_order_notes'] = $request->input('pre_order_notes') ?: null;

        return $data;
    }

    private function calculateOrderTotals(array $data, array $items): array
    {
        $subtotal = collect($items)->sum(function ($item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $net = $price
                * (1 - (float) ($item['discount_1'] ?? 0) / 100)
                * (1 - (float) ($item['discount_2'] ?? 0) / 100)
                * (1 - (float) ($item['discount_3'] ?? 0) / 100)
                * (1 - (float) ($item['discount_4'] ?? 0) / 100);
            return $qty * $net;
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
            $d1 = (float) ($item['discount_1'] ?? 0);
            $d2 = (float) ($item['discount_2'] ?? 0);
            $d3 = (float) ($item['discount_3'] ?? 0);
            $d4 = (float) ($item['discount_4'] ?? 0);

            $netUnitPrice = $unitPrice
                * (1 - $d1 / 100)
                * (1 - $d2 / 100)
                * (1 - $d3 / 100)
                * (1 - $d4 / 100);

            $order->items()->create([
                'product_code' => $product->id,
                'product_name' => $product->item_name,
                'unit' => $product->unit ?: $item['unit'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_1' => $d1,
                'discount_2' => $d2,
                'discount_3' => $d3,
                'discount_4' => $d4,
                'line_total' => round($quantity * $netUnitPrice, 2),
                'stock_status' => 'unchecked',
            ]);
        }
    }

    private function customerOptions()
    {
        return Master_customer::query()
            ->orderBy('nama_customer')
            ->get(['id', 'nama_customer', 'termin'])
            ->map(function ($c) {
                $c->unpaid_count = $c->unpaid_invoices_count;
                $c->is_blocked = $c->is_blocked_for_so;
                return $c;
            });
    }

    private function productOptions()
    {
        return SupplierProduct::query()
            ->where('status', 'active')
            ->with(['gudangProducts.rack'])
            ->orderBy('item_name')
            ->get(['id', 'sku', 'part_number', 'item_name', 'unit', 'last_purchase_price'])
            ->map(function ($product) {
                $gProducts = $product->gudangProducts;
                
                $availableStock = $gProducts->sum('qty');
                $locations = [];
                
                foreach ($gProducts as $gp) {
                    if ($gp->qty > 0) {
                        $gType = strtoupper($gp->gudang_type ?: 'JS');
                        $rakKode = $gp->rack->rak_kode ?? $gp->rack_id ?? '-';
                        $unitStr = $product->unit ?: 'pcs';
                        $locations[] = "{$gType}: {$rakKode} ({$gp->qty} {$unitStr})";
                    }
                }

                if (empty($locations)) {
                    $firstGp = $gProducts->first();
                    if ($firstGp) {
                        $gType = strtoupper($firstGp->gudang_type ?: 'JS');
                        $rakKode = $firstGp->rack->rak_kode ?? $firstGp->rack_id ?? '-';
                        $unitStr = $product->unit ?: 'pcs';
                        $locations[] = "{$gType}: {$rakKode} (0 {$unitStr})";
                    } else {
                        $locations[] = "- Belum di Rak -";
                    }
                }

                $product->available_stock = $availableStock;
                $product->warehouse_location = implode(' | ', $locations);

                $gProduct = $gProducts->where('price', '>', 0)->sortByDesc('qty')->first()
                    ?: ($gProducts->firstWhere('qty', '>', 0) ?: $gProducts->first());

                $price = $gProduct && floatval($gProduct->price) > 0 ? floatval($gProduct->price) : floatval($product->last_purchase_price);

                if ($price <= 0) {
                    $masterProd = \App\Models\Product::where(function($q) use ($product) {
                        if ($product->sku) $q->where('seller_sku', $product->sku);
                        if ($product->item_name) $q->orWhere('product_name', $product->item_name);
                    })->first();
                    if ($masterProd && floatval($masterProd->price) > 0) {
                        $price = floatval($masterProd->price);
                    }
                }

                $product->custom_price = $price;
                $product->discount_percent = $gProduct ? floatval($gProduct->discount) : 0;
                $product->unit = $product->unit ?: 'pcs';
                
                return $product;
            });
    }

    private function nextDocumentNumber(string $prefix): string
    {
        return $prefix . '-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }

    private function nextFakturNumber(string $taxType): string
    {
        $year = now()->format('y');  // 2-digit year e.g. '26'
        $month = now()->format('m');

        // Count existing invoices this month for sequence
        $prefix = match ($taxType) {
            'sjb_non_pajak', 'sjb_pajak' => 'SJB',
            default => 'JS',
        };

        $count = \App\Models\Invoice::whereYear('invoice_date', now()->year)
            ->whereMonth('invoice_date', now()->month)
            ->where('faktur_number', 'like', "{$prefix}/{$year}/{$month}/%")
            ->count();

        $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        return "{$prefix}/{$year}/{$month}/{$sequence}";
    }
}
