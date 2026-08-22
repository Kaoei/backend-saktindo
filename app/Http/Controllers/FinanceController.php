<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\SupplierPurchaseHistory;
use App\Models\ActivityLog;
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
        $totalAR = Invoice::where('status', '!=', 'paid')->sum('outstanding_amount');
        $totalAP = SupplierPurchaseHistory::where('status', '!=', 'paid')->sum('total_amount');
        
        $recentPayments = InvoicePayment::with('invoice')->latest()->take(5)->get();
        $recentAPPayments = SupplierPurchaseHistory::where('status', 'paid')->latest()->take(5)->get();

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
        $query = SupplierPurchaseHistory::query()->with('supplier');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $purchases = $query->latest()->paginate(15);

        return view('finance.ap', compact('purchases'));
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
            'giro_number' => 'nullable|string|max:255',
            'giro_due_date' => 'nullable|date',
            'giro_status' => 'nullable|in:pending,cleared,rejected',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $type = $request->type;
        $id = $request->id;
        $amount = (float) $request->amount;

        try {
            DB::beginTransaction();

            if ($type === 'ar') {
                $invoice = Invoice::findOrFail($id);

                if ((float) $invoice->outstanding_amount <= 0) {
                    return back()->with('error', 'Invoice ini sudah lunas.');
                }

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
                $purchase = SupplierPurchaseHistory::findOrFail($id);

                if ($purchase->status === 'paid') {
                    return back()->with('error', 'Tagihan ini sudah lunas.');
                }

                $purchase->update([
                    'status' => 'paid',
                    'notes' => trim(($purchase->notes ?? '') . " | Lunas via {$request->payment_method} tgl {$request->payment_date} (" . number_format($amount, 0, ',', '.') . ")"),
                ]);
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
        // Monthly AR/AP report summaries
        $arMonthly = Invoice::selectRaw("DATE_FORMAT(invoice_date, '%Y-%m') as month, SUM(grand_total) as total_billing, SUM(paid_amount) as total_collected")
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get();

        $apMonthly = SupplierPurchaseHistory::selectRaw("DATE_FORMAT(purchase_date, '%Y-%m') as month, SUM(total_amount) as total_purchase")
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get();

        return view('finance.report', compact('arMonthly', 'apMonthly'));
    }
}
