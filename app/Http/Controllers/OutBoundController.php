<?php

namespace App\Http\Controllers;

use App\Models\OutBound;
use App\Models\WarehouseTask;
use App\Models\GudangProduct;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OutBoundController extends Controller
{
    public function index()
    {
        $outBounds = OutBound::with([
            'warehouseTask',
            'gudangProduct.supplierProduct',
            'gudangProduct.rack'
        ])->latest()->get();

        return view('outbound.index', compact('outBounds'));
    }

    public function create(WarehouseTask $warehouseTask)
    {
        $warehouseTask->load(['salesOrder', 'invoice']);

        $gudangProducts = GudangProduct::with([
            'supplierProduct',
            'rack'
        ])
        ->where('qty', '>', 0)
        ->get();

        return view('outbound.create', compact(
            'warehouseTask',
            'gudangProducts'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'warehouse_task_id' => 'required|exists:warehouse_tasks,id',
            'gudang_product_id' => 'required|exists:gudang_products,id',
            'qty' => 'required|integer|min:1',
            'outbound_date' => 'required|date',
            'delivery_type' => 'required|in:full,partial',
            'note' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            $gudangProduct = GudangProduct::findOrFail($request->gudang_product_id);

            if ($request->qty > $gudangProduct->qty) {
                throw new \Exception('Qty keluar melebihi stok gudang.');
            }

            OutBound::create([
                'id' => OutBound::generateId(),
                'warehouse_task_id' => $request->warehouse_task_id,
                'gudang_product_id' => $request->gudang_product_id,
                'qty' => $request->qty,
                'outbound_date' => $request->outbound_date,
                'delivery_type' => $request->delivery_type,
                'status' => 'completed',
                'note' => $request->note,
            ]);

            $gudangProduct->update([
                'qty' => $gudangProduct->qty - $request->qty,
            ]);

            WarehouseTask::where('id', $request->warehouse_task_id)
                ->update([
                    'status' => 'completed',
                ]);

            $warehouseTask = WarehouseTask::find($request->warehouse_task_id);
            $warehouseTask?->salesOrder?->update([
                'order_status' => 'delivered',
            ]);
        });

        return redirect()
            ->route('outbound.index')
            ->with('success', 'Outbound berhasil dibuat dan stok berhasil dikurangi.');
    }

    public function print(OutBound $outBound)
    {
        $outBound->load([
            'warehouseTask.invoice',
            'gudangProduct.supplierProduct',
            'gudangProduct.rack',
        ]);

        $pdf = Pdf::loadView(
            'outbound.print',
            compact('outBound')
        );

        return $pdf->download(
            'Outbound-' . $outBound->id . '.pdf'
        );
    }

    public function destroy(OutBound $outBound)
    {
        $outBound->delete();

        return redirect()
            ->route('outbound.index')
            ->with('success', 'Data outbound berhasil dihapus.');
    }
}
