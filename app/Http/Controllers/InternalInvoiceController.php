<?php

namespace App\Http\Controllers;

use App\Models\InternalInvoice;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class InternalInvoiceController extends Controller
{
    public function index()
    {
        $internalInvoices = InternalInvoice::latest()->paginate(15);
        return view('internal-invoice.index', compact('internalInvoices'));
    }

    public function create()
    {
        return view('internal-invoice.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'from_store' => 'required|in:js,sjb',
            'to_store' => 'required|in:js,sjb|different:from_store',
            'invoice_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
        ]);

        InternalInvoice::create([
            'id' => InternalInvoice::generateId() ?? ('INV-INT-' . now()->format('YmdHis')),
            'from_store' => $request->from_store,
            'to_store' => $request->to_store,
            'invoice_date' => $request->invoice_date,
            'amount' => $request->amount,
            'description' => $request->description,
        ]);

        return redirect()
            ->route('internal-invoices.index')
            ->with('status', 'Invoice Internal berhasil dibuat.');
    }

    public function destroy(InternalInvoice $internalInvoice): RedirectResponse
    {
        $internalInvoice->delete();
        return redirect()
            ->route('internal-invoices.index')
            ->with('status', 'Invoice Internal berhasil dihapus.');
    }
}
