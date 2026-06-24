<?php

namespace App\Http\Controllers;

use App\Models\GudangProduct;
use App\Models\InBound;
use App\Models\Rak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GudangProductController extends Controller
{
    public function index()
    {
        $products = GudangProduct::with(['supplierProduct', 'rack'])
            ->latest()
            ->get();

        return view('gudang_product.index', compact('products'));
    }

    public function create()
    {
        $inbounds = InBound::with(['supplier', 'supplierProduct'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        $racks = Rak::orderBy('rak_kode')->get();

        return view('gudang_product.create', compact('inbounds', 'racks'));
    }

    public function store(Request $request){
        $request->validate([
            'in_bound_id' => 'required|exists:in_bounds,id',
            'rack_id' => 'required|exists:raks,rak_kode',
        ]);

        DB::transaction(function () use ($request) {
            $inBound = InBound::where('id', $request->in_bound_id)
                ->where('status', 'pending')
                ->firstOrFail();

            $existingProduct = GudangProduct::where('supplier_product_id', $inBound->supplier_product_id)
                ->where('rack_id', $request->rack_id)
                ->first();

            if ($existingProduct) {
                $existingProduct->update([
                    'qty' => $existingProduct->qty + $inBound->qty_received,
                    'status' => 'stored',
                ]);
            } else {    

                GudangProduct::create([
                    'id' => GudangProduct::generateId(),
                    'supplier_product_id' => $inBound->supplier_product_id,
                    'rack_id' => $request->rack_id,
                    'qty' => $inBound->qty_received,
                    'status' => 'stored',
                ]);
            }

            $inBound->update([
                'status' => 'stored',
            ]);
        });

        return redirect()
            ->route('gudang-product.index')
            ->with('success', 'Barang berhasil disimpan ke rak.');
    }

    public function destroy(GudangProduct $gudangProduct)
    {
        $gudangProduct->delete();

        return redirect()
            ->route('gudang-product.index')
            ->with('success', 'Data barang gudang berhasil dihapus.');
    }
}