<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $invoice->id }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 14px;
            color: #222;
            background: #f0f2f5;
            padding: 30px;
        }
        .receipt-wrapper {
            max-width: 700px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.13);
        }
        .receipt-header {
            background: #1a237e;
            color: #fff;
            padding: 28px 36px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .receipt-header .company-name {
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .receipt-header .subtitle {
            font-size: 13px;
            opacity: 0.8;
            margin-top: 4px;
        }
        .receipt-header .receipt-meta {
            text-align: right;
        }
        .receipt-header .receipt-meta .receipt-num {
            font-size: 18px;
            font-weight: bold;
        }
        .receipt-header .receipt-meta small {
            font-size: 12px;
            opacity: 0.8;
        }
        .paid-stamp {
            display: inline-block;
            border: 3px solid #4caf50;
            color: #4caf50;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 3px;
            padding: 6px 18px;
            border-radius: 4px;
            transform: rotate(-8deg);
            margin-top: 8px;
        }
        .receipt-body {
            padding: 32px 36px;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #888;
            margin-bottom: 8px;
            border-bottom: 1px solid #eee;
            padding-bottom: 4px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px dashed #f0f0f0;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #555; width: 45%; }
        .info-value { font-weight: 600; width: 53%; text-align: right; }
        .amount-box {
            background: #e8eaf6;
            border-left: 5px solid #1a237e;
            padding: 18px 24px;
            border-radius: 4px;
            margin: 24px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .amount-box .label { font-size: 13px; color: #555; }
        .amount-box .value { font-size: 26px; font-weight: bold; color: #1a237e; }
        .desc-box {
            background: #fafafa;
            border: 1px solid #e0e0e0;
            padding: 14px 18px;
            border-radius: 4px;
            line-height: 1.6;
            color: #444;
        }
        .receipt-footer {
            background: #f5f5f5;
            border-top: 2px solid #e0e0e0;
            padding: 18px 36px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #777;
        }
        .print-btn-bar {
            text-align: center;
            margin-bottom: 24px;
        }
        .btn-print {
            background: #1a237e;
            color: white;
            border: none;
            padding: 10px 30px;
            font-size: 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }
        .btn-close-tab {
            background: #555;
            color: white;
            border: none;
            padding: 10px 24px;
            font-size: 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .receipt-wrapper { box-shadow: none; border-radius: 0; }
            .print-btn-bar { display: none; }
        }
    </style>
</head>
<body>
    <div class="print-btn-bar">
        <button class="btn-print" onclick="window.print()">&#128438; Cetak Receipt</button>
        <button class="btn-close-tab" onclick="window.close()">Tutup</button>
    </div>

    <div class="receipt-wrapper">
        <div class="receipt-header">
            <div>
                <div class="company-name">GAPURA</div>
                <div class="subtitle">Receipt Pembayaran</div>
            </div>
            <div class="receipt-meta">
                <div class="receipt-num">{{ $invoice->id }}</div>
                <small>Tanggal Lunas: {{ $invoice->paid_at->format('d/m/Y H:i') }}</small>
                <br>
                <div class="paid-stamp">PAID</div>
            </div>
        </div>

        <div class="receipt-body">
            {{-- Client Info --}}
            <p class="section-title">Informasi Client</p>
            <div class="info-row">
                <span class="info-label">Nama</span>
                <span class="info-value">{{ $invoice->proposal->client->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Email</span>
                <span class="info-value">{{ $invoice->proposal->client->email }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Telepon</span>
                <span class="info-value">{{ $invoice->proposal->client->phone }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Alamat</span>
                <span class="info-value">{{ $invoice->proposal->client->address }}</span>
            </div>

            {{-- Invoice Info --}}
            <p class="section-title" style="margin-top:20px;">Detail Invoice</p>
            <div class="info-row">
                <span class="info-label">No. Invoice</span>
                <span class="info-value">{{ $invoice->id }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Tanggal Invoice</span>
                <span class="info-value">{{ $invoice->created_at->format('d/m/Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">No. Penawaran</span>
                <span class="info-value">{{ $invoice->proposal->id }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Sales</span>
                <span class="info-value">{{ $invoice->proposal->creator->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Finance</span>
                <span class="info-value">{{ $invoice->creator->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Tanggal Lunas</span>
                <span class="info-value">{{ $invoice->paid_at->format('d/m/Y H:i') }}</span>
            </div>

            {{-- Amount --}}
            <div class="amount-box">
                <span class="label">Total Pembayaran</span>
                <span class="value">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</span>
            </div>

            {{-- Description --}}
            <p class="section-title">Keterangan Layanan</p>
            <div class="desc-box">
                <strong>{{ $invoice->proposal->title }}</strong><br>
                <span style="color:#555;">{{ $invoice->proposal->description }}</span>
            </div>

            @if($invoice->notes)
                <p class="section-title" style="margin-top:16px;">Catatan</p>
                <div class="desc-box">{{ $invoice->notes }}</div>
            @endif
        </div>

        <div class="receipt-footer">
            <span>&copy; {{ date('Y') }} Gapura. Terima kasih atas kepercayaan Anda.</span>
            <span>Dicetak: {{ now()->format('d/m/Y H:i') }}</span>
        </div>
    </div>
</body>
</html>
