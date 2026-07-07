<?php

namespace App\Http\Controllers;

use App\Models\WarehouseTask;
use App\Models\SalesOrder;
use App\Models\Invoice;
use Illuminate\Http\Request;

class WarehouseTaskController extends Controller
{
    public function index()
    {
        $warehouseTasks = WarehouseTask::with(['salesOrder', 'invoice'])
            ->latest()
            ->get();

        $totalTask = $warehouseTasks->count();
        $waitingTask = $warehouseTasks->where('status', 'waiting')->count();
        $processTask = $warehouseTasks->where('status', 'process')->count();
        $completedTask = $warehouseTasks->where('status', 'completed')->count();

        return view('warehouse-task.index', compact(
            'warehouseTasks',
            'totalTask',
            'waitingTask',
            'processTask',
            'completedTask'
        ));
    }

    public function create()
    {
        $salesOrders = SalesOrder::latest()->get();
        $usedInvoiceIds = WarehouseTask::pluck('invoice_id');

        $invoices = Invoice::whereNotIn('id', $usedInvoiceIds)
            ->latest()
            ->get();
        return view('warehouse-task.create', compact(
            'salesOrders',
            'invoices'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required',
            'assigned_to' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);

        $invoice = Invoice::findOrFail($request->invoice_id);

        WarehouseTask::create([
            'id' => WarehouseTask::generateId(),
            'sales_order_id' => $invoice->sales_order_id,
            'invoice_id' => $invoice->id,
            'assigned_to' => $request->assigned_to,
            'status' => 'waiting',
            'note' => $request->note,
        ]);

        return redirect()
            ->route('warehouse-task.index')
            ->with('success', 'Warehouse task berhasil dibuat');
    }

    public function edit(WarehouseTask $warehouseTask)
    {
        $salesOrders = SalesOrder::latest()->get();
        $invoices = Invoice::latest()->get();

        return view('warehouse-task.edit', compact(
            'warehouseTask',
            'salesOrders',
            'invoices'
        ));
    }

    public function update(Request $request, WarehouseTask $warehouseTask)
    {
        $request->validate([
            'sales_order_id' => 'required',
            'invoice_id' => 'required',
            'assigned_to' => 'nullable|string|max:255',
            'status' => 'required|in:waiting,process,completed',
            'note' => 'nullable|string',
        ]);

        $warehouseTask->update([
            'sales_order_id' => $request->sales_order_id,
            'invoice_id' => $request->invoice_id,
            'assigned_to' => $request->assigned_to,
            'status' => $request->status,
            'note' => $request->note,
        ]);

        return redirect()
            ->route('warehouse-task.index')
            ->with('success', 'Warehouse task berhasil diupdate');
    }

    public function destroy(WarehouseTask $warehouseTask)
    {
        $warehouseTask->delete();

        return redirect()
            ->route('warehouse-task.index')
            ->with('success', 'Warehouse task berhasil dihapus');
    }

    public function process(WarehouseTask $warehouseTask)
    {
        if ($warehouseTask->status !== 'waiting') {
            return back()->with('error', 'Task hanya bisa diproses dari status waiting.');
        }

        $warehouseTask->update([
            'status' => 'process'
        ]);

        return back()->with('success', 'Warehouse task mulai diproses');
    }

    public function complete(WarehouseTask $warehouseTask)
    {
        if ($warehouseTask->status !== 'process') {
            return back()->with('error', 'Task hanya bisa diselesaikan dari status process.');
        }

        $warehouseTask->update([
            'status' => 'completed'
        ]);

        return back()->with('success', 'Warehouse task selesai');
    }
}