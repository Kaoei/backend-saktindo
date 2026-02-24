<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::query()
            ->with(['proposal.client', 'creator'])
            ->orderByDesc('id')
            ->get();

        return view('invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['proposal.client', 'proposal.creator', 'creator']);

        return view('invoices.show', compact('invoice'));
    }

    public function markPaid(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        if ($invoice->status === Invoice::STATUS_PAID) {
            return redirect()->route('invoices.show', $invoice)->with('error', 'Invoice sudah berstatus Paid.');
        }

        $invoice->status  = Invoice::STATUS_PAID;
        $invoice->paid_at = now();

        if (! empty($data['notes'])) {
            $invoice->notes = $data['notes'];
        }

        $invoice->save();

        return redirect()->route('invoices.show', $invoice)->with('status', 'Invoice berhasil ditandai Paid.');
    }

    public function receipt(Invoice $invoice)
    {
        $invoice->load(['proposal.client', 'proposal.creator', 'creator']);

        return view('invoices.receipt', compact('invoice'));
    }
}
