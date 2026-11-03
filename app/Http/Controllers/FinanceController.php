<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\SupplierPurchaseHistory;
use App\Models\SupplierPO;
use App\Models\SupplierPoPayment;
use App\Models\ActivityLog;
use App\Support\ActivityLogger;
use App\Mail\InvoiceReminderMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    /**
     * Finance Dashboard / Overview
     */
    public function index()
    {
        $totalAR = (float) Invoice::where('status', '!=', 'paid')->sum('outstanding_amount');

        // Total AP combines outstanding SupplierPO and any unpaid SupplierPurchaseHistory
        $totalAPFromPO = (float) SupplierPO::where('status', '!=', 'cancelled')
            ->where(function ($q) {
                $q->where('payment_status', '!=', 'paid')
                  ->orWhereNull('payment_status');
            })
            ->sum(DB::raw('COALESCE(outstanding_amount, total_amount)'));

        $totalAPFromHistory = (float) SupplierPurchaseHistory::where('status', '!=', 'paid')
            ->sum('total_amount');

        $totalAP = $totalAPFromPO + $totalAPFromHistory;

        $recentPayments = InvoicePayment::with('invoice')->latest()->take(5)->get();

        $recentAPPayments = SupplierPoPayment::with('supplierPo.supplier')
            ->latest('payment_date')
            ->latest('id')
            ->take(5)
            ->get();

        if ($recentAPPayments->isEmpty()) {
            $recentAPPayments = SupplierPurchaseHistory::where('status', 'paid')->latest()->take(5)->get();
        }

        return view('finance.index', compact('totalAR', 'totalAP', 'recentPayments', 'recentAPPayments'));
    }

    /**
     * Accounts Receivable (Piutang) — same as ar() but also accessible via 'finance.receivables'
     */
    public function receivables(Request $request)
    {
        return $this->ar($request);
    }

    /**
     * Accounts Receivable (Piutang)
     */
    public function ar(Request $request)
    {
        $query = Invoice::query()->with('salesOrder');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->latest()->paginate(15);

        return view('finance.ar', compact('invoices'));
    }

    /**
     * Show detail piutang / invoice
     */
    public function showPiutang(Invoice $invoice)
    {
        $invoice->load(['salesOrder', 'payments']);
        return view('finance.showPiutang', compact('invoice'));
    }

    /**
     * Show payment form for a specific invoice
     */
    public function paymentForm(Invoice $invoice)
    {
        return view('finance.paymentPiutang', compact('invoice'));
    }

    /**
     * Accounts Payable (Hutang)
     */
    public function ap(Request $request)
    {
        // Query SupplierPO by default (all active POs excluding cancelled)
        $query = SupplierPO::query()
            ->with(['supplier', 'items.supplierProduct', 'payments'])
            ->where('status', '!=', 'cancelled');

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'paid') {
                $query->where('payment_status', 'paid');
            } elseif ($status === 'partial') {
                $query->where('payment_status', 'partial');
            } elseif ($status === 'unpaid') {
                $query->where(function ($q) {
                    $q->where('payment_status', 'unpaid')
                      ->orWhereNull('payment_status');
                });
            }
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                         ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        $purchases = $query->latest('order_date')->latest('id')->paginate(15);

        // Fallback: If no SupplierPO exists in system at all, but SupplierPurchaseHistory does, use that
        if ($purchases->total() === 0 && SupplierPO::count() === 0 && SupplierPurchaseHistory::count() > 0) {
            $sphQuery = SupplierPurchaseHistory::query()->with('supplier');
            if ($request->filled('status')) {
                $sphQuery->where('status', $request->status);
            }
            $purchases = $sphQuery->latest()->paginate(15);
        }

        return view('finance.ap', compact('purchases'));
    }

    /**
     * Show detail hutang / Supplier PO
     */
    public function showHutang(SupplierPO $supplierPo)
    {
        $supplierPo->load(['supplier', 'items.supplierProduct', 'payments.user']);
        return view('finance.showHutang', compact('supplierPo'));
    }

    /**
     * Record a Payment / Pelunasan
     */
    public function storePayment(Request $request): RedirectResponse
    {
        // Support both payment_method and method fields from frontend forms
        if (!$request->has('payment_method') && $request->has('method')) {
            $request->merge(['payment_method' => $request->input('method')]);
        }

        if (!$request->has('id') && $invoiceId = $request->route('invoice')) {
            $request->merge([
                'id' => is_object($invoiceId) ? $invoiceId->id : $invoiceId,
                'type' => 'ar'
            ]);
        }

        if (!$request->has('type') && $request->has('id')) {
            $request->merge(['type' => 'ar']);
        }

        $request->validate([
            'type' => 'required|in:ar,ap',
            'id' => 'required',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'receiving_account' => 'required_if:type,ar|nullable|in:js,sjb',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'giro_number' => 'nullable|string|max:255',
            'giro_due_date' => 'nullable|date',
            'giro_status' => 'nullable|in:pending,cleared,rejected',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $type = $request->type;
        $id = $request->id;
        $amount = (float) $request->amount;

        if ($type === 'ar') {
            $invoice = Invoice::findOrFail($id);
            if ((float) $invoice->outstanding_amount <= 0) {
                return back()->with('error', 'Invoice ini sudah lunas.');
            }
        } else {
            $po = SupplierPO::find($id);
            if ($po) {
                $currentOutstanding = $po->outstanding_amount !== null
                    ? (float) $po->outstanding_amount
                    : max(0, (float) $po->total_amount - (float) ($po->paid_amount ?? 0));

                if ($po->payment_status === 'paid' || $currentOutstanding <= 0.001) {
                    return back()->with('error', 'Tagihan Supplier PO ini sudah lunas.');
                }

                if ($amount > ($currentOutstanding + 0.01)) {
                    return back()->with('error', 'Jumlah pembayaran (Rp ' . number_format($amount, 0, ',', '.') . ') melebihi sisa hutang (Rp ' . number_format($currentOutstanding, 0, ',', '.') . ').');
                }
            } else {
                $purchase = SupplierPurchaseHistory::findOrFail($id);
                if ($purchase->status === 'paid') {
                    return back()->with('error', 'Tagihan ini sudah lunas.');
                }
            }
        }

        try {
            DB::beginTransaction();

            if ($type === 'ar') {
                $paid = (float) $invoice->paid_amount + $amount;
                $outstanding = max(0, (float) $invoice->grand_total - $paid);
                $status = $outstanding <= 0 ? 'paid' : 'partial';

                $invoice->update([
                    'paid_amount' => $paid,
                    'outstanding_amount' => $outstanding,
                    'status' => $status,
                ]);

                // Update SalesOrder status if fully paid
                if ($status === 'paid' && $invoice->salesOrder) {
                    $invoice->salesOrder->update(['order_status' => 'completed']);
                }

                $method = $request->payment_method;
                if ($method === 'transfer') $method = 'transfer_bank';
                if ($method === 'cheque') $method = 'giro';

                InvoicePayment::create([
                    'invoice_id' => $invoice->id,
                    'payment_number' => 'PAY-' . now()->format('YmdHis') . '-' . random_int(100, 999),
                    'payment_date' => $request->payment_date,
                    'method' => $method,
                    'receiving_account' => $request->receiving_account,
                    'bank_name' => $method === 'giro' ? $request->bank_name : null,
                    'giro_number' => $method === 'giro' ? ($request->giro_number ?: $request->reference_number) : null,
                    'giro_due_date' => $method === 'giro' ? $request->giro_due_date : null,
                    'giro_status' => $method === 'giro' ? ($request->giro_status ?: 'pending') : null,
                    'amount' => $amount,
                    'reference_number' => $request->reference_number ?: ($method === 'giro' ? $request->giro_number : null),
                    'notes' => $request->notes,
                ]);

            } else {
                // type === 'ap' (Hutang ke Supplier)
                if ($po) {
                    $newPaid = (float) ($po->paid_amount ?? 0) + $amount;
                    $newOutstanding = max(0, (float) $po->total_amount - $newPaid);
                    $paymentStatus = $newOutstanding <= 0.01 ? 'paid' : 'partial';

                    $po->update([
                        'paid_amount' => $newPaid,
                        'outstanding_amount' => $newOutstanding,
                        'payment_status' => $paymentStatus,
                    ]);

                    $method = $request->payment_method;
                    if ($method === 'transfer') $method = 'transfer_bank';
                    if ($method === 'cheque') $method = 'giro';
                    if ($method === 'cash') $method = 'cash';

                    $apPayment = SupplierPoPayment::create([
                        'supplier_po_id' => $po->id,
                        'payment_number' => 'PAY-AP-' . now()->format('YmdHis') . '-' . random_int(100, 999),
                        'payment_date' => $request->payment_date,
                        'amount' => $amount,
                        'payment_method' => $method,
                        'bank_name' => $request->bank_name,
                        'bank_account_number' => $request->bank_account_number,
                        'reference_number' => $request->reference_number ?: $request->giro_number,
                        'notes' => $request->notes,
                        'created_by' => auth()->id(),
                    ]);

                    ActivityLogger::log('payment', 'supplier_po', $po, [
                        'payment_id' => $apPayment->id,
                        'amount' => $amount,
                        'payment_method' => $method,
                        'outstanding_amount' => $newOutstanding,
                        'payment_status' => $paymentStatus,
                    ]);
                } else {
                    $purchase->update([
                        'status' => 'paid',
                        'notes' => trim(($purchase->notes ?? '') . " | Lunas via {$request->payment_method} tgl {$request->payment_date} (" . number_format($amount, 0, ',', '.') . ")"),
                    ]);

                    ActivityLogger::log('payment', 'supplier_purchase', $purchase, [
                        'amount' => $amount,
                        'status' => 'paid',
                    ]);
                }
            }

            DB::commit();
            return back()->with('status', 'Pelunasan berhasil disimpan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses pelunasan: ' . $e->getMessage());
        }
    }

    /**
     * Financial Reports
     */
    public function report()
    {
        $driver = DB::connection()->getDriverName();
        $invoiceMonthExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m', invoice_date)"
            : "DATE_FORMAT(invoice_date, '%Y-%m')";
        $poMonthExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m', order_date)"
            : "DATE_FORMAT(order_date, '%Y-%m')";

        // Monthly AR report summaries
        $arMonthly = Invoice::selectRaw("{$invoiceMonthExpr} as month, SUM(grand_total) as total_billing, SUM(paid_amount) as total_collected")
            ->whereNotNull('invoice_date')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get();

        // Monthly AP report summaries from SupplierPO
        $apMonthly = SupplierPO::selectRaw("{$poMonthExpr} as month, SUM(total_amount) as total_purchase")
            ->whereNotNull('order_date')
            ->where('status', '!=', 'cancelled')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get();

        if ($apMonthly->isEmpty()) {
            $purchaseMonthExpr = $driver === 'sqlite'
                ? "strftime('%Y-%m', purchase_date)"
                : "DATE_FORMAT(purchase_date, '%Y-%m')";

            $apMonthly = SupplierPurchaseHistory::selectRaw("{$purchaseMonthExpr} as month, SUM(total_amount) as total_purchase")
                ->whereNotNull('purchase_date')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->get();
        }

        return view('finance.report', compact('arMonthly', 'apMonthly'));
    }

    /**
     * Send Invoice / Billing Reminder Email to Customer
     */
    public function sendInvoiceEmail(Request $request, Invoice $invoice): RedirectResponse
    {
        $invoice->loadMissing(['salesOrder.customer', 'salesOrders.customer']);

        $customerEmail = null;
        if ($invoice->salesOrder && $invoice->salesOrder->customer) {
            $customerEmail = $invoice->salesOrder->customer->email;
        } elseif ($invoice->salesOrders->isNotEmpty() && $invoice->salesOrders->first()->customer) {
            $customerEmail = $invoice->salesOrders->first()->customer->email;
        }

        $recipientEmail = $request->input('email') ?: $customerEmail;

        if (!$recipientEmail) {
            return back()->with('error', 'Alamat email customer tidak ditemukan. Mohon isi alamat email tujuan.');
        }

        if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return back()->with('error', "Format email '{$recipientEmail}' tidak valid.");
        }

        $customMessage = $request->input('message');

        try {
            Mail::to($recipientEmail)->send(new InvoiceReminderMail($invoice, $customMessage));

            ActivityLogger::log('send_email', 'invoice', $invoice, [
                'recipient_email' => $recipientEmail,
                'invoice_number' => $invoice->invoice_number,
                'outstanding_amount' => $invoice->outstanding_amount,
            ]);

            return back()->with('status', "Tagihan invoice {$invoice->invoice_number} berhasil dikirim ke {$recipientEmail}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengirim email: ' . $e->getMessage());
        }
    }
}

