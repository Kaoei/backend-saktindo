<!DOCTYPE html>
<html>
<head>
    <title>Proforma Invoice - {{ $salesOrder->proformaInvoice->pi_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            padding: 20px;
            color: #000;
        }
        .text-center {
            text-align: center;
        }
        .text-end {
            text-align: right;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .info-table td {
            border: none;
            padding: 4px 0;
        }
        .detail-table th,
        .detail-table td {
            border: 1px solid #000;
            padding: 8px;
        }
        .signature-table td {
            border: none;
            text-align: center;
            padding-top: 50px;
        }
    </style>
</head>
<body>
    <h2 class="text-center">PROFORMA INVOICE</h2>
    <p class="text-center">Pra-Invoice Pemesanan Barang</p>
    <hr>
    
    <table class="info-table">
        <tr>
            <td width="20%"><strong>No. PI</strong></td>
            <td width="30%">: {{ $salesOrder->proformaInvoice->pi_number }}</td>
            <td width="20%"><strong>Customer</strong></td>
            <td width="30%">: {{ $salesOrder->customer_name }}</td>
        </tr>
        <tr>
            <td><strong>Tanggal PI</strong></td>
            <td>: {{ $salesOrder->proformaInvoice->pi_date->format('d M Y') }}</td>
            <td><strong>No. PO Customer</strong></td>
            <td>: {{ $salesOrder->customer_po_number }}</td>
        </tr>
        <tr>
            <td><strong>Toko</strong></td>
            <td>: {{ strtoupper($salesOrder->toko) }}</td>
            <td><strong>Status</strong></td>
            <td>: {{ strtoupper($salesOrder->proformaInvoice->status) }}</td>
        </tr>
    </table>

    <table class="detail-table">
        <thead>
            <tr>
                <th>Produk</th>
                <th class="text-end" width="15%">Qty</th>
                <th>Unit</th>
                <th class="text-end" width="20%">Harga Satuan</th>
                <th class="text-end" width="20%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($salesOrder->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td class="text-end">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                    <td>{{ $item->unit }}</td>
                    <td class="text-end">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-end">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-end">Subtotal</th>
                <th class="text-end">Rp {{ number_format($salesOrder->proformaInvoice->subtotal, 0, ',', '.') }}</th>
            </tr>
            <tr>
                <th colspan="4" class="text-end">Pajak (11%)</th>
                <th class="text-end">Rp {{ number_format($salesOrder->proformaInvoice->tax_amount, 0, ',', '.') }}</th>
            </tr>
            <tr>
                <th colspan="4" class="text-end">Grand Total</th>
                <th class="text-end">Rp {{ number_format($salesOrder->proformaInvoice->grand_total, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>

    <br><br>
    <table class="signature-table">
        <tr>
            <td>
                Dibuat Oleh
                <br><br><br>
                ( ____________________ )
            </td>
            <td>
                Disetujui Oleh Customer
                <br><br><br>
                ( ____________________ )
            </td>
        </tr>
    </table>
</body>
</html>
