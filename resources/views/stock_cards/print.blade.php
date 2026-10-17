<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Stok - {{ $currentProduct->sku ?: $currentProduct->item_name }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #111;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #222;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .header .subtitle {
            margin-top: 3px;
            font-size: 12px;
            color: #444;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .info-table td {
            padding: 3px 6px;
            font-size: 11px;
        }
        .summary-boxes {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .summary-boxes td {
            width: 25%;
            border: 1px solid #ccc;
            padding: 8px 10px;
            background-color: #fcfcfc;
        }
        .summary-boxes .label {
            font-size: 9px;
            color: #666;
            text-transform: uppercase;
            font-weight: bold;
        }
        .summary-boxes .val {
            font-size: 14px;
            font-weight: bold;
            color: #111;
            margin-top: 3px;
        }
        .summary-boxes .subval {
            font-size: 10px;
            color: #555;
            margin-top: 2px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        .data-table th, .data-table td {
            border: 1px solid #bbb;
            padding: 5px 6px;
        }
        .data-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .bg-light { background-color: #f8f8f8; }
        .print-btn-bar {
            margin-bottom: 15px;
            text-align: right;
        }
        .btn-print {
            padding: 6px 14px;
            background-color: #0d6efd;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
        }
        .footer-signatures {
            width: 100%;
            margin-top: 25px;
            border-collapse: collapse;
        }
        .footer-signatures td {
            width: 33.3%;
            text-align: center;
            vertical-align: top;
            font-size: 11px;
        }
        .sign-space {
            height: 55px;
        }
    </style>
</head>
<body>

<div class="print-btn-bar no-print">
    <button onclick="window.print()" class="btn-print">🖨️ Cetak / Simpan PDF</button>
</div>

<div class="header">
    <h1>KARTU STOK PERSEDIAAN BARANG</h1>
    <div class="subtitle">PT SAKTINDO JAYA BERSAMA - GUDANG LOGISTIK</div>
</div>

<table class="info-table">
    <tr>
        <td style="width: 15%;"><strong>Nama Produk</strong></td>
        <td style="width: 35%;">: {{ $currentProduct->item_name }}</td>
        <td style="width: 15%;"><strong>Periode</strong></td>
        <td style="width: 35%;">: {{ $startDate->format('d/m/Y') }} s/d {{ $endDate->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td><strong>SKU / Kode Barang</strong></td>
        <td>: {{ $currentProduct->sku ?: '-' }}</td>
        <td><strong>Lokasi Rak / Gudang</strong></td>
        <td>: {{ $rackId ?: 'Semua Rak (Global)' }}</td>
    </tr>
    <tr>
        <td><strong>Brand / Kategori</strong></td>
        <td>: {{ $currentProduct->brand ?: '-' }} / {{ $currentProduct->category ?: '-' }}</td>
        <td><strong>Satuan Unit</strong></td>
        <td>: {{ strtoupper($currentProduct->unit ?: 'PCS') }}</td>
    </tr>
</table>

<table class="summary-boxes">
    <tr>
        <td>
            <div class="label">Saldo Awal Periode</div>
            <div class="val">{{ number_format($saldoAwalQty) }} {{ $currentProduct->unit ?: 'pcs' }}</div>
            <div class="subval">Nilai: Rp {{ number_format($saldoAwalValue, 0, ',', '.') }}</div>
        </td>
        <td>
            <div class="label">Total Masuk (+)</div>
            <div class="val" style="color: #008000;">+{{ number_format($totalMasukPeriode) }} {{ $currentProduct->unit ?: 'pcs' }}</div>
            <div class="subval">Periode Terpilih</div>
        </td>
        <td>
            <div class="label">Total Keluar (-)</div>
            <div class="val" style="color: #b30000;">-{{ number_format($totalKeluarPeriode) }} {{ $currentProduct->unit ?: 'pcs' }}</div>
            <div class="subval">Periode Terpilih</div>
        </td>
        <td>
            <div class="label">Saldo Akhir Periode</div>
            <div class="val" style="color: #0033cc;">{{ number_format($saldoAkhirPeriode) }} {{ $currentProduct->unit ?: 'pcs' }}</div>
            <div class="subval">Valuasi: Rp {{ number_format($totalValuasiSaldo, 0, ',', '.') }}</div>
        </td>
    </tr>
</table>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 30px;">No</th>
            <th style="width: 80px;">Tanggal</th>
            <th style="width: 110px;">No. Referensi</th>
            <th style="width: 85px;">Tipe Mutasi</th>
            <th style="width: 75px;">Lokasi Rak</th>
            <th style="width: 60px;">Masuk (+)</th>
            <th style="width: 60px;">Keluar (-)</th>
            <th style="width: 65px;">Saldo Akhir</th>
            <th style="width: 85px;">HPP Satuan</th>
            <th style="width: 95px;">Total Nilai</th>
            <th style="width: 105px;">Valuasi Persediaan</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        <!-- Saldo Awal Row -->
        <tr class="bg-light">
            <td class="text-center">-</td>
            <td class="text-center">{{ $startDate->format('d/m/Y') }}</td>
            <td class="fw-bold">SALDO AWAL</td>
            <td>Saldo Awal</td>
            <td class="text-center">{{ $rackId ?: 'Semua Rak' }}</td>
            <td class="text-end">-</td>
            <td class="text-end">-</td>
            <td class="text-end fw-bold">{{ number_format($saldoAwalQty) }}</td>
            <td class="text-end">Rp {{ number_format($unitCost, 0, ',', '.') }}</td>
            <td class="text-end">-</td>
            <td class="text-end fw-bold">Rp {{ number_format($saldoAwalValue, 0, ',', '.') }}</td>
            <td>Saldo Awal sebelum periode</td>
        </tr>

        @forelse($mutations as $idx => $m)
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td class="text-center">{{ $m->transaction_date ? $m->transaction_date->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ $m->reference_number ?: '-' }}</td>
                <td>{{ $m->type_badge['label'] ?? $m->mutation_type }}</td>
                <td class="text-center">{{ $m->rack_id ?: '-' }}</td>
                <td class="text-end fw-bold" style="color: #008000;">{{ $m->qty_in > 0 ? '+' . number_format($m->qty_in) : '-' }}</td>
                <td class="text-end fw-bold" style="color: #b30000;">{{ $m->qty_out > 0 ? '-' . number_format($m->qty_out) : '-' }}</td>
                <td class="text-end fw-bold">{{ number_format($m->calculated_balance ?? $m->balance) }}</td>
                <td class="text-end">Rp {{ number_format($m->unit_cost ?: $unitCost, 0, ',', '.') }}</td>
                <td class="text-end">Rp {{ number_format($m->total_cost, 0, ',', '.') }}</td>
                <td class="text-end fw-bold">Rp {{ number_format($m->calculated_balance_value ?? $m->balance_value, 0, ',', '.') }}</td>
                <td>{{ $m->notes ?: '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="12" class="text-center" style="padding: 15px;">Belum ada catatan mutasi transaksi pada rentang periode ini.</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr class="bg-light fw-bold">
            <td colspan="5" class="text-end">TOTAL MUTASI PERIODE:</td>
            <td class="text-end" style="color: #008000;">+{{ number_format($totalMasukPeriode) }}</td>
            <td class="text-end" style="color: #b30000;">-{{ number_format($totalKeluarPeriode) }}</td>
            <td class="text-end">{{ number_format($saldoAkhirPeriode) }}</td>
            <td></td>
            <td></td>
            <td class="text-end">Rp {{ number_format($totalValuasiSaldo, 0, ',', '.') }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

<table class="footer-signatures">
    <tr>
        <td>
            Dibuat Oleh (Admin Gudang),
            <div class="sign-space"></div>
            ( __________________________ )
        </td>
        <td>
            Diperiksa Oleh (Accounting/Finance),
            <div class="sign-space"></div>
            ( __________________________ )
        </td>
        <td>
            Disetujui Oleh (Kepala Gudang),
            <div class="sign-space"></div>
            ( __________________________ )
        </td>
    </tr>
</table>

</body>
</html>
