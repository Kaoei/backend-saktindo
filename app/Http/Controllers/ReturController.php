<?php

namespace App\Http\Controllers;

use App\Models\Retur;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ReturController extends Controller
{
    public function index()
    {
        $returs = Retur::with('salesOrder')->latest()->paginate(15);
        return view('retur.index', compact('returs'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'sales_order_id' => 'required|exists:sales_orders,id',
            'return_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $salesOrder = SalesOrder::findOrFail($request->sales_order_id);

        if ($salesOrder->retur) {
            return back()->with('error', 'Sales Order ini sudah memiliki retur.');
        }

        Retur::create([
            'id' => Retur::generateId() ?? ('RET-' . now()->format('YmdHis')),
            'sales_order_id' => $request->sales_order_id,
            'return_date' => $request->return_date,
            'status' => 'pending',
            'notes' => $request->notes,
        ]);

        return back()->with('status', 'Retur barang berhasil dicatat.');
    }

    public function update(Request $request, Retur $retur): RedirectResponse
    {
        $request->validate([
            'received_date' => 'nullable|date',
            'status' => 'required|in:pending,received,cancelled',
            'notes' => 'nullable|string',
        ]);

        $retur->update([
            'received_date' => $request->received_date,
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

        return back()->with('status', 'Data retur barang berhasil diperbarui.');
    }

    public function destroy(Retur $retur): RedirectResponse
    {
        $retur->delete();
        return back()->with('status', 'Data retur barang berhasil dihapus.');
    }
}
