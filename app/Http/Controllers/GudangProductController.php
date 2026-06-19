<?php

namespace App\Http\Controllers;

use App\Models\GudangProduct;
use App\Models\InBound;
use App\Models\Rack;
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

    public function store(Request $request)
    {
        $request->validate([
            'inbound_id' => 'required|exists:in_bounds,id',
            'rack_id' => 'required|exists:racks,rak_kode',
        ]);

        DB::transaction(function () use ($request) {
            $inbound = InBound::findOrFail($request->inbound_id);

            GudangProduct::create([
                'id' => GudangProduct::generateId(),
                'supplier_product_id' => $inbound->supplier_product_id,
                'rack_id' => $request->rack_id,
                'qty' => $inbound->qty_received,
                'status' => 'stored',
            ]);

            $inbound->update([
                'status' => 'stored',
            ]);
        });

        return redirect()
            ->route('gudang-product.index')
            ->with('status', 'Barang berhasil ditempatkan ke rak.');
    }

    public function destroy(GudangProduct $gudangProduct)
    {
        $gudangProduct->delete();

        return redirect()
            ->route('gudang-product.index')
            ->with('status', 'Data barang gudang berhasil dihapus.');
    }
}