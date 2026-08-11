<!DOCTYPE html>
<html>
<head>
    <title>Task Checklist - {{ $warehouseTask->id }}</title>
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
    <h2 class="text-center">CHECKLIST PEKERJAAN GUDANG</h2>
    <p class="text-center">Task Checklist - WMS</p>
    <hr>
    
    <table class="info-table">
        <tr>
            <td width="20%"><strong>No. Tugas</strong></td>
            <td width="30%">: {{ $warehouseTask->id }}</td>
            <td width="20%"><strong>Sales Order</strong></td>
            <td width="30%">: {{ $warehouseTask->sales_order_id }}</td>
        </tr>
        <tr>
            <td><strong>Ditugaskan Ke</strong></td>
            <td>: {{ $warehouseTask->assigned_to ?: '-' }}</td>
            <td><strong>Invoice Ref</strong></td>
            <td>: {{ $warehouseTask->invoice?->invoice_number ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Status</strong></td>
            <td>: {{ strtoupper($warehouseTask->status) }}</td>
            <td><strong>Tipe Toko</strong></td>
            <td>: {{ strtoupper($warehouseTask->toko ?: 'JS') }}</td>
        </tr>
        <tr>
            <td><strong>Catatan</strong></td>
            <td colspan="3">: {{ $warehouseTask->note ?: '-' }}</td>
        </tr>
    </table>

    <table class="detail-table">
        <thead>
            <tr>
                <th width="5%" class="text-center">[✓]</th>
                <th>Nama Produk</th>
                <th>Unit</th>
                <th class="text-center" width="15%">Qty Diminta</th>
                <th class="text-center" width="20%">Selesai Qty</th>
            </tr>
        </thead>
        <tbody>
            @if($warehouseTask->salesOrder && $warehouseTask->salesOrder->items)
                @foreach($warehouseTask->salesOrder->items as $item)
                    <tr>
                        <td class="text-center">[ ]</td>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ $item->unit }}</td>
                        <td class="text-center">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                        <td class="text-center">___________</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="5" class="text-center">Tidak ada item barang dalam SO ini.</td>
                </tr>
            @endif
        </tbody>
    </table>

    <br><br>
    <table class="signature-table">
        <tr>
            <td>
                Disiapkan Oleh
                <br><br><br>
                ( ____________________ )
            </td>
            <td>
                Dicek Oleh Supervisor
                <br><br><br>
                ( ____________________ )
            </td>
        </tr>
    </table>
</body>
</html>
