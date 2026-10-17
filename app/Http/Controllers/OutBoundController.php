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

        try {
            DB::transaction(function () use ($request) {
                $gudangProduct = GudangProduct::findOrFail($request->gudang_product_id);

                if ($request->qty > $gudangProduct->qty) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'qty' => "Qty keluar ({$request->qty}) melebihi stok gudang yang tersedia ({$gudangProduct->qty})."
                    ]);
                }

                $outBound = OutBound::create([
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

                $warehouseTask = WarehouseTask::find($request->warehouse_task_id);

                try {
                    \App\Services\StockMutationService::recordMutation([
                        'supplier_product_id' => $gudangProduct->supplier_product_id,
                        'gudang_product_id'   => $gudangProduct->id,
                        'rack_id'             => $gudangProduct->rack_id,
                        'gudang_type'         => $gudangProduct->gudang_type,
                        'transaction_date'    => $request->outbound_date ? date('Y-m-d H:i:s', strtotime($request->outbound_date)) : now(),
                        'mutation_type'       => 'outbound',
                        'reference_type'      => 'outbound',
                        'reference_id'        => $outBound->id,
                        'reference_number'    => $warehouseTask?->task_number ?: ($warehouseTask?->sales_order_id ?: $outBound->id),
                        'qty_in'              => 0,
                        'qty_out'             => (int)$request->qty,
                        'unit_cost'           => (float)$gudangProduct->price,
                        'notes'               => $request->note ?: 'Pengeluaran Barang (Outbound)',
                    ]);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to record stock mutation for outbound: ' . $e->getMessage());
                }

                WarehouseTask::where('id', $request->warehouse_task_id)
                    ->update([
                        'status' => 'completed',
                    ]);

                $warehouseTask?->salesOrder?->update([
                    'order_status' => 'delivered',
                ]);
            });

            return redirect()
                ->route('outbound.index')
                ->with('success', 'Outbound berhasil dibuat dan stok berhasil dikurangi.');
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal membuat Outbound: ' . $e->getMessage());
        }
    }

    public function print(OutBound $outBound)
    {
        $outBound->load([
            'warehouseTask.invoice.deliveryNote',
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
