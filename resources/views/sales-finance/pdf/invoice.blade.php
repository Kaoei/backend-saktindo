<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        h1 { font-size: 22px; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #d1d5db; padding: 7px 5px; vertical-align: top; }
        th { text-align: left; background: #f3f4f6; }
        .meta td { border: 0; padding: 2px 0; }
        .right { text-align: right; }
        .muted { color: #6b7280; }
        .summary td { border: 0; padding: 4px 5px; }
    </style>
</head>
<body>
    @php($customer = $invoice->salesOrders->first())
    <table class="meta">
        <tr>
            <td><h1>INVOICE</h1><div class="muted">{{ ucfirst($invoice->invoice_type) }}</div></td>
            <td class="right">
                <strong>{{ $invoice->invoice_number }}</strong><br>
                Faktur: {{ $invoice->faktur_number ?: '-' }}<br>
                {{ optional($invoice->invoice_date)->format('d/m/Y') }}
            </td>
        </tr>
    </table>

    <table class="meta" style="margin-top: 12px;">
        <tr><td>Customer</td><td>: {{ $customer?->customer_name }}</td></tr>
        <tr><td>Jatuh Tempo</td><td>: {{ optional($invoice->due_date)->format('d/m/Y') ?: '-' }}</td></tr>
        <tr><td>Status</td><td>: {{ ucfirst($invoice->status) }}</td></tr>
    </table>

    <table style="margin-top: 14px;">
        <thead>
            <tr><th>SO</th><th>Produk</th><th class="right">Qty</th><th class="right">Harga</th><th class="right">Total</th></tr>
        </thead>
        <tbody>
            @foreach($invoice->salesOrders as $order)
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td>{{ $item->product_name }}<br><span class="muted">{{ $item->product_code }}</span></td>
                        <td class="right">{{ number_format((float) $item->quantity, 2, ',', '.') }} {{ $item->unit }}</td>
                        <td class="right">Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                        <td class="right">Rp {{ number_format((float) $item->line_total, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <table class="summary" style="margin-top: 12px; width: 45%; margin-left: auto;">
        <tr><td>Subtotal</td><td class="right">Rp {{ number_format((float) $invoice->subtotal, 0, ',', '.') }}</td></tr>
        <tr><td>Pajak</td><td class="right">Rp {{ number_format((float) $invoice->tax_amount, 0, ',', '.') }}</td></tr>
        <tr><td><strong>Grand Total</strong></td><td class="right"><strong>Rp {{ number_format((float) $invoice->grand_total, 0, ',', '.') }}</strong></td></tr>
        <tr><td>Terbayar</td><td class="right">Rp {{ number_format((float) $invoice->paid_amount, 0, ',', '.') }}</td></tr>
        <tr><td>Outstanding</td><td class="right">Rp {{ number_format((float) $invoice->outstanding_amount, 0, ',', '.') }}</td></tr>
    </table>
</body>
</html>
