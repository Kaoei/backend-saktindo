<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Master_customer;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DummyFinanceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure dummy customers exist
        $customers = [
            [
                'id' => 'CUST-001',
                'nama_customer' => 'PT Sinar Raya Utama',
                'nama_pic' => 'Budi Santoso',
                'nomor_hp' => '08123456789',
                'email' => 'finance@sinarraya.co.id',
                'alamat' => 'Jl. Industri No. 45',
                'kota' => 'Jakarta Barat',
                'termin' => 30,
            ],
            [
                'id' => 'CUST-002',
                'nama_customer' => 'CV Jaya Abadi Sukses',
                'nama_pic' => 'Hendri Wijaya',
                'nomor_hp' => '08177788990',
                'email' => 'purchasing@jayaabadi.com',
                'alamat' => 'Jl. Raya Serpong No. 88',
                'kota' => 'Tangerang',
                'termin' => 14,
            ],
            [
                'id' => 'CUST-003',
                'nama_customer' => 'Toko Listrik Berkah Mandiri',
                'nama_pic' => 'Haji Ahmad',
                'nomor_hp' => '081299887766',
                'email' => 'berkahlistrik@gmail.com',
                'alamat' => 'Glodok Blok B-12',
                'kota' => 'Jakarta Pusat',
                'termin' => 7,
            ],
            [
                'id' => 'CUST-004',
                'nama_customer' => 'PT Mega Konstruksi Nusantara',
                'nama_pic' => 'Dewi Lestari',
                'nomor_hp' => '081334455667',
                'email' => 'ap@megakonstruksi.co.id',
                'alamat' => 'Wisma Asri Lt. 5',
                'kota' => 'Jakarta Selatan',
                'termin' => 30,
            ],
        ];

        $customerModels = [];
        foreach ($customers as $c) {
            $customerModels[] = Master_customer::firstOrCreate(
                ['id' => $c['id']],
                $c
            );
        }

        // 2. Ensure supplier product exists for line items
        $supplier = Supplier::firstOrCreate(
            ['name' => 'PT Supplier Utama Saktindo'],
            [
                'company_name' => 'Hannocs',
                'status' => 'active',
            ]
        );

        $product1 = SupplierProduct::firstOrCreate(
            ['sku' => 'SKU-LMP-001'],
            [
                'supplier_id' => $supplier->id,
                'item_name' => 'Lampu LED Hannocs 15W Daylight',
                'unit' => 'pcs',
                'last_purchase_price' => 45000,
                'status' => 'active',
            ]
        );

        $product2 = SupplierProduct::firstOrCreate(
            ['sku' => 'SKU-KBL-002'],
            [
                'supplier_id' => $supplier->id,
                'item_name' => 'Kabel NGA 2x1.5mm 100 Meter',
                'unit' => 'roll',
                'last_purchase_price' => 250000,
                'status' => 'active',
            ]
        );

        // 3. Create dummy Sales Orders & Invoices for Piutang Usaha (AR)
        $invoiceSpecs = [
            [
                'po_num' => 'PO-SRU-2026-001',
                'cust' => $customerModels[0],
                'inv_num' => 'INV-202607-001',
                'days_ago' => 40,
                'due_days_ago' => 10, // Overdue!
                'items' => [
                    ['prod' => $product2, 'qty' => 50, 'price' => 300000, 'd1' => 10, 'd2' => 0],
                ],
                'paid' => 0,
            ],
            [
                'po_num' => 'PO-JAS-2026-002',
                'cust' => $customerModels[1],
                'inv_num' => 'INV-202607-002',
                'days_ago' => 10,
                'due_days_ago' => -5, // Due in 5 days
                'items' => [
                    ['prod' => $product1, 'qty' => 200, 'price' => 50000, 'd1' => 5, 'd2' => 2],
                ],
                'paid' => 3000000,
            ],
            [
                'po_num' => 'PO-TLB-2026-003',
                'cust' => $customerModels[2],
                'inv_num' => 'INV-202607-003',
                'days_ago' => 5,
                'due_days_ago' => -14, // Due in 14 days
                'items' => [
                    ['prod' => $product2, 'qty' => 80, 'price' => 280000, 'd1' => 0, 'd2' => 0],
                ],
                'paid' => 0,
            ],
            [
                'po_num' => 'PO-MKN-2026-004',
                'cust' => $customerModels[3],
                'inv_num' => 'INV-202607-004',
                'days_ago' => 25,
                'due_days_ago' => 2,
                'items' => [
                    ['prod' => $product1, 'qty' => 300, 'price' => 48000, 'd1' => 10, 'd2' => 0],
                ],
                'paid' => 12960000, // Fully paid
            ],
        ];

        foreach ($invoiceSpecs as $spec) {
            $soId = 'SO-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4));
            
            $subtotal = 0;
            $itemsData = [];
            foreach ($spec['items'] as $it) {
                $p = $it['prod'];
                $qty = (float) $it['qty'];
                $price = (float) $it['price'];
                $d1 = (float) $it['d1'];
                $d2 = (float) $it['d2'];
                $lineTotal = $qty * $price * (1 - $d1/100) * (1 - $d2/100);
                $subtotal += $lineTotal;

                $itemsData[] = [
                    'product_code' => $p->id,
                    'product_name' => $p->item_name,
                    'unit' => $p->unit,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'discount_1' => $d1,
                    'discount_2' => $d2,
                    'discount_3' => 0,
                    'discount_4' => 0,
                    'line_total' => $lineTotal,
                    'stock_status' => 'available',
                ];
            }

            $orderDate = now()->subDays($spec['days_ago']);
            $dueDate = now()->subDays($spec['due_days_ago']);

            $so = SalesOrder::firstOrCreate(
                ['customer_po_number' => $spec['po_num']],
                [
                    'id' => $soId,
                    'customer_id' => $spec['cust']->id,
                    'customer_name' => $spec['cust']->nama_customer,
                    'customer_po_number' => $spec['po_num'],
                    'po_date' => $orderDate,
                    'order_date' => $orderDate,
                    'sales_type' => 'js',
                    'order_status' => 'invoiced',
                    'subtotal' => $subtotal,
                    'tax_amount' => 0,
                    'grand_total' => $subtotal,
                ]
            );

            if ($so->wasRecentlyCreated || $so->items()->count() === 0) {
                foreach ($itemsData as $itemRow) {
                    $so->items()->create($itemRow);
                }
            }

            $paid = (float) $spec['paid'];
            $outstanding = max($subtotal - $paid, 0);
            $status = $outstanding <= 0 ? 'paid' : 'outstanding';

            $inv = Invoice::firstOrCreate(
                ['invoice_number' => $spec['inv_num']],
                [
                    'id' => 'INV-' . Str::upper(Str::random(8)),
                    'invoice_number' => $spec['inv_num'],
                    'invoice_type' => 'normal',
                    'tax_type' => 'js',
                    'invoice_date' => $orderDate,
                    'due_date' => $dueDate,
                    'subtotal' => $subtotal,
                    'tax_amount' => 0,
                    'grand_total' => $subtotal,
                    'paid_amount' => $paid,
                    'outstanding_amount' => $outstanding,
                    'status' => $status,
                ]
            );

            if ($inv->wasRecentlyCreated || !$inv->salesOrders()->where('sales_order_id', $so->id)->exists()) {
                $inv->salesOrders()->syncWithoutDetaching([$so->id]);
            }

            if ($paid > 0 && $inv->payments()->count() === 0) {
                InvoicePayment::create([
                    'id' => 'PAY-' . Str::upper(Str::random(8)),
                    'invoice_id' => $inv->id,
                    'payment_number' => 'PAY-' . $orderDate->format('YmdHis'),
                    'payment_date' => $orderDate->addDays(2),
                    'method' => 'transfer_bank',
                    'receiving_account' => 'js',
                    'amount' => $paid,
                    'reference_number' => 'TRF-' . rand(100000, 999999),
                    'notes' => 'Pembayaran DP / Partial dummy',
                ]);
            }
        }
    }
}
