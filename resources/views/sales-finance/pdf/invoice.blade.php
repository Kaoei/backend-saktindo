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

    // Jika faktur gabungan, kumpulkan item dari semua sales order terkait
    if ($items->isEmpty() && isset($invoice->salesOrders) && $invoice->salesOrders->isNotEmpty()) {
        $items = $invoice->salesOrders->flatMap(function($so) {
            return $so->items ?? collect();
        });
        if (!$customer) {
            $customer = $invoice->salesOrders->first()?->customer;
        }
    }

    // Ambil data custom jika disediakan dari form cetak
    $c = $custom ?? [];

    $paperSize = $c['paper_size'] ?? 'continuous_half';
    $fontMode = $c['font_mode'] ?? 'dotmatrix';
    $showLogo = isset($c['show_logo']) ? (bool) $c['show_logo'] : true;
    $isHtmlPrint = $isHtmlPrint ?? false;

    $invoiceNumber = !empty($c['invoice_number']) ? $c['invoice_number'] : ($invoice->invoice_number ?? $invoice->id);
    $poNumber = !empty($c['po_number']) ? $c['po_number'] : ($order?->customer_po_number);
    if (empty($poNumber) && isset($invoice->salesOrders) && $invoice->salesOrders->isNotEmpty()) {
        $poNumber = $invoice->salesOrders->pluck('customer_po_number')->filter()->unique()->join(', ');
    }
    if (empty($poNumber)) {
        $poNumber = '-';
    }

    $invoiceDateObj = !empty($c['invoice_date']) ? \Carbon\Carbon::parse($c['invoice_date']) : ($invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date) : now());
    $dueDateObj = !empty($c['due_date']) ? \Carbon\Carbon::parse($c['due_date']) : ($invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date) : null);
    $notesText = isset($c['notes']) ? $c['notes'] : ($order->notes ?? '');
    $fakturPajakText = isset($c['faktur_pajak']) ? $c['faktur_pajak'] : ($invoice->faktur_number ?? '-');

    $customerDisplayName = !empty($c['customer_name'])
        ? $c['customer_name']
        : ($order?->customer_name ?? $invoice->salesOrders?->first()?->customer_name ?? ($customer->nama_customer ?? 'CASH'));

    $customerAddressText = !empty($c['customer_address'])
        ? $c['customer_address']
        : ($customer?->alamat ? $customer->alamat . (!empty($customer->kota) ? ', ' . $customer->kota : '') : ($order->shipping_address ?? ''));

    // Items list
    $customItemsList = !empty($c['items']) && is_array($c['items']) ? collect($c['items']) : null;

    $subtotal = isset($c['subtotal']) && $c['subtotal'] !== ''
        ? (float) str_replace(['.', ','], ['', '.'], (string) $c['subtotal'])
        : (float) ($invoice->subtotal ?? 0);

    $taxAmount = isset($c['tax_amount']) && $c['tax_amount'] !== ''
        ? (float) str_replace(['.', ','], ['', '.'], (string) $c['tax_amount'])
        : (float) ($invoice->tax_amount ?? 0);

    $grandTotal = isset($c['grand_total']) && $c['grand_total'] !== ''
        ? (float) str_replace(['.', ','], ['', '.'], (string) $c['grand_total'])
        : (float) ($invoice->grand_total ?? 0);

    if ($grandTotal <= 0 && $subtotal > 0) {
        $grandTotal = $subtotal + $taxAmount;
    }

    $terbilangText = !empty($c['terbilang']) ? $c['terbilang'] : (trim(terbilang($grandTotal)) . ' Rupiah');

    $signerName = !empty($c['signer_name']) ? $c['signer_name'] : 'FENIKI';
    $signerTitle = !empty($c['signer_title']) ? $c['signer_title'] : 'DIREKTUR';
    $userName = !empty($c['user_name']) ? $c['user_name'] : (auth()->user()?->name ?? 'SALSA');
    $computerName = !empty($c['computer_name']) ? $c['computer_name'] : 'JAYA';

    // Logo Perusahaan dari Web Customization (Sidebar Logo)
    $logoBase64 = null;
    if ($showLogo) {
        $sidebarLogoSetting = \App\Models\WebSetting::getValue('sidebar_logo_path');
        $possibleLogoPaths = [];

        if (!empty($sidebarLogoSetting)) {
            $cleanPath = ltrim(str_replace('public/', '', $sidebarLogoSetting), '/');
            $possibleLogoPaths[] = storage_path('app/public/' . $cleanPath);
            $possibleLogoPaths[] = public_path('storage/' . $cleanPath);
            $possibleLogoPaths[] = public_path($cleanPath);
        }
        // Fallback paths
        $possibleLogoPaths[] = storage_path('app/public/branding/logo-saktindo.png');
        $possibleLogoPaths[] = public_path('images/logo-saktindo.png');
        $possibleLogoPaths[] = public_path('src/img/gapuraWhite.png');
        $possibleLogoPaths[] = public_path('DashboardKit-main/images/logo.svg');

        foreach ($possibleLogoPaths as $path) {
            if (!empty($path) && file_exists($path) && is_readable($path) && filesize($path) > 0) {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $mime = match($ext) {
                    'png' => 'image/png',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'svg' => 'image/svg+xml',
                    'webp' => 'image/webp',
                    default => mime_content_type($path) ?: 'image/png',
                };
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
                break;
            }
        }
    }
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Faktur {{ $invoiceNumber }}</title>
    <style>
        @page {
            @if($paperSize === 'continuous_half')
                size: 8.5in 5.5in;
                margin: 4mm 6mm 4mm 6mm;
            @elseif($paperSize === 'continuous_full')
                size: 8.5in 11in;
                margin: 6mm 8mm 6mm 8mm;
            @elseif($paperSize === 'a4_landscape')
                size: 297mm 210mm;
                margin: 8mm 12mm 8mm 12mm;
            @else
                size: 210mm 297mm;
                margin: 8mm 12mm 8mm 12mm;
            @endif
        }

        * {
            box-sizing: border-box;
        }

        body {
            @if($fontMode === 'dotmatrix')
                font-family: 'Courier New', Courier, 'Lucida Console', Monaco, monospace !important;
                font-size: 9.5px;
                color: #000000 !important;
                -webkit-font-smoothing: none;
            @else
                font-family: Arial, Helvetica, sans-serif !important;
                font-size: 9.5px;
                color: #000000 !important;
            @endif
            margin: 0;
            padding: 0;
            background: #ffffff;
            line-height: 1.25;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }

        .header-table td {
            vertical-align: top;
            padding: 0;
        }

        .company-name {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 1px;
            letter-spacing: 0.3px;
        }

        .company-address {
            font-size: 8px;
            line-height: 1.2;
        }

        .faktur-title {
            text-align: center;
            font-size: 17px;
            font-weight: bold;
            text-decoration: underline;
            padding-top: 1px;
            letter-spacing: 2px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }

        .info-table td {
            padding: 0px 2px;
            font-size: 9px;
            vertical-align: top;
        }

        .customer-box {
            border: 1px solid #000000;
            width: 100%;
            min-height: 36px;
            text-align: left;
            padding: 2px 4px;
            font-size: 8.5px;
            line-height: 1.2;
        }

        .customer-name {
            font-weight: bold;
            font-size: 9.5px;
            margin-bottom: 1px;
        }

        .ref-table {
            width: 100%;
            margin-top: 3px;
            border-collapse: collapse;
        }

        .ref-table td {
            padding: 1px 0;
            font-size: 9px;
        }

        .label-bold {
            font-weight: bold;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
            table-layout: fixed;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000000;
            padding: 2.5px 4px;
            font-size: 8.5px;
            vertical-align: middle;
            word-wrap: break-word;
            overflow: hidden;
        }

        .items-table th {
            font-weight: bold;
            text-align: center;
            background: #ffffff;
        }

        .items-table td.center {
            text-align: center;
        }

        .items-table td.right {
            text-align: right;
        }

        .items-table .empty-row td {
            height: 13px;
            border-left: 1px solid #000000;
            border-right: 1px solid #000000;
            border-top: none;
            border-bottom: none;
        }

        .items-table .last-row td {
            border-bottom: 1px solid #000000;
        }

        .footer-table {
            width: 100%;
            margin-top: 3px;
            border-collapse: collapse;
        }

        .footer-table td {
            vertical-align: top;
            font-size: 8.5px;
            padding: 1px 0;
        }

        .signature-block {
            text-align: center;
            margin-top: 2px;
        }

        .signature-space {
            height: 25px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .page-container {
            page-break-inside: avoid;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
            }
        }
    </style>
</head>
<body>

    @if($isHtmlPrint)
        <div class="no-print" style="background: #1e293b; color: #f8fafc; padding: 10px 16px; margin-bottom: 12px; border-radius: 6px; font-family: system-ui, -apple-system, sans-serif; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
            <div>
                <strong style="color: #38bdf8;">🖨️ Mode Cetak Epson LX-310 (Direct Browser Print)</strong>
                <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">
                    Ukuran: <strong>Continuous Form 8.5" x 5.5" (Bagi 2)</strong> &bull; Font: <strong>Monospace Pekat</strong> (Anti-Terpotong & Jelas di Pita Jarum)
                </div>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" onclick="window.print()" style="background: #16a34a; color: white; border: none; padding: 7px 16px; border-radius: 4px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                    <span>🖨️</span> Cetak Sekarang
                </button>
                <button type="button" onclick="window.close()" style="background: #475569; color: white; border: none; padding: 7px 12px; border-radius: 4px; cursor: pointer;">
                    Tutup
                </button>
            </div>
        </div>
    @endif

    <div class="page-container">
        {{-- HEADER --}}
        <table class="header-table">
            <tr>
                <td style="width: 42%;">
                    @if(!empty($logoBase64))
                        <div style="margin-bottom: 2px;">
                            <img src="{{ $logoBase64 }}" style="max-height: 26px; max-width: 170px; object-fit: contain;">
                        </div>
                    @endif
                    <div class="company-name">PT. SAKTINDO JAYA BERSAMA</div>
                    <div class="company-address">
                        PASAR KENARI ALO D AKS 110<br>
                        JL. SALEMBA RAYA<br>
                        SENEN , JAKARTA PUSAT-10430
                    </div>
                </td>
                <td style="width: 23%; text-align: center;">
                    <div class="faktur-title">FAKTUR</div>
                </td>
                <td style="width: 35%;">
                    <table class="info-table">
                        <tr>
                            <td style="width: 32%; font-weight: bold;">Tanggal</td>
                            <td style="width: 68%;">
                                {{ $invoiceDateObj ? $invoiceDateObj->translatedFormat('d-F-Y') : now()->translatedFormat('d-F-Y') }}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="font-weight: bold; padding-top: 1px;">Kepada Yth :</td>
                        </tr>
                    </table>
                    <div class="customer-box">
                        <div class="customer-name">{{ $customerDisplayName }}</div>
                        @if(!empty($customerAddressText))
                            <div>{!! nl2br(e($customerAddressText)) !!}</div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        {{-- NO FAKTUR / NO REFF / KETERANGAN --}}
        <table class="ref-table">
            <tr>
                <td style="width: 34%;">
                    <span class="label-bold">No. Faktur :</span>
                    {{ $invoiceNumber }}
                </td>
                <td style="width: 33%;">
                    <span class="label-bold">No Reff :</span>
                    {{ $poNumber }}
                </td>
                <td style="width: 33%;">
                    <span class="label-bold">Keterangan :</span>
                    {{ $notesText }}
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="label-bold">No . Seri faktur Pajak :</span>
                    {{ $fakturPajakText }}
                </td>
                <td></td>
            </tr>
        </table>

        {{-- TABEL BARANG --}}
        @php
            $displayItems = $customItemsList ?? $items;
            $rowCount = count($displayItems);
            // Pada kertas Continuous Form Bagi 2 (tinggi 5.5 in), batasi baris kosong agar pas 1 halaman
            $targetRows = ($paperSize === 'continuous_half') ? 3 : 5;
            $emptyRows = max(0, $targetRows - $rowCount);
        @endphp
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 39%;">Nama Barang</th>
                    <th style="width: 13%;">Qty</th>
                    <th style="width: 14%;">Harga</th>
                    <th style="width: 9%;">Disc</th>
                    <th style="width: 20%;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($displayItems as $index => $item)
                    @php
                        $pName = is_array($item) ? ($item['name'] ?? $item['item_name'] ?? $item['product_name'] ?? '') : ($item->product_name ?? '');
                        $pQty = (float) (is_array($item) ? ($item['qty'] ?? $item['quantity'] ?? 0) : ($item->quantity ?? 0));
                        $pUnit = is_array($item) ? ($item['unit'] ?? 'PCS') : ($item->unit ?? 'PCS');
                        $rawPrice = is_array($item) ? ($item['price'] ?? $item['unit_price'] ?? 0) : ($item->unit_price ?? 0);
                        $pPrice = (float) str_replace(['.', ','], ['', '.'], (string) $rawPrice);

                        $d1 = (float) (is_array($item) ? ($item['discount_1'] ?? $item['discount1'] ?? 0) : ($item->discount_1 ?? $item->discount ?? 0));
                        $d2 = (float) (is_array($item) ? ($item['discount_2'] ?? $item['discount2'] ?? 0) : ($item->discount_2 ?? 0));

                        $discs = [];
                        if ($d1 > 0) $discs[] = rtrim(rtrim(number_format($d1, 2), '0'), '.');
                        if ($d2 > 0) $discs[] = rtrim(rtrim(number_format($d2, 2), '0'), '.');
                        $discDisplay = !empty($discs) ? implode('+ ', $discs) : '0+ 0';

                        $pTotal = is_array($item) && isset($item['subtotal']) && $item['subtotal'] !== ''
                            ? (float) str_replace(['.', ','], ['', '.'], (string) $item['subtotal'])
                            : (is_array($item) && isset($item['line_total']) && $item['line_total'] !== ''
                                ? (float) str_replace(['.', ','], ['', '.'], (string) $item['line_total'])
                                : ($pPrice * (1 - $d1/100) * (1 - $d2/100) * $pQty));
                        if ($pTotal <= 0 && !is_array($item)) {
                            $pTotal = (float) ($item->line_total ?? ($pPrice * $pQty));
                        }
                    @endphp
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td style="font-weight: 600;">{{ $pName }}</td>
                        <td class="center">{{ number_format($pQty, 2, ',', '.') }} {{ strtoupper($pUnit) }}</td>
                        <td class="right">{{ number_format($pPrice, 2, ',', '.') }}</td>
                        <td class="center">{{ $discDisplay }}</td>
                        <td class="right" style="font-weight: 600;">{{ number_format($pTotal, 2, ',', '.') }}</td>
                    </tr>
                @endforeach

                {{-- Baris kosong pengisi ruang meniru cetakan asli --}}
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

        {{-- SUBTOTAL / TERBILANG --}}
        <table class="footer-table" style="margin-top: 4px;">
            <tr>
                <td style="width: 70%; vertical-align: top;">
                    <span class="label-bold">Terbilang :</span>
                    {{ $terbilangText }}
                </td>
                <td style="width: 30%; text-align: right; vertical-align: top; font-weight: bold; font-size: 10px;">
                    {{ number_format($subtotal > 0 ? $subtotal : $grandTotal, 2, ',', '.') }}
                </td>
            </tr>
        </table>

        {{-- JATUH TEMPO, PERHATIAN, HORMAT KAMI, DAN TOTAL RP --}}
        <table class="footer-table" style="margin-top: 4px;">
            <tr>
                <td style="width: 62%; vertical-align: top;">
                    <div style="font-size: 9.5px; margin-bottom: 4px;">
                        <span class="label-bold">Jatuh Tempo pada Tanggal :</span>
                        {{ $dueDateObj ? $dueDateObj->translatedFormat('l, d F, Y') : ($invoiceDateObj ? $invoiceDateObj->translatedFormat('l, d F, Y') : '-') }}
                    </div>

                    <table style="width: 100%; font-size: 8px; line-height: 1.25; border-collapse: collapse;">
                        <tr>
                            <td style="width: 16%; vertical-align: top; font-weight: bold; padding: 0;">Perhatian</td>
                            <td style="width: 84%; vertical-align: top; padding: 0;">
                                1. Barang-barang yang telah dibeli tidak dapat dikembalikan.<br>
                                2. Pembayaran dengan cek/giro belum berarti lunas sebelum diuangkan.<br>
                                3. CEK/GIRO atas nama : <strong>PT. SAKTINDO JAYA BERSAMA</strong>. BCA KENARI . REK NO. <strong>068.3055678</strong>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width: 38%; vertical-align: top;">
                    <div class="signature-block">
                        <div style="font-weight: bold;">Hormat Kami</div>
                        <div class="signature-space"></div>
                        <div class="signature-name">{{ $signerName }}</div>
                        <div style="font-weight: bold; font-size: 8.5px;">{{ $signerTitle }}</div>
                    </div>
                    <table style="width: 100%; margin-top: 4px;">
                        <tr>
                            <td style="width: 35%; font-weight: bold; font-size: 10.5px; padding: 2px 0;">Total Rp.</td>
                            <td style="width: 65%; text-align: right; font-weight: bold; font-size: 10.5px; border-bottom: 2px solid #000000; padding: 2px 0;">
                                {{ number_format($grandTotal, 2, ',', '.') }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- FOOTER BAWAH --}}
        <table style="width: 100%; margin-top: 6px; font-size: 7.5px; border-collapse: collapse;">
            <tr>
                <td style="width: 75%; text-align: left;">
                    User : {{ $userName }}, Tgl & Jam Cetak : {{ now()->format('d/m/Y H:i:s') }} WIB ,Komp : {{ $computerName }}
                </td>
                <td style="width: 25%; text-align: right;">
                    Page 1 of 1
                </td>
            </tr>
        </table>
    </div>

    @if($isHtmlPrint)
        <script>
            window.addEventListener('load', function() {
                setTimeout(function() {
                    window.print();
                }, 400);
            });
        </script>
    @endif

</body>
</html>
