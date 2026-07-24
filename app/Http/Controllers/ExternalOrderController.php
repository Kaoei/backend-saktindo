<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\SupplierProduct;
use App\Models\Master_customer;
use App\Models\WarehouseTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExternalOrderController extends Controller
{
    public function store(Request $request)
    {
        Log::info('External order received:', $request->all());

        try {
            $validated = $request->validate([
                'customer_name' => 'required|string|max:255',
                'customer_email' => 'required|email|max:255',
                'customer_phone' => 'required|string|max:50',
                'customer_address' => 'required|string|max:1000',
                'items' => 'required|array|min:1',
                'items.*.product_code' => 'nullable|string|max:50',
                'items.*.product_name' => 'required|string|max:255',
                'items.*.unit' => 'nullable|string|max:50',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit_price' => 'required|numeric|min:0',
            ]);

            // Try to find a matching customer in master_customers
            $customer = Master_customer::where('email', $validated['customer_email'])
                ->orWhere('nomor_hp', $validated['customer_phone'])
                ->first();

            $poNumber = 'WEB-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));

            $order = DB::transaction(function () use ($validated, $customer, $poNumber) {
                $subtotal = 0;
                foreach ($validated['items'] as $item) {
                    $subtotal += (float) $item['quantity'] * (float) $item['unit_price'];
                }

                $order = SalesOrder::create([
                    'customer_id' => $customer?->id,
                    'customer_name' => $validated['customer_name'],
                    'customer_po_number' => $poNumber,
                    'toko' => 'sjb',
                    'jenis_invoice' => 'normal',
                    'po_date' => now(),
                    'order_date' => now(),
                    'order_status' => 'stock_check',
                    'stock_status' => 'unchecked',
                    'notes' => "Pesanan otomatis dari Toko Online (web-led).\nAlamat Pengiriman: " . $validated['customer_address'] . "\nNo. Telepon: " . $validated['customer_phone'] . "\nEmail: " . $validated['customer_email'],
                    'subtotal' => $subtotal,
                    'tax_amount' => 0,
                    'grand_total' => $subtotal,
                ]);

                foreach ($validated['items'] as $item) {
                    $product = null;
                    if (!empty($item['product_code'])) {
                        $product = SupplierProduct::where('id', $item['product_code'])
                            ->orWhere('sku', $item['product_code'])
                            ->first();
                    }

                    $order->items()->create([
                        'product_code' => $product?->id,
                        'product_name' => $product?->item_name ?? $item['product_name'],
                        'unit' => $product?->unit ?? $item['unit'] ?? 'pcs',
                        'quantity' => (float) $item['quantity'],
                        'unit_price' => (float) $item['unit_price'],
                        'line_total' => (float) $item['quantity'] * (float) $item['unit_price'],
                        'stock_status' => 'unchecked',
                    ]);
                }

                // Create a WarehouseTask automatically for the external order
                WarehouseTask::create([
                    'id' => WarehouseTask::generateId(),
                    'sales_order_id' => $order->id,
                    'invoice_id' => '',
                    'toko' => 'sjb',
                    'task_type' => 'warehouse',
                    'status' => 'waiting',
                    'note' => 'Task otomatis dari pesanan Toko Online (web-led).',
                ]);

                return $order;
            });

            return response()->json([
                'success' => true,
                'message' => 'Sales Order berhasil terintegrasi.',
                'sales_order_id' => $order->id,
                'customer_po_number' => $order->customer_po_number,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('External order validation failed:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('External order integration error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat menyimpan pesanan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
