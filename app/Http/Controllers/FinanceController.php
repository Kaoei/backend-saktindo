<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FinanceController extends Controller
{
    public function index()
    {
        $totalReceivable = Invoice::sum('outstanding_amount');
        $totalPaid = InvoicePayment::sum('amount');
        $totalInvoice = Invoice::sum('grand_total');

        $outstandingCount = Invoice::where('status', 'outstanding')->count();
        $paidCount = Invoice::where('status', 'paid')->count();

        $receivables = Invoice::with(['salesOrder', 'payments'])
            ->whereIn('status', ['outstanding', 'paid'])
            ->latest()
            ->get();

        $payables = collect();

        return view('finance.index', compact(
            'totalReceivable',
            'totalPaid',
            'totalInvoice',
            'outstandingCount',
            'paidCount',
            'receivables',
            'payables'
        ));
    }

    public function receivables()
    {
    $invoices = Invoice::with(['salesOrder', 'payments'])
        ->whereIn('status', ['outstanding', 'paid'])
        ->latest()
        ->get();

    $outstandingCount = Invoice::where('status', 'outstanding')->count();

    $totalOutstanding = Invoice::where('status', 'outstanding')
        ->sum('outstanding_amount');

    $overdueCount = Invoice::where('status', 'outstanding')
        ->whereDate('due_date', '<', now())
        ->count();

    $overdueAmount = Invoice::where('status', 'outstanding')
        ->whereDate('due_date', '<', now())
        ->sum('outstanding_amount');

    $paymentCount = InvoicePayment::count();

    $totalPaid = InvoicePayment::sum('amount');

    $paidCount = Invoice::where('status', 'paid')->count();

    $paidAmount = Invoice::where('status', 'paid')
        ->sum('grand_total');

    return view('finance.receivables', compact(
        'invoices',
        'outstandingCount',
        'totalOutstanding',
        'overdueCount',
        'overdueAmount',
        'paymentCount',
        'totalPaid',
        'paidCount',
        'paidAmount'
    ));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['salesOrder', 'payments']);

        return view('finance.showPiutang', compact('invoice'));
    }

    public function paymentForm(Invoice $invoice)
    {
        return view('finance.paymentPiutang', compact('invoice'));
    }

    public function storePayment(Request $request, Invoice $invoice)
    {
        $request->validate([
            'payment_date' => 'required|date',
            'method' => 'required|in:cash,transfer_bank,qris,giro',
            'receiving_account' => 'required|in:js,sjb',
            'amount' => 'required|numeric|min:1',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($request->amount > $invoice->outstanding_amount) {
            return back()->with('error', 'Nominal pembayaran melebihi sisa piutang.');
        }

        InvoicePayment::create([
            'id' => 'PAY-' . strtoupper(Str::random(8)),
            'invoice_id' => $invoice->id,
            'payment_number' => 'PAY-' . now()->format('YmdHis'),
            'payment_date' => $request->payment_date,
            'method' => $request->method,
            'receiving_account' => $request->receiving_account,
            'amount' => $request->amount,
            'reference_number' => $request->reference_number,
            'notes' => $request->notes,
        ]);

        $paidAmount = $invoice->payments()->sum('amount');
        $outstandingAmount = $invoice->grand_total - $paidAmount;

        $invoice->update([
            'paid_amount' => $paidAmount,
            'outstanding_amount' => max($outstandingAmount, 0),
            'status' => $outstandingAmount <= 0 ? 'paid' : 'outstanding',
        ]);

        return redirect()
            ->route('finance.receivables')
            ->with('success', 'Pembayaran berhasil disimpan.');
    }

    public function reports()
    {
        $totalSales = Invoice::sum('grand_total');
        $totalPaid = InvoicePayment::sum('amount');
        $totalReceivable = Invoice::sum('outstanding_amount');

        $paidInvoices = Invoice::where('status', 'paid')->count();
        $outstandingInvoices = Invoice::where('status', 'outstanding')->count();

        return view('finance.reports', compact(
            'totalSales',
            'totalPaid',
            'totalReceivable',
            'paidInvoices',
            'outstandingInvoices'
        ));
    }
}