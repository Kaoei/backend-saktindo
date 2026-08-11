<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $deliveryNote->delivery_note_number }}</title>
    <style>
        @page { size: 74mm 105mm; margin: 2mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 6.8px; line-height: 1.12; color: #1f2937; }
        table { width: 100%; border-collapse: collapse; }
        .doc { border: 1px solid #111827; padding: 4px; }
        .header { border-bottom: 1.5px solid #111827; padding-bottom: 3px; margin-bottom: 3px; }
        .brand { font-size: 6.8px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: #374151; }
        h1 { font-size: 12px; margin: 0; letter-spacing: .4px; color: #111827; }
        .doc-number { text-align: right; font-size: 6.8px; }
        .doc-number strong { display: block; font-size: 8.4px; color: #111827; }
        .pill { display: inline-block; border: 1px solid #111827; padding: 1px 3px; font-size: 6px; text-transform: uppercase; }
        .section-title { margin: 4px 0 2px; font-size: 6.5px; font-weight: 700; color: #4b5563; text-transform: uppercase; }
        .meta td { border: 0; padding: .8px 0; vertical-align: top; }
        .meta .label { width: 31px; color: #6b7280; }
        .customer { border: 1px solid #d1d5db; background: #f9fafb; padding: 3px; margin-top: 3px; }
        .customer-name { font-size: 7.8px; font-weight: 700; color: #111827; }
        .items { margin-top: 4px; border: 1px solid #111827; }
        .items th { background: #111827; color: #ffffff; border: 0; padding: 2.5px 2px; font-size: 6.3px; text-transform: uppercase; }
        .items td { border-top: 1px solid #d1d5db; padding: 2.5px 2px; vertical-align: top; }
        .items tbody tr:nth-child(even) td { background: #f9fafb; }
        .product-name { font-weight: 700; color: #111827; }
        .right { text-align: right; }
        .center { text-align: center; }
        .muted { color: #6b7280; }
        .note { border-left: 2px solid #111827; background: #f9fafb; padding: 2px 3px; margin-top: 4px; }
        .summary { margin-top: 3px; }
        .summary td { border: 0; padding: .5px 0; }
        .signatures { margin-top: 5px; table-layout: fixed; }
        .signatures td { border: 0; text-align: center; padding: 0 1px; }
        .sign-box { height: 19px; border: 1px solid #d1d5db; border-bottom: 1px solid #111827; margin: 2px 0 1px; }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
    @php($invoice = $deliveryNote->invoice)
    @php($customer = $invoice?->salesOrders?->first())
    @php($totalQty = $deliveryNote->items->sum(fn ($item) => (float) $item->qty_sent))
    <div class="toolbar" style="margin-bottom: 10px;">
        <button onclick="window.print()">Print / Re-print A7</button>
    </div>
    <div class="doc">
        <table class="header">
            <tr>
                <td><div class="brand">Warehouse Delivery</div><h1>SURAT JALAN</h1></td>
                <td class="doc-number"><span class="pill">{{ ucfirst($deliveryNote->status) }}</span><strong>{{ $deliveryNote->delivery_note_number }}</strong>{{ optional($deliveryNote->delivery_date)->format('d/m/Y') }}</td>
            </tr>
        </table>
        <table class="meta">
            <tr><td class="label">Invoice</td><td>: {{ $invoice?->invoice_number ?: '-' }}</td><td class="label">Tanggal</td><td>: {{ optional($deliveryNote->delivery_date)->format('d/m/Y') }}</td></tr>
            <tr><td class="label">Tipe</td><td>: {{ ucfirst($invoice?->invoice_type ?: '-') }}</td><td class="label">Faktur</td><td>: {{ $invoice?->faktur_number ?: '-' }}</td></tr>
        </table>
        <div class="customer">
            <div class="muted">Dikirim kepada</div>
            <div class="customer-name">{{ $customer?->customer_name ?: '-' }}</div>
            <div class="muted">SO: {{ $invoice?->salesOrders?->pluck('id')->join(', ') ?: '-' }}</div>
        </div>
        <div class="section-title">Detail Barang</div>
        <table class="items">
            <thead><tr><th style="width: 10%;" class="center">No</th><th>Barang</th><th style="width: 18%;" class="right">Qty</th><th style="width: 14%;">Unit</th></tr></thead>
            <tbody>
                @foreach($deliveryNote->items as $item)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td><div class="product-name">{{ $item->salesOrderItem?->product_name }}</div><div class="muted">{{ $item->salesOrderItem?->product_code }}</div></td>
                        <td class="right">{{ number_format((float) $item->qty_sent, 2, ',', '.') }}</td>
                        <td>{{ $item->salesOrderItem?->unit }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <table class="summary">
            <tr><td class="muted">Total item</td><td class="right">{{ $deliveryNote->items->count() }} baris</td></tr>
            <tr><td class="muted">Total qty</td><td class="right">{{ number_format($totalQty, 2, ',', '.') }}</td></tr>
        </table>
        @if($deliveryNote->notes)
            <div class="note"><strong>Catatan:</strong> {{ $deliveryNote->notes }}</div>
        @endif
        <table class="signatures">
            <tr><td>PIC Sales</td><td>PIC Gudang</td><td>Penerima</td></tr>
            <tr><td><div class="sign-box"></div></td><td><div class="sign-box"></div></td><td><div class="sign-box"></div></td></tr>
            <tr><td>{{ $deliveryNote->pic_sales ?: '-' }}</td><td>{{ $deliveryNote->pic_gudang ?: '-' }}</td><td></td></tr>
        </table>
    </div>
</body>
</html>
