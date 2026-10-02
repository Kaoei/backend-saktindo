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
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Faktur {{ $invoiceNumber }}</title>
    <style>
        @page {
            margin: 15px 25px 15px 25px;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #000;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .header-table td {
            vertical-align: top;
            padding: 0;
        }

        .company-name {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .company-address {
            font-size: 9px;
            line-height: 1.3;
        }

        .faktur-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            text-decoration: underline;
            padding-top: 4px;
            letter-spacing: 1px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3px;
        }

        .info-table td {
            padding: 1px 2px;
            font-size: 10px;
            vertical-align: top;
        }

        .customer-box {
            border: 1px solid #000;
            width: 100%;
            min-height: 48px;
            text-align: left;
            padding: 4px 6px;
            font-size: 9.5px;
            line-height: 1.35;
        }

        .customer-name {
            font-weight: bold;
            font-size: 10.5px;
            margin-bottom: 2px;
        }

        .ref-table {
            width: 100%;
            margin-top: 6px;
            border-collapse: collapse;
        }

        .ref-table td {
            padding: 2px 0;
            font-size: 10px;
        }

        .label-bold {
            font-weight: bold;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 4px 5px;
            font-size: 9.5px;
        }

        .items-table th {
            font-weight: bold;
            text-align: center;
            background: #fff;
        }

        .items-table td.center {
            text-align: center;
        }

        .items-table td.right {
            text-align: right;
        }

        .items-table .empty-row td {
            height: 18px;
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
            margin-top: 4px;
            border-collapse: collapse;
        }

        .footer-table td {
            vertical-align: top;
            font-size: 10px;
            padding: 2px 0;
        }

        .signature-block {
            text-align: center;
            margin-top: 6px;
        }

        .signature-space {
            height: 42px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <table class="header-table">
        <tr>
            <td style="width: 40%;">
                @if(!empty($logoBase64))
                    <div style="margin-bottom: 3px;">
                        <img src="{{ $logoBase64 }}" style="max-height: 38px; max-width: 200px; object-fit: contain;">
                    </div>
                @endif
                <div class="company-name">PT. SAKTINDO JAYA BERSAMA</div>
                <div class="company-address">
                    PASAR KENARI ALO D AKS 110<br>
                    JL. SALEMBA RAYA<br>
                    SENEN , JAKARTA PUSAT-10430
                </div>
            </td>
            <td style="width: 25%; text-align: center;">
                <div class="faktur-title">FAKTUR</div>
            </td>
            <td style="width: 35%;">
                <table class="info-table">
                    <tr>
                        <td style="width: 35%; font-weight: bold;">Tanggal</td>
                        <td style="width: 65%;">
                            {{ $invoiceDateObj ? $invoiceDateObj->translatedFormat('d-F-Y') : now()->translatedFormat('d-F-Y') }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="font-weight: bold; padding-top: 2px;">Kepada Yth :</td>
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
        $emptyRows = max(0, 5 - $rowCount);
    @endphp
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 40%;">Nama Barang</th>
                <th style="width: 13%;">Qty</th>
                <th style="width: 14%;">Harga</th>
                <th style="width: 9%;">Disc</th>
                <th style="width: 20%;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($displayItems as $index => $item)
                @php
                    $pName = is_array($item) ? ($item['name'] ?? $item['product_name'] ?? '') : ($item->product_name ?? '');
                    $pQty = (float) (is_array($item) ? ($item['qty'] ?? $item['quantity'] ?? 0) : ($item->quantity ?? 0));
                    $pUnit = is_array($item) ? ($item['unit'] ?? 'PCS') : ($item->unit ?? 'PCS');
                    $rawPrice = is_array($item) ? ($item['price'] ?? $item['unit_price'] ?? 0) : ($item->unit_price ?? 0);
                    $pPrice = (float) str_replace(['.', ','], ['', '.'], (string) $rawPrice);

                    $d1 = (float) (is_array($item) ? ($item['discount_1'] ?? 0) : ($item->discount_1 ?? $item->discount ?? 0));
                    $d2 = (float) (is_array($item) ? ($item['discount_2'] ?? 0) : ($item->discount_2 ?? 0));

                    $discs = [];
                    if ($d1 > 0) $discs[] = rtrim(rtrim(number_format($d1, 2), '0'), '.');
                    if ($d2 > 0) $discs[] = rtrim(rtrim(number_format($d2, 2), '0'), '.');
                    $discDisplay = !empty($discs) ? implode('+ ', $discs) : '0+ 0';

                    $pTotal = is_array($item) && isset($item['line_total']) && $item['line_total'] !== ''
                        ? (float) str_replace(['.', ','], ['', '.'], (string) $item['line_total'])
                        : ($pPrice * (1 - $d1/100) * (1 - $d2/100) * $pQty);
                    if ($pTotal <= 0 && !is_array($item)) {
                        $pTotal = (float) ($item->line_total ?? ($pPrice * $pQty));
                    }
                @endphp
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $pName }}</td>
                    <td class="center">{{ number_format($pQty, 2, ',', '.') }} {{ strtoupper($pUnit) }}</td>
                    <td class="right">{{ number_format($pPrice, 2, ',', '.') }}</td>
                    <td class="center">{{ $discDisplay }}</td>
                    <td class="right">{{ number_format($pTotal, 2, ',', '.') }}</td>
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
    <table class="footer-table" style="margin-top: 6px;">
        <tr>
            <td style="width: 70%; vertical-align: top;">
                <span class="label-bold">Terbilang :</span>
                {{ $terbilangText }}
            </td>
            <td style="width: 30%; text-align: right; vertical-align: top; font-weight: bold; font-size: 11px;">
                {{ number_format($subtotal > 0 ? $subtotal : $grandTotal, 2, ',', '.') }}
            </td>
        </tr>
    </table>

    {{-- JATUH TEMPO, PERHATIAN, HORMAT KAMI, DAN TOTAL RP --}}
    <table class="footer-table" style="margin-top: 8px;">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                <div style="font-size: 10.5px; margin-bottom: 8px;">
                    <span class="label-bold">Jatuh Tempo pada Tanggal :</span>
                    {{ $dueDateObj ? $dueDateObj->translatedFormat('l, d F, Y') : ($invoiceDateObj ? $invoiceDateObj->translatedFormat('l, d F, Y') : '-') }}
                </div>

                <table style="width: 100%; font-size: 9px; line-height: 1.45; border-collapse: collapse;">
                    <tr>
                        <td style="width: 17%; vertical-align: top; font-weight: bold; padding: 0;">Perhatian</td>
                        <td style="width: 83%; vertical-align: top; padding: 0;">
                            1. Barang-barang yang telah dibeli tidak dapat dikembalikan.<br>
                            2. Pembayaran dengan cek/giro belum berarti lunas sebelum diuangkan.<br>
                            3. CEK/GIRO atas nama : PT. SAKTINDO JAYA BERSAMA. BCA KENARI . REK NO. 068.3055678
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 40%; vertical-align: top;">
                <div class="signature-block">
                    Hormat Kami
                    <div class="signature-space"></div>
                    <div class="signature-name">{{ $signerName }}</div>
                    <div style="font-weight: bold; font-size: 9.5px;">{{ $signerTitle }}</div>
                </div>
                <table style="width: 100%; margin-top: 8px;">
                    <tr>
                        <td style="width: 35%; font-weight: bold; font-size: 11.5px; padding: 4px 0;">Total Rp.</td>
                        <td style="width: 65%; text-align: right; font-weight: bold; font-size: 11.5px; border-bottom: 2px solid #000; padding: 4px 0;">
                            {{ number_format($grandTotal, 2, ',', '.') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- FOOTER BAWAH --}}
    <table style="width: 100%; margin-top: 18px; font-size: 8px; border-collapse: collapse;">
        <tr>
            <td style="width: 75%; text-align: left;">
                User : {{ $userName }}, Tgl & Jam Cetak : {{ now()->format('d/m/Y H:i:s') }} WIB ,Komp : {{ $computerName }}
            </td>
            <td style="width: 25%; text-align: right;">
                Page 1 of 1
            </td>
        </tr>
    </table>

</body>
</html>