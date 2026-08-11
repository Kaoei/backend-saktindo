<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\GudangProduct;
use App\Models\SupplierProduct;
use App\Models\Master_customer;
use App\Models\SupplierPO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $role = $user->role;

        // Alerts (All roles get low stock alerts by default, but customized below)
        $lowStockProducts = GudangProduct::with(['supplierProduct', 'rack'])->where('qty', '<', 50)->get();

        switch ($role) {
            case 'finance':
                $pendingOrders30Days = SalesOrder::where('order_date', '<=', now()->subDays(30))
                    ->whereNotIn('order_status', ['completed', 'cancelled'])
                    ->get();

                $dueAR = Invoice::where('status', 'outstanding')
                    ->where('due_date', '<=', now()->addDays(7))
                    ->get();

                $orderedPendingStock = SalesOrderItem::query()
                    ->select('product_code', 'product_name', 'unit', DB::raw('SUM(quantity) as total_ordered'))
                    ->where('stock_status', 'pending')
                    ->whereHas('salesOrder', function ($q) {
                        $q->whereNotIn('order_status', ['completed', 'cancelled']);
                    })
                    ->groupBy('product_code', 'product_name', 'unit')
                    ->get()
                    ->map(function ($item) {
                        $item->current_stock = (float) GudangProduct::where('supplier_product_id', $item->product_code)->sum('qty');
                        $sp = SupplierProduct::find($item->product_code);
                        $item->last_purchase_price = $sp ? (float) $sp->last_purchase_price : 0;
                        $item->supplier_product_id = $item->product_code;
                        return $item;
                    });

                $totalRevenue = Invoice::sum('grand_total');
                $totalOutstandingAR = Invoice::where('status', 'outstanding')->sum('outstanding_amount');
                $totalSupplierPOs = SupplierPO::count();

                $recentInvoices = Invoice::with('salesOrder')->latest()->limit(5)->get();

                return view('dashboard.finance', compact(
                    'lowStockProducts',
                    'pendingOrders30Days',
                    'dueAR',
                    'orderedPendingStock',
                    'totalRevenue',
                    'totalOutstandingAR',
                    'totalSupplierPOs',
                    'recentInvoices'
                ));

            case 'sales':
                $pendingOrders30Days = SalesOrder::where('order_date', '<=', now()->subDays(30))
                    ->whereNotIn('order_status', ['completed', 'cancelled'])
                    ->get();

                $orderedPendingStock = SalesOrderItem::query()
                    ->select('product_code', 'product_name', 'unit', DB::raw('SUM(quantity) as total_ordered'))
                    ->where('stock_status', 'pending')
                    ->whereHas('salesOrder', function ($q) {
                        $q->whereNotIn('order_status', ['completed', 'cancelled']);
                    })
                    ->groupBy('product_code', 'product_name', 'unit')
                    ->get()
                    ->map(function ($item) {
                        $item->current_stock = (float) GudangProduct::where('supplier_product_id', $item->product_code)->sum('qty');
                        $sp = SupplierProduct::find($item->product_code);
                        $item->last_purchase_price = $sp ? (float) $sp->last_purchase_price : 0;
                        $item->supplier_product_id = $item->product_code;
                        return $item;
                    });

                $totalCustomers = Master_customer::count();
                $totalOrders = SalesOrder::count();
                $totalPhysicalProducts = GudangProduct::sum('qty');

                $recentOrders = SalesOrder::latest()->limit(5)->get();

                return view('dashboard.sales', compact(
                    'lowStockProducts',
                    'pendingOrders30Days',
                    'orderedPendingStock',
                    'totalCustomers',
                    'totalOrders',
                    'totalPhysicalProducts',
                    'recentOrders'
                ));

            case 'teknisi': // Warehouse / Gudang
                $temporaryRackProducts = GudangProduct::with(['supplierProduct', 'rack'])
                    ->whereHas('rack', fn($q) => $q->where('is_temporary', true))
                    ->where('created_at', '<=', now()->subDays(3))
                    ->get();

                $orderedPendingStock = SalesOrderItem::query()
                    ->select('product_code', 'product_name', 'unit', DB::raw('SUM(quantity) as total_ordered'))
                    ->where('stock_status', 'pending')
                    ->whereHas('salesOrder', function ($q) {
                        $q->whereNotIn('order_status', ['completed', 'cancelled']);
                    })
                    ->groupBy('product_code', 'product_name', 'unit')
                    ->get()
                    ->map(function ($item) {
                        $item->current_stock = (float) GudangProduct::where('supplier_product_id', $item->product_code)->sum('qty');
                        $sp = SupplierProduct::find($item->product_code);
                        $item->last_purchase_price = $sp ? (float) $sp->last_purchase_price : 0;
                        $item->supplier_product_id = $item->product_code;
                        return $item;
                    });

                $totalPhysicalProducts = GudangProduct::sum('qty');
                $recentOrders = SalesOrder::latest()->limit(5)->get();

                return view('dashboard.warehouse', compact(
                    'lowStockProducts',
                    'temporaryRackProducts',
                    'orderedPendingStock',
                    'totalPhysicalProducts',
                    'recentOrders'
                ));

            case 'super_admin':
            case 'admin':
            default:
                $pendingOrders30Days = SalesOrder::where('order_date', '<=', now()->subDays(30))
                    ->whereNotIn('order_status', ['completed', 'cancelled'])
                    ->get();

                $dueAR = Invoice::where('status', 'outstanding')
                    ->where('due_date', '<=', now()->addDays(7))
                    ->get();

                $temporaryRackProducts = GudangProduct::with(['supplierProduct', 'rack'])
                    ->whereHas('rack', fn($q) => $q->where('is_temporary', true))
                    ->where('created_at', '<=', now()->subDays(3))
                    ->get();

                $orderedPendingStock = SalesOrderItem::query()
                    ->select('product_code', 'product_name', 'unit', DB::raw('SUM(quantity) as total_ordered'))
                    ->where('stock_status', 'pending')
                    ->whereHas('salesOrder', function ($q) {
                        $q->whereNotIn('order_status', ['completed', 'cancelled']);
                    })
                    ->groupBy('product_code', 'product_name', 'unit')
                    ->get()
                    ->map(function ($item) {
                        $item->current_stock = (float) GudangProduct::where('supplier_product_id', $item->product_code)->sum('qty');
                        $sp = SupplierProduct::find($item->product_code);
                        $item->last_purchase_price = $sp ? (float) $sp->last_purchase_price : 0;
                        $item->supplier_product_id = $item->product_code;
                        return $item;
                    });

                $totalCustomers = Master_customer::count();
                $totalOrders = SalesOrder::count();
                $totalRevenue = Invoice::sum('grand_total');
                $totalPhysicalProducts = GudangProduct::sum('qty');
                $totalSupplierPOs = SupplierPO::count();
                $totalOutstandingAR = Invoice::where('status', 'outstanding')->sum('outstanding_amount');

                $recentOrders = SalesOrder::latest()->limit(5)->get();
                $recentInvoices = Invoice::with('salesOrder')->latest()->limit(5)->get();

                return view('dashboard.admin', compact(
                    'lowStockProducts',
                    'pendingOrders30Days',
                    'dueAR',
                    'temporaryRackProducts',
                    'orderedPendingStock',
                    'totalCustomers',
                    'totalOrders',
                    'totalRevenue',
                    'totalPhysicalProducts',
                    'totalSupplierPOs',
                    'totalOutstandingAR',
                    'recentOrders',
                    'recentInvoices'
                ));
        }
    }

    public function searchCustomers(Request $request)
    {
        $search = $request->input('q');
        $customers = \App\Models\Master_customer::query()
            ->when($search, function ($query, $search) {
                $query->where('nama_customer', 'LIKE', "%{$search}%")
                      ->orWhere('id', 'LIKE', "%{$search}%");
            })
            ->limit(20)
            ->get(['id', 'nama_customer', 'termin']);

        return response()->json([
            'results' => $customers->map(fn($c) => [
                'id' => $c->id,
                'text' => $c->nama_customer . ' (' . $c->id . ')',
                'termin' => $c->termin
            ])
        ]);
    }

    public function searchProducts(Request $request)
    {
        $search = $request->input('q');
        $products = \App\Models\SupplierProduct::query()
            ->where(function ($q) {
                $q->where('status', 'active')
                  ->orWhereNull('status')
                  ->orWhere('status', 'stored');
            })
            ->when($search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('item_name', 'LIKE', "%{$search}%")
                      ->orWhere('sku', 'LIKE', "%{$search}%")
                      ->orWhere('part_number', 'LIKE', "%{$search}%");
                });
            })
            ->limit(20)
            ->get(['id', 'sku', 'part_number', 'item_name', 'unit', 'last_purchase_price']);

        $results = $products->map(function ($product) {
            $gProducts = \App\Models\GudangProduct::with('rack')->where('supplier_product_id', $product->id)->get();
            $gProduct = $gProducts->where('qty', '>', 0)->sortByDesc('id')->first() ?: $gProducts->sortByDesc('id')->first();

            $totalStock = (float) $gProducts->sum('qty');
            $rackCodes = $gProducts->pluck('rack_id')->unique()->filter()->implode(', ');
            $locations = $gProducts->map(fn($g) => $g->rack?->location ?: $g->rack?->gudang)->filter()->unique()->implode(', ');

            $price = $gProduct && floatval($gProduct->price) > 0 ? floatval($gProduct->price) : floatval($product->last_purchase_price);
            $discountPercent = $gProduct ? floatval($gProduct->discount) : 0;

            return [
                'id' => $product->id,
                'text' => $product->item_name . ($product->sku ? ' (' . $product->sku . ')' : ''),
                'item_name' => $product->item_name,
                'sku' => $product->sku,
                'unit' => $product->unit ?: 'pcs',
                'price' => $price,
                'stock' => $totalStock,
                'rack' => $rackCodes ?: ($gProduct ? $gProduct->rack_id : '-'),
                'location' => $locations ?: ($gProduct?->rack?->location ?: '-'),
                'discount_percent' => $discountPercent
            ];
        });

        return response()->json([
            'results' => $results
        ]);
    }
}
