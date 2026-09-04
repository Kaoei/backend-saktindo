<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Barang Masuk - {{ $inbound->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #111; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 12px; }
        .header h2 { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
        .header p { font-size: 12px; color: #555; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 30px; margin-bottom: 20px; }
        .info-row { display: flex; gap: 8px; padding: 3px 0; border-bottom: 1px dotted #ddd; }
        .info-row .label { min-width: 130px; color: #555; font-size: 12px; }
        .info-row .value { font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table th { background: #333; color: #fff; padding: 7px 10px; font-size: 12px; text-align: left; }
        table td { padding: 7px 10px; border-bottom: 1px solid #ddd; font-size: 12px; }
        table tr:nth-child(even) td { background: #f9f9f9; }
        .footer { display: flex; justify-content: space-between; margin-top: 40px; }
        .sign-box { text-align: center; min-width: 150px; }
        .sign-box .name { border-top: 1px solid #000; padding-top: 5px; margin-top: 60px; font-size: 12px; }
        @media print { body { padding: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="header">
        <h2>TANDA TERIMA BARANG MASUK</h2>
        <p>Nomor: {{ $inbound->id }} &bull; Tanggal Cetak: {{ now()->format('d/m/Y H:i') }}</p>
    </div>
    <div class="info-grid">
        <div>
            <div class="info-row"><span class="label">ID Inbound</span><span class="value">{{ $inbound->id }}</span></div>
            <div class="info-row"><span class="label">Tanggal Diterima</span><span class="value">{{ \Carbon\Carbon::parse($inbound->received_date)->format('d/m/Y') }}</span></div>
            <div class="info-row"><span class="label">Status</span><span class="value">{{ strtoupper($inbound->status) }}</span></div>
            @if($inbound->invoice_number)
            <div class="info-row"><span class="label">No. Invoice Supplier</span><span class="value">{{ $inbound->invoice_number }}</span></div>
            @endif
        </div>
        <div>
            <div class="info-row"><span class="label">Supplier</span><span class="value">{{ $inbound->supplier->name ?? '-' }}</span></div>
            @if($inbound->supplierPo)
            <div class="info-row"><span class="label">No. PO Supplier</span><span class="value">{{ $inbound->supplierPo->po_number }}</span></div>
            @endif
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Nama Barang</th>
                <th>SKU</th>
                <th style="text-align:right;">Qty Diterima</th>
                <th style="text-align:right;">Qty Rusak</th>
                <th style="text-align:right;">Qty Kurang</th>
                <th style="text-align:right;">HPP (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $inbound->supplierProduct->item_name ?? '-' }}</td>
                <td>{{ $inbound->supplierProduct->sku ?? '-' }}</td>
                <td style="text-align:right;">{{ number_format($inbound->qty_received, 0) }}</td>
                <td style="text-align:right;">{{ number_format($inbound->qty_damaged ?? 0, 0) }}</td>
                <td style="text-align:right;">{{ number_format($inbound->qty_missing ?? 0, 0) }}</td>
                <td style="text-align:right;">Rp {{ number_format($inbound->hpp ?? 0, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
    @if($inbound->notes)
    <p style="margin-bottom: 20px;"><strong>Catatan:</strong> {{ $inbound->notes }}</p>
    @endif
    <div class="footer">
        <div class="sign-box"><div class="name">Dibuat Oleh</div></div>
        <div class="sign-box"><div class="name">Petugas Gudang</div></div>
        <div class="sign-box"><div class="name">Mengetahui</div></div>
    </div>
    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()" style="padding: 8px 24px; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer;">Cetak</button>
        <button onclick="window.close()" style="padding: 8px 24px; background: #6c757d; color: #fff; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px;">Tutup</button>
    </div>
    <script>window.addEventListener('load', function() { setTimeout(function() { window.print(); }, 300); });</script>
</body>
</html>
