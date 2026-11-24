@php
    if (!function_exists('terbilangRupiah') && file_exists(app_path('Helpers/terbilang.php'))) {
        require_once app_path('Helpers/terbilang.php');
    }

    // Fallback jika helper belum tersedia
    if (!function_exists('terbilangWords')) {
        function terbilangWords($angka): string
        {
            $angka = (int) $angka;
            $huruf = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
            if ($angka < 0) return 'Minus ' . terbilangWords(abs($angka));
            if ($angka < 12) return $huruf[$angka];
            if ($angka < 20) return trim(terbilangWords($angka - 10) . ' Belas');
            if ($angka < 100) return trim(terbilangWords(intval($angka / 10)) . ' Puluh ' . terbilangWords($angka % 10));
            if ($angka < 200) return trim('Seratus ' . terbilangWords($angka - 100));
            if ($angka < 1000) return trim(terbilangWords(intval($angka / 100)) . ' Ratus ' . terbilangWords($angka % 100));
            if ($angka < 2000) return trim('Seribu ' . terbilangWords($angka - 1000));
            if ($angka < 1000000) return trim(terbilangWords(intval($angka / 1000)) . ' Ribu ' . terbilangWords($angka % 1000));
            if ($angka < 1000000000) return trim(terbilangWords(intval($angka / 1000000)) . ' Juta ' . terbilangWords($angka % 1000000));
            if ($angka < 1000000000000) return trim(terbilangWords(intval($angka / 1000000000)) . ' Milyar ' . terbilangWords($angka % 1000000000));
            if ($angka < 1000000000000000) return trim(terbilangWords(intval($angka / 1000000000000)) . ' Triliun ' . terbilangWords($angka % 1000000000000));
            return '';
        }
    }
    if (!function_exists('terbilangRupiah')) {
        function terbilangRupiah($angka): string
        {
            $nominal = (int) round((float) $angka);
            if ($nominal === 0) return 'Nol Rupiah';
            $hasil = trim(preg_replace('/\s+/', ' ', terbilangWords($nominal)));
            return $hasil ? $hasil . ' Rupiah' : 'Nol Rupiah';
        }
    }
    if (!function_exists('terbilang')) {
        function terbilang($angka) { return terbilangWords($angka); }
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

    // Permintaan user: No Faktur dan No Reff harus sama
    $poNumber = !empty($c['po_number']) ? $c['po_number'] : $invoiceNumber;

    $invoiceDateObj = !empty($c['invoice_date']) ? \Carbon\Carbon::parse($c['invoice_date']) : ($invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date) : now());
    $dueDateObj = !empty($c['due_date']) ? \Carbon\Carbon::parse($c['due_date']) : ($invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date) : null);
    $notesText = isset($c['notes']) ? $c['notes'] : ($order->notes ?? '');
    // Jika keterangan kosong tetapi terdapat referensi PO customer, cantumkan di keterangan
    if (empty($notesText) && !empty($order?->customer_po_number) && !preg_match('/^(PO-\d{8}-[A-Za-z0-9]{5}|WEB-\d{14}-[A-Za-z0-9]+)$/', $order->customer_po_number)) {
        $notesText = $order->customer_po_number;
    }

    // Permintaan user: Jika bukan SJB Pajak, no seri faktur pajak tidak diperlukan
    $isSjbPajak = ($invoice->tax_type === 'sjb_pajak') || ($order?->sales_type === 'sjb_pajak');
    $fakturPajakText = '';
    if ($isSjbPajak) {
        $fakturPajakText = isset($c['faktur_pajak']) ? trim((string)$c['faktur_pajak']) : ($invoice->faktur_number ?? '');
    }

    $customerDisplayName = !empty($c['customer_name'])
        ? $c['customer_name']
        : ($order?->customer_name ?? $invoice->salesOrders?->first()?->customer_name ?? ($customer->nama_customer ?? 'CASH'));

    $customerAddressText = !empty($c['customer_address'])
        ? $c['customer_address']
        : ($customer?->alamat ? $customer->alamat . (!empty($customer->kota) ? ', ' . $customer->kota : '') : ($order->shipping_address ?? ''));

    // Items list
    $customItemsList = !empty($c['items']) && is_array($c['items']) ? collect($c['items']) : null;

    $subtotal = isset($c['subtotal']) && $c['subtotal'] !== ''
        ? (function_exists('parseFlexibleMoney') ? parseFlexibleMoney($c['subtotal']) : (float) $c['subtotal'])
        : (float) ($invoice->subtotal ?? 0);

    $taxAmount = isset($c['tax_amount']) && $c['tax_amount'] !== ''
        ? (function_exists('parseFlexibleMoney') ? parseFlexibleMoney($c['tax_amount']) : (float) $c['tax_amount'])
        : (float) ($invoice->tax_amount ?? 0);

    $grandTotal = isset($c['grand_total']) && $c['grand_total'] !== ''
        ? (function_exists('parseFlexibleMoney') ? parseFlexibleMoney($c['grand_total']) : (float) $c['grand_total'])
        : (float) ($invoice->grand_total ?? 0);

    if ($grandTotal <= 0 && $subtotal > 0) {
        $grandTotal = $subtotal + $taxAmount;
    }

    $terbilangText = !empty($c['terbilang']) && trim((string) $c['terbilang']) !== ''
        ? trim((string) $c['terbilang'])
        : (function_exists('terbilangRupiah') ? terbilangRupiah($grandTotal) : (trim(terbilang($grandTotal)) . ' Rupiah'));

    $signerName = !empty($c['signer_name']) ? $c['signer_name'] : 'FENIKI';
    $signerTitle = !empty($c['signer_title']) ? $c['signer_title'] : 'DIREKTUR';
    $userName = !empty($c['user_name']) ? $c['user_name'] : (auth()->user()?->name ?? 'SALSA');
    $computerName = !empty($c['computer_name']) ? $c['computer_name'] : 'JAYA';

    // Entity / Nama Toko
    $taxType = $invoice->tax_type ?? $order?->sales_type ?? 'sjb_pajak';
    $isJs = in_array($taxType, ['js', 'non_pajak']);
    $defaultCompanyName = $isJs ? 'JAYA SAKTI' : 'PT. SAKTINDO JAYA BERSAMA';
    $companyName = !empty($c['company_name']) ? $c['company_name'] : $defaultCompanyName;
    $companyAddress = !empty($c['company_address']) ? $c['company_address'] : ($isJs 
        ? "PASAR KENARI AKS 110\nJL. SALEMBA RAYA\nSENEN , JAKARTA PUSAT"
        : "PASAR KENARI ALO D AKS 110\nJL. SALEMBA RAYA\nSENEN , JAKARTA PUSAT-10430");
    $bankAccountName = !empty($c['bank_account_name']) ? $c['bank_account_name'] : $companyName;
    $bankAccountDetails = !empty($c['bank_account_details']) ? $c['bank_account_details'] : "BCA KENARI . REK NO. 068.3055678";

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
                size: 8.5in 5.5in landscape;
                margin: 3mm 6mm 3mm 6mm;
            @elseif($paperSize === 'continuous_full')
                size: 8.5in 11in portrait;
                margin: 5mm 8mm 5mm 8mm;
            @elseif($paperSize === 'a4_landscape')
                size: 297mm 210mm landscape;
                margin: 6mm 10mm 6mm 10mm;
            @else
                size: 210mm 297mm portrait;
                margin: 6mm 10mm 6mm 10mm;
            @endif
        }

        * {
            box-sizing: border-box;
            -webkit-text-stroke: 0 !important;
            text-shadow: none !important;
        }

        body {
            @if($fontMode === 'dotmatrix')
                font-family: 'Consolas', 'Courier New', Courier, 'Lucida Console', Monaco, monospace !important;
                font-size: 11px;
                font-weight: 700 !important;
                color: #000000 !important;
                -webkit-font-smoothing: none !important;
                font-smooth: never !important;
                text-rendering: geometricPrecision !important;
            @else
                font-family: Arial, Helvetica, sans-serif !important;
                font-size: 10.5px;
                font-weight: 700 !important;
                color: #000000 !important;
            @endif
            margin: 0;
            padding: 0;
            background: #ffffff;
            line-height: 1.25;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
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
            font-size: 14px;
            font-weight: 900;
            margin-bottom: 1px;
            letter-spacing: 0.5px;
            color: #000000;
        }

        .company-address {
            font-size: 9.5px;
            font-weight: 700;
            line-height: 1.22;
            color: #000000;
        }

        .faktur-title {
            text-align: center;
            font-size: 21px;
            font-weight: 900;
            text-decoration: underline;
            padding-top: 2px;
            letter-spacing: 3px;
            color: #000000;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
            font-weight: 700;
        }

        .info-table td {
            padding: 0px 2px;
            font-size: 10px;
            vertical-align: top;
        }

        .customer-box {
            border: 1.5px solid #000000;
            width: 100%;
            min-height: 44px;
            text-align: left;
            padding: 4px 6px;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.25;
            background: #ffffff;
        }

        .customer-name {
            font-weight: 900;
            font-size: 10.5px;
            margin-bottom: 1px;
        }

        .ref-table {
            width: 100%;
            margin-top: 5px;
            border-collapse: collapse;
            font-size: 10.5px;
            font-weight: 700;
        }

        .ref-table td {
            padding: 1.5px 0;
        }

        .label-bold {
            font-weight: 900;
        }

        /* TABEL BARANG: TEBAL, FULL SAMPAI BAWAH, BEBAS GARIS ANTAR BARIS */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            table-layout: fixed;
            font-size: 10.5px;
            font-weight: 700;
            border: 1.5px solid #000000;
        }

        .items-table th,
        .items-table td {
            font-size: 10.5px;
            word-wrap: break-word;
            overflow: hidden;
        }

        .items-table th {
            border: 1.5px solid #000000;
            padding: 4px 6px;
            font-weight: 900;
            text-align: center;
            background: #ffffff;
        }

        .items-table td {
            border-left: 1.5px solid #000000;
            border-right: 1.5px solid #000000;
            border-top: none;
            border-bottom: none;
            padding: 3px 6px;
            font-weight: 700;
            vertical-align: top;
        }

        .items-table td.center {
            text-align: center;
        }

        .items-table td.right {
            text-align: right;
        }

        .items-table tr.last-row td {
            border-bottom: 1.5px solid #000000;
        }

        .items-table .spacer-row td {
            border-left: 1.5px solid #000000;
            border-right: 1.5px solid #000000;
            border-top: none;
            border-bottom: none;
        }

        .subtotal-table {
            width: 100%;
            margin-top: 4px;
            border-collapse: collapse;
            font-size: 11px;
            font-weight: 800;
        }

        .bottom-section {
            width: 100%;
            margin-top: 5px;
            border-collapse: collapse;
        }

        .bottom-section td {
            vertical-align: top;
            padding: 0;
        }

        .signature-block {
            text-align: center;
            padding: 0 8px;
        }

        .signature-space {
            height: 42px;
        }

        .signature-name {
            font-weight: 900;
            font-size: 11.5px;
            text-decoration: underline;
        }

        .total-box {
            text-align: right;
            padding-left: 8px;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            font-weight: 900;
            font-size: 13px;
            border-bottom: 2.5px solid #000000;
            padding-bottom: 2px;
            margin-top: 38px;
        }

        .footer-table {
            width: 100%;
            margin-top: 5px;
            border-collapse: collapse;
        }

        .page-container {
            page-break-inside: avoid;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            * {
                -webkit-text-stroke: 0 !important;
                text-shadow: none !important;
                filter: none !important;
            }
            body {
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                color: #000000 !important;
                -webkit-font-smoothing: none !important;
                font-smooth: never !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

    @if($isHtmlPrint)
        <div class="no-print" style="background: #0f172a; color: #f8fafc; padding: 12px 18px; margin-bottom: 12px; border-radius: 6px; font-family: system-ui, -apple-system, sans-serif; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #334155; padding-bottom: 8px; margin-bottom: 8px;">
                <div>
                    <strong style="color: #38bdf8; font-size: 14px;">🖨️ Mode Cetak Epson LX-310 (Direct Browser Print)</strong>
                    <div style="font-size: 12px; color: #cbd5e1; margin-top: 2px;">
                        Ukuran: <strong>Continuous Form 8.5" x 5.5" (Bagi 2)</strong> &bull; Font: <strong>Solid Monospace</strong> (Anti-Keropos & Pekat Tanpa Dithering)
                    </div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="button" onclick="window.print()" style="background: #16a34a; color: white; border: none; padding: 8px 18px; border-radius: 4px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px; font-size: 13px;">
                        <span>🖨️</span> Cetak Sekarang
                    </button>
                    <button type="button" onclick="window.close()" style="background: #475569; color: white; border: none; padding: 8px 14px; border-radius: 4px; cursor: pointer; font-size: 13px;">
                        Tutup
                    </button>
                </div>
            </div>
            <div style="font-size: 11px; color: #94a3b8; display: flex; gap: 16px; flex-wrap: wrap;">
                <span>💡 <strong>Panduan Print Dialog Browser:</strong></span>
                <span>• Tata Letak (Layout): <strong style="color:#fde047;">Lanskap / Landscape</strong></span>
                <span>• Margin: <strong style="color:#fde047;">None (Tanpa Margin)</strong></span>
                <span>• Skala (Scale): <strong style="color:#fde047;">100% / Default</strong></span>
                <span>• Warna: <strong style="color:#fde047;">Hitam & Putih (Black & White)</strong></span>
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
                    <div class="company-name">{{ $companyName }}</div>
                    <div class="company-address">
                        {!! nl2br(e($companyAddress)) !!}
                    </div>
                </td>
                <td style="width: 24%; text-align: center;">
                    <div class="faktur-title">FAKTUR</div>
                </td>
                <td style="width: 34%;">
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
                <td style="width: 38%;">
                    <span class="label-bold">No. Faktur :</span>
                    {{ $invoiceNumber }}
                </td>
                <td style="width: 32%;">
                    <span class="label-bold">No Reff :</span>
                    {{ $poNumber }}
                </td>
                <td style="width: 30%;">
                    <span class="label-bold">Keterangan :</span>
                    {{ $notesText }}
                </td>
            </tr>
            @if($isSjbPajak && !empty($fakturPajakText) && $fakturPajakText !== '-')
            <tr>
                <td colspan="2">
                    <span class="label-bold">No . Seri faktur Pajak :</span>
                    {{ $fakturPajakText }}
                </td>
                <td></td>
            </tr>
            @endif
        </table>

        {{-- TABEL BARANG --}}
        @php
            $rawDisplayItems = $customItemsList ?? $items;
            $consolidatedItems = collect();
            foreach ($rawDisplayItems as $item) {
                $pName = is_array($item) ? ($item['name'] ?? $item['item_name'] ?? $item['product_name'] ?? '') : ($item->product_name ?? '');
                $pCode = is_array($item) ? ($item['code'] ?? $item['product_code'] ?? '') : ($item->product_code ?? '');
                $pQty = (float) (is_array($item) ? ($item['qty'] ?? $item['quantity'] ?? 0) : ($item->quantity ?? 0));
                $pUnit = is_array($item) ? ($item['unit'] ?? 'PCS') : ($item->unit ?? 'PCS');
                $rawPrice = is_array($item) ? ($item['price'] ?? $item['unit_price'] ?? 0) : ($item->unit_price ?? 0);
                $pPrice = function_exists('parseFlexibleMoney') ? parseFlexibleMoney($rawPrice) : (float) $rawPrice;

                $d1 = (float) (is_array($item) ? ($item['discount_1'] ?? $item['discount1'] ?? 0) : ($item->discount_1 ?? $item->discount ?? 0));
                $d2 = (float) (is_array($item) ? ($item['discount_2'] ?? $item['discount2'] ?? 0) : ($item->discount_2 ?? 0));

                $pTotal = is_array($item) && isset($item['subtotal']) && $item['subtotal'] !== ''
                    ? (function_exists('parseFlexibleMoney') ? parseFlexibleMoney($item['subtotal']) : (float) $item['subtotal'])
                    : (is_array($item) && isset($item['line_total']) && $item['line_total'] !== ''
                        ? (function_exists('parseFlexibleMoney') ? parseFlexibleMoney($item['line_total']) : (float) $item['line_total'])
                        : ($pPrice * (1 - $d1/100) * (1 - $d2/100) * $pQty));
                if ($pTotal <= 0 && !is_array($item)) {
                    $pTotal = (float) ($item->line_total ?? ($pPrice * $pQty));
                }

                // Konsolidasi barang yang identik (nama produk, harga satuan, diskon, dan satuan sama)
                $key = trim(strtolower($pName)) . '|' . round($pPrice, 2) . '|' . round($d1, 2) . '|' . round($d2, 2) . '|' . strtoupper(trim($pUnit));
                if ($consolidatedItems->has($key)) {
                    $existing = $consolidatedItems->get($key);
                    $existing['qty'] += $pQty;
                    $existing['total'] += $pTotal;
                    $consolidatedItems->put($key, $existing);
                } else {
                    $discs = [];
                    if ($d1 > 0) $discs[] = rtrim(rtrim(number_format($d1, 2), '0'), '.');
                    if ($d2 > 0) $discs[] = rtrim(rtrim(number_format($d2, 2), '0'), '.');
                    $discDisplay = !empty($discs) ? implode('+ ', $discs) : '0+ 0';

                    $consolidatedItems->put($key, [
                        'name' => $pName,
                        'code' => $pCode,
                        'unit' => $pUnit,
                        'qty' => $pQty,
                        'price' => $pPrice,
                        'd1' => $d1,
                        'd2' => $d2,
                        'disc_display' => $discDisplay,
                        'total' => $pTotal,
                    ]);
                }
            }
            $displayItems = $consolidatedItems->values();
            $rowCount = count($displayItems);
            
            // Full sampai bawah: hitung tinggi spacer agar garis vertikal mengisi penuh area tengah halaman
            $targetHeightPx = ($paperSize === 'continuous_half') ? 180 : 320;
            $usedHeightPx = $rowCount * 22;
            $spacerHeightPx = max(25, $targetHeightPx - $usedHeightPx);
        @endphp
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 41%;">Nama Barang</th>
                    <th style="width: 12%;">Qty</th>
                    <th style="width: 14%;">Harga</th>
                    <th style="width: 10%;">Disc</th>
                    <th style="width: 18%;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($displayItems as $index => $item)
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td style="font-weight: 800;">{{ $item['name'] }}</td>
                        <td class="center" style="font-weight: 800;">{{ number_format($item['qty'], 2, ',', '.') }} {{ strtoupper($item['unit']) }}</td>
                        <td class="right" style="font-weight: 800;">{{ number_format($item['price'], 2, ',', '.') }}</td>
                        <td class="center" style="font-weight: 800;">{{ $item['disc_display'] }}</td>
                        <td class="right" style="font-weight: 900;">{{ number_format($item['total'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach

                {{-- Spacer Row Full Sampai Bawah: garis vertikal tembus tanpa garis horizontal antar baris --}}
                <tr class="spacer-row last-row">
                    <td style="height: {{ $spacerHeightPx }}px;">&nbsp;</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        {{-- SUBTOTAL / TERBILANG --}}
        <table class="subtotal-table">
            <tr>
                <td style="width: 72%; vertical-align: top;">
                    <span class="label-bold">Terbilang :</span>
                    {{ $terbilangText }}
                </td>
                <td style="width: 28%; text-align: right; vertical-align: top; font-weight: 900; font-size: 11px;">
                    {{ number_format($subtotal > 0 ? $subtotal : $grandTotal, 2, ',', '.') }}
                </td>
            </tr>
        </table>

        {{-- JATUH TEMPO, PERHATIAN | TTD DI TENGAH | TOTAL RP DI KANAN --}}
        <table class="bottom-section">
            <tr>
                {{-- KIRI: Jatuh Tempo & Perhatian --}}
                <td style="width: 48%; vertical-align: top;">
                    <div style="font-size: 10px; margin-bottom: 4px; font-weight: 800;">
                        <span class="label-bold">Jatuh Tempo pada Tanggal :</span>
                        {{ $dueDateObj ? $dueDateObj->translatedFormat('l, d F, Y') : ($invoiceDateObj ? $invoiceDateObj->translatedFormat('l, d F, Y') : '-') }}
                    </div>

                    <table style="width: 100%; font-size: 9px; line-height: 1.25; border-collapse: collapse; font-weight: 700;">
                        <tr>
                            <td style="width: 18%; vertical-align: top; font-weight: 900; padding: 0;">Perhatian</td>
                            <td style="width: 82%; vertical-align: top; padding: 0;">
                                1. Barang-barang yang telah dibeli tidak dapat dikembalikan.<br>
                                2. Pembayaran dengan cek/giro belum berarti lunas sebelum diuangkan.<br>
                                3. CEK/GIRO atas nama : <strong>{{ $bankAccountName }}</strong>.<br>
                                &nbsp;&nbsp;&nbsp;{{ $bankAccountDetails }}
                            </td>
                        </tr>
                    </table>
                </td>

                {{-- TENGAH: Tanda Tangan (Sesuai Arahan User) --}}
                <td style="width: 26%; text-align: center; vertical-align: top;">
                    <div class="signature-block">
                        <div style="font-weight: 900; font-size: 10.5px;">Hormat Kami</div>
                        <div class="signature-space"></div>
                        <div class="signature-name">{{ $signerName }}</div>
                        <div style="font-weight: 800; font-size: 9.5px;">{{ $signerTitle }}</div>
                    </div>
                </td>

                {{-- KANAN: Total Rp --}}
                <td style="width: 26%; vertical-align: top;">
                    <div class="total-box">
                        <div class="total-line">
                            <span>Total Rp.</span>
                            <span>{{ number_format($grandTotal, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        {{-- FOOTER BAWAH --}}
        <table style="width: 100%; margin-top: 6px; font-size: 8.5px; font-weight: 700; border-collapse: collapse; color: #000000;">
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
