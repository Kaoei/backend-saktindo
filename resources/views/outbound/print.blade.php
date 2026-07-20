<!DOCTYPE html>
<html>
<head>
    <title>{{ $outBound->id }}</title>

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

        table {
            width: 100%;
            border-collapse: collapse;
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

    <h2 class="text-center">DOKUMEN BARANG KELUAR</h2>
    <p class="text-center">Warehouse Management System</p>

    <hr>

    <table class="info-table">
        <tr>
            <td width="20%"><strong>No Outbound</strong></td>
            <td width="30%">: {{ $outBound->id }}</td>

            <td width="20%"><strong>Tanggal Keluar</strong></td>
            <td width="30%">: {{ $outBound->outbound_date }}</td>
        </tr>

        <tr>
            <td><strong>Warehouse Task</strong></td>
            <td>: {{ $outBound->warehouseTask?->id ?? '-' }}</td>

            <td><strong>Invoice</strong></td>
            <td>: {{ $outBound->warehouseTask?->invoice?->invoice_number ?? '-' }}</td>
        </tr>

        <tr>
            <td><strong>PIC Gudang</strong></td>
            <td>: {{ $outBound->warehouseTask?->assigned_to ?? '-' }}</td>

            <td><strong>PIC Sales</strong></td>
            <td>: {{ $outBound->warehouseTask?->invoice?->deliveryNote?->pic_sales ?? '-' }}</td>
        </tr>

        <tr>
            <td><strong>Delivery Type</strong></td>
            <td>: {{ ucfirst($outBound->delivery_type) }}</td>

            <td><strong>Status</strong></td>
            <td>: {{ ucfirst($outBound->status) }}</td>
        </tr>

        <tr>
            <td><strong>Note</strong></td>
            <td colspan="3">: {{ $outBound->note ?? '-' }}</td>
        </tr>
    </table>

    <br>

    <table class="detail-table">
        <thead>
            <tr>
                <th>Barang</th>
                <th>SKU</th>
                <th>Rack</th>
                <th>Qty Keluar</th>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td>{{ $outBound->gudangProduct?->supplierProduct?->item_name ?? '-' }}</td>
                <td>{{ $outBound->gudangProduct?->supplierProduct?->sku ?? '-' }}</td>
                <td>{{ $outBound->gudangProduct?->rack?->rak_kode ?? '-' }}</td>
                <td class="text-center">{{ $outBound->qty }}</td>
            </tr>
        </tbody>
    </table>

    <br><br>

    <table class="signature-table">
        <tr>
            <td>
                Disiapkan Oleh
                <br><br><br>
                ( {{ $outBound->warehouseTask?->assigned_to ?? '_____________' }} )
            </td>

            <td>
                Diterima Oleh
                <br><br><br>
                ( __________________ )
            </td>
        </tr>
    </table>

</body>
</html>