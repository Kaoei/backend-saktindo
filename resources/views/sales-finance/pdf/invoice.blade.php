@php
    // Helper terbilang (angka ke kata dalam Bahasa Indonesia)
    if (!function_exists('terbilang')) {
        function terbilang($angka)
        {
            $angka = (int) $angka;
            $huruf = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh',
                'Sebelas'];

            if ($angka < 0) {
                return 'Minus ' . terbilang(abs($angka));
            } elseif ($angka < 12) {
                return $huruf[$angka];
            } elseif ($angka < 20) {
                return trim(terbilang($angka - 10) . ' Belas');
            } elseif ($angka < 100) {
                return trim(terbilang(intval($angka / 10)) . ' Puluh ' . terbilang($angka % 10));
            } elseif ($angka < 200) {
                return trim('Seratus ' . terbilang($angka - 100));
            } elseif ($angka < 1000) {
                return trim(terbilang(intval($angka / 100)) . ' Ratus ' . terbilang($angka % 100));
            } elseif ($angka < 2000) {
                return trim('Seribu ' . terbilang($angka - 1000));
            } elseif ($angka < 1000000) {
                return trim(terbilang(intval($angka / 1000)) . ' Ribu ' . terbilang($angka % 1000));
            } elseif ($angka < 1000000000) {
                return trim(terbilang(intval($angka / 1000000)) . ' Juta ' . terbilang($angka % 1000000));
            } elseif ($angka < 1000000000000) {
                return trim(terbilang(intval($angka / 1000000000)) . ' Milyar ' . terbilang($angka % 1000000000));
            }

            return '';
        }
    }

    $order = $invoice->salesOrder;
    $customer = $order?->customer; // Master_customer (bisa null jika order dibuat manual dgn customer_name saja)
    $items = $order?->items ?? collect();

    $grandTotal = (float) ($invoice->grand_total ?? 0);
    $subtotal   = (float) ($invoice->subtotal ?? 0);
    $taxAmount  = (float) ($invoice->tax_amount ?? 0);

    // Nama yang tampil di kotak "Kepada Yth" -> pakai customer_name dari sales order
    $customerDisplayName = $order->customer_name ?? ($customer->nama_customer ?? 'CASH');

    $taxTypeLabel = match ($invoice->tax_type ?? null) {
        'js' => 'JS',
        'sjb_non_pajak' => 'SJB Non Pajak',
        'sjb_pajak' => 'SJB Pajak',
        default => '-',
    };
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Faktur {{ $invoice->invoice_number ?? $invoice->id }}</title>
    <style>
        @page {
            margin: 20px 30px;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #000;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .header-table td {
            vertical-align: top;
            padding: 0;
        }

        .company-name {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .company-address {
            font-size: 10px;
            line-height: 1.4;
        }

        .faktur-title {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            text-decoration: underline;
            padding-top: 6px;
        }

        .info-table {
            width: 100%;
        }

        .info-table td {
            padding: 1px 4px;
            font-size: 11px;
            vertical-align: top;
        }

        .cash-box {
            border: 1.5px solid #000;
            width: 100%;
            height: 34px;
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            vertical-align: middle;
            padding-top: 8px;
        }

        .ref-table {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
        }

        .ref-table td {
            padding: 2px 0;
            font-size: 11px;
        }

        .label-bold {
            font-weight: bold;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 5px 6px;
        }

        .items-table th {
            font-weight: bold;
            text-align: center;
            background: #f2f2f2;
        }

        .items-table td.center {
            text-align: center;
        }

        .items-table td.right {
            text-align: right;
        }

        .items-table .empty-row td {
            height: 22px;
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            border-top: none;
            border-bottom: none;
        }

        .items-table .last-row td {
            border-bottom: 1px solid #000;
        }

        .footer-table {
            width: 100%;
            margin-top: 8px;
        }

        .footer-table td {
            vertical-align: top;
            font-size: 11px;
            padding: 2px 4px;
        }

        .total-box {
            text-align: right;
            font-weight: bold;
            font-size: 13px;
        }

        .signature-block {
            text-align: center;
            margin-top: 10px;
        }

        .signature-space {
            height: 60px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .notes {
            font-size: 9.5px;
            line-height: 1.5;
        }

        .page-footer {
            font-size: 9px;
            margin-top: 20px;
        }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <table class="header-table">
        <tr>
            <td style="width: 40%;">
                <div class="company-name">PT. SAKTINDO JAYA BERSAMA</div>
                <div class="company-address">
                    PASAR KENARI ALO O AKS 110<br>
                    JL. SALEMBA RAYA<br>
                    SENEN, JAKARTA PUSAT-10430
                </div>
            </td>
            <td style="width: 30%;">
                <div class="faktur-title">FAKTUR</div>
            </td>
            <td style="width: 30%;">
                <table class="info-table">
                    <tr>
                        <td style="width: 40%;" class="label-bold">Tanggal</td>
                        <td style="width: 60%;">
                            {{ optional($invoice->invoice_date)->translatedFormat('d-F-Y') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="label-bold">Kepada Yth :</td>
                        <td></td>
                    </tr>
                </table>
                <div class="cash-box">
                    
                </div>
            </td>
        </tr>
    </table>

    {{-- NO FAKTUR / NO REFF / KETERANGAN --}}
    <table class="ref-table">
        <tr>
            <td style="width: 34%;">
                <span class="label-bold">No. Faktur :</span>
                {{ $invoice->invoice_number ?? $invoice->id }}
            </td>
            <td style="width: 33%;">
                <span class="label-bold">No Reff :</span>
                
            </td>
            <td style="width: 33%;">
                <span class="label-bold">Keterangan :</span>
                {{ $order->notes ?? '' }}
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="label-bold">No. Seri faktur Pajak :</span>
                {{ $invoice->faktur_number ?? '-' }}
            </td>
            <td></td>
        </tr>
    </table>

    {{-- TABEL BARANG --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 38%;">Nama Barang</th>
                <th style="width: 12%;">Qty</th>
                <th style="width: 14%;">Harga</th>
                <th style="width: 10%;">Disc</th>
                <th style="width: 22%;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $index => $item)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $item->product_name }}</td>
                    <td class="center">{{ number_format($item->quantity, 2) }} {{ $item->unit }}</td>
                    <td class="right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="center">
                        {{ rtrim(rtrim(number_format($item->discount_1 ?? 0, 2), '0'), '.') }}+{{ rtrim(rtrim(number_format($item->discount_2 ?? 0, 2), '0'), '.') }}
                    </td>
                    <td class="right">{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach

            {{-- baris kosong pengisi ruang, meniru tampilan asli --}}
            @php $emptyRows = max(0, 6 - $items->count()); @endphp
            @for ($i = 0; $i < $emptyRows; $i++)
                <tr class="empty-row {{ $i == $emptyRows - 1 ? 'last-row' : '' }}">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endfor
        </tbody>
    </table>

    {{-- SUBTOTAL / PAJAK / TERBILANG / TOTAL --}}
    <table class="footer-table">
        <tr>
            <td style="width: 70%;">
                <span class="label-bold">Terbilang :</span>
                {{ trim(terbilang($grandTotal)) }} Rupiah
            </td>
            <td style="width: 30%;">
                <table style="width: 100%;">
                    <tr>
                        <td>Subtotal</td>
                        <td style="text-align: right;">{{ number_format($subtotal, 2) }}</td>
                    </tr>
                    @if ($taxAmount > 0)
                        <tr>
                            <td>PPN 11%</td>
                            <td style="text-align: right;">{{ number_format($taxAmount, 2) }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <table class="footer-table">
        <tr>
            <td style="width: 50%;">
                <div style="margin-top: 10px;">
                    <span class="label-bold">Jatuh Tempo pada Tanggal :</span>
                    {{ optional($invoice->due_date)->translatedFormat('l, j F, Y') }}
                </div>

                <div class="notes" style="margin-top: 15px;">
                    <span class="label-bold">Perhatian</span><br>
                    1. Barang barang yang telah dibeli tidak dapat dikembalikan<br>
                    2. Pembayaran dengan cek/giro belum berarti lunas sebelum diuangkan<br>
                    3. CEK/ GIRO atas nama : PT.SAKTINDO JAYA BERSAMA<br>
                    &nbsp;&nbsp;&nbsp;BCA KENARI, REK NO. 068.3055678
                </div>
            </td>
            <td style="width: 50%;">
                <div class="signature-block">
                    Hormat Kami
                    <div class="signature-space"></div>
                    <div class="signature-name">FENIKI</div>
                    <div>DIREKTUR</div>
                </div>
                <table style="width: 100%; margin-top: 10px;">
                    <tr>
                        <td style="width: 40%;" class="label-bold">Total Rp.</td>
                        <td style="width: 60%; text-align: right; font-weight: bold; border-top: 1px solid #000;">
                            {{ number_format($grandTotal, 2) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="page-footer">
        User : {{ auth()->user()->name ?? '-' }},
        Tgl Cetak : {{ now()->format('d/m/Y H:i:s') }}
    </div>

</body>
</html>