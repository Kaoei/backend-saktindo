<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <title>Faktur Penjualan - {{ $supplierPo->po_number }}</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 7mm 8mm 8mm 8mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
        }

        .page {
            width: 100%;
        }

        /* =========================================================
           HEADER
        ========================================================= */

        .top-header {
            width: 100%;
            display: table;
            table-layout: fixed;
            margin-bottom: 4px;
        }

        .top-left {
            display: table-cell;
            width: 58%;
            vertical-align: top;
            padding-right: 10px;
        }

        .top-right {
            display: table-cell;
            width: 42%;
            vertical-align: top;
        }

        /* =========================================================
           NAMA SUPPLIER / PERUSAHAAN
        ========================================================= */

        .company-name {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 2px;
            margin-bottom: 3px;
        }

        .company-address {
            font-size: 8px;
            line-height: 1.4;
        }

        /* =========================================================
           JUDUL
        ========================================================= */

        .invoice-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        /* =========================================================
           INFO FAKTUR
        ========================================================= */

        .invoice-info {
            width: 100%;
            border: 1px solid #777;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .invoice-info td {
            padding: 3px 4px;
            height: 19px;
            vertical-align: middle;
            font-size: 8px;
        }

        .info-label-left {
            width: 24%;
            white-space: nowrap;
        }

        .info-colon-left {
            width: 3%;
            text-align: center;
        }

        .info-value-left {
            width: 22%;
            white-space: nowrap;
        }

        .info-label-right {
            width: 18%;
            white-space: nowrap;
        }

        .info-colon-right {
            width: 3%;
            text-align: center;
        }

        .info-value-right {
            width: 30%;
            white-space: nowrap;
        }

        /* =========================================================
           KEPADA / ALAMAT
        ========================================================= */

        .customer-box {
            width: 100%;
            min-height: 55px;
            border: 1px dashed #777;
            padding: 5px 7px;
            margin-bottom: 5px;
        }

        .customer-title {
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .customer-name {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .customer-address {
            font-size: 8px;
            line-height: 1.35;
        }

        /* =========================================================
           TABEL BARANG
        ========================================================= */

        .items {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .items th {
            border: 1px solid #333;
            background: #f2f2f2;
            text-align: center;
            font-size: 8px;
            font-weight: bold;
            padding: 4px 2px;
            height: 26px;
            vertical-align: middle;
        }

        .items td {
            border-left: 1px solid #333;
            border-right: 1px solid #333;
            border-bottom: 1px solid #333;
            font-size: 8px;
            padding: 4px 3px;
            height: 29px;
            vertical-align: middle;
        }

        .text-left {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        /* =========================================================
           LEBAR KOLOM
        ========================================================= */

        .col-no {
            width: 3.5%;
        }

        .col-code {
            width: 17%;
        }

        .col-name {
            width: 28%;
        }

        .col-qty {
            width: 6%;
        }

        .col-unit {
            width: 6%;
        }

        .col-price {
            width: 11%;
        }

        .col-discount {
            width: 9%;
        }

        .col-total {
            width: 14%;
        }

        .col-wh {
            width: 5.5%;
        }

        /* =========================================================
           BAGIAN BAWAH
        ========================================================= */

        .bottom-area {
            width: 100%;
            display: table;
            table-layout: fixed;
            margin-top: 4px;
        }

        .bottom-left {
            display: table-cell;
            width: 61%;
            vertical-align: top;
            padding-right: 15px;
        }

        .bottom-right {
            display: table-cell;
            width: 39%;
            vertical-align: top;
        }

        /* =========================================================
           TERBILANG
        ========================================================= */

        .terbilang {
            font-size: 8px;
            margin-top: 2px;
            margin-bottom: 10px;
        }

        .terbilang-label {
            font-weight: bold;
        }

        /* =========================================================
           KETERANGAN
        ========================================================= */

        .notes {
            font-size: 8px;
            line-height: 1.4;
        }

        .notes-label {
            font-weight: bold;
            margin-bottom: 3px;
        }

        /* =========================================================
           TOTAL
        ========================================================= */

        .totals {
            width: 100%;
            border-collapse: collapse;
        }

        .totals td {
            font-size: 8px;
            padding: 3px 4px;
            height: 18px;
        }

        .total-label {
            width: 55%;
            text-align: left;
        }

        .total-value {
            width: 45%;
            text-align: right;
            white-space: nowrap;
        }

        .grand-total td {
            border-top: 1px solid #222;
            padding-top: 5px;
            font-size: 10px;
            font-weight: bold;
        }

        /* =========================================================
           TANDA TANGAN
        ========================================================= */

        .signature {
            width: 100%;
            display: table;
            table-layout: fixed;
            margin-top: 26px;
        }

        .signature-left,
        .signature-right {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 8px;
        }

        .signature-space {
            height: 45px;
        }

        /* =========================================================
           FOOTER
        ========================================================= */

        .footer {
            width: 100%;
            border-top: 1px solid #333;
            margin-top: 6px;
            padding-top: 3px;
            font-size: 7px;
        }

        /* =========================================================
           PAGE NUMBER
        ========================================================= */

        .page-number {
            text-align: right;
            font-size: 7px;
            margin-top: 3px;
        }
    </style>
</head>

<body>

<div class="page">

    {{-- =========================================================
         HEADER
    ========================================================== --}}

    <div class="top-header">

        {{-- =========================
             KIRI
        ========================== --}}

        <div class="top-left">

            <div class="company-name">
                {{ $supplierPo->supplier->name ?? 'NAMA SUPPLIER' }}
            </div>

            <div class="company-address">

                @if(!empty($supplierPo->supplier->phone))
                    Telp. {{ $supplierPo->supplier->phone }}
                @endif

                @if(!empty($supplierPo->supplier->email))
                    <br>
                    {{ $supplierPo->supplier->email }}
                @endif

                @if(!empty($supplierPo->supplier->address))
                    <br>
                    {{ $supplierPo->supplier->address }}
                @endif

            </div>

        </div>


        {{-- =========================
             KANAN
        ========================== --}}

        <div class="top-right">

            <div class="invoice-title">
                Faktur Penjualan
            </div>

            <table class="invoice-info">

                {{-- =================================================
                     BARIS 1
                ================================================== --}}

                <tr>

                    <td class="info-label-left">
                        Tanggal Faktur
                    </td>

                    <td class="info-colon-left">
                        :
                    </td>

                    <td class="info-value-left">

                        @if(!empty($supplierPo->invoice_date))

                            {{ \Carbon\Carbon::parse(
                                $supplierPo->invoice_date
                            )->format('d M Y') }}

                        @else

                            __________

                        @endif

                    </td>


                    <td class="info-label-right">
                        Faktur No.
                    </td>

                    <td class="info-colon-right">
                        :
                    </td>

                    <td class="info-value-right">

                        {{ $supplierPo->invoice_number ?? '__________' }}

                    </td>

                </tr>


                {{-- =================================================
                     BARIS 2
                ================================================== --}}

                <tr>

                    <td class="info-label-left">
                        Tgl Jatuh Tempo
                    </td>

                    <td class="info-colon-left">
                        :
                    </td>

                    <td class="info-value-left">

                        @if(!empty($supplierPo->due_date))

                            {{ \Carbon\Carbon::parse(
                                $supplierPo->due_date
                            )->format('d M Y') }}

                        @else

                            __________

                        @endif

                    </td>


                    <td class="info-label-right">
                        Tgl Kirim
                    </td>

                    <td class="info-colon-right">
                        :
                    </td>

                    <td class="info-value-right">

                        @if(!empty($supplierPo->received_date))

                            {{ \Carbon\Carbon::parse(
                                $supplierPo->received_date
                            )->format('d M Y') }}

                        @else

                            __________

                        @endif

                    </td>

                </tr>


                {{-- =================================================
                     BARIS 3
                ================================================== --}}

                <tr>

                    <td class="info-label-left">
                        PO No.
                    </td>

                    <td class="info-colon-left">
                        :
                    </td>

                    <td class="info-value-left">

                        {{ $supplierPo->po_number }}

                    </td>


                    <td class="info-label-right">
                        No. SJ
                    </td>

                    <td class="info-colon-right">
                        :
                    </td>

                    <td class="info-value-right">

                        {{ $supplierPo->surat_jalan_number ?? '__________' }}

                    </td>

                </tr>


                {{-- =================================================
                     BARIS 4
                ================================================== --}}

                <tr>

                    <td class="info-label-left">
                        Sales
                    </td>

                    <td class="info-colon-left">
                        :
                    </td>

                    <td class="info-value-left">

                        {{ $supplierPo->sales_name ?? '__________' }}

                    </td>


                    <td class="info-label-right">
                        Exp
                    </td>

                    <td class="info-colon-right">
                        :
                    </td>

                    <td class="info-value-right">

                        {{ $supplierPo->exp ?? '__________' }}

                    </td>

                </tr>

            </table>

        </div>

    </div>


    {{-- =========================================================
         KEPADA
    ========================================================== --}}

    <div class="customer-box">

        <div class="customer-title">
            KEPADA :
        </div>

        <div class="customer-name">
            PT. SAKTINDO JAYA BERSAMA
        </div>

        <div class="customer-address">
            Pasar Kenari Lantai 2, Jl. Salemba Raya, Jakarta Pusat
        </div>

    </div>


    {{-- =========================================================
         TABEL BARANG
    ========================================================== --}}

    <table class="items">

        <thead>

            <tr>

                <th class="col-no">
                    No.
                </th>

                <th class="col-code">
                    Kode Barang
                </th>

                <th class="col-name">
                    Nama Barang
                </th>

                <th class="col-qty">
                    Qty
                </th>

                <th class="col-unit">
                    Unit
                </th>

                <th class="col-price">
                    Harga
                </th>

                <th class="col-discount">
                    Diskon
                </th>

                <th class="col-total">
                    Total Harga
                </th>

                <th class="col-wh">
                    WH
                </th>

            </tr>

        </thead>


        <tbody>

            @forelse($supplierPo->items as $index => $item)

                @php

                    $qty = (float) ($item->qty ?? 0);

                    $price = (float) ($item->price ?? 0);

                    $d1 = (float) (
                        $item->discount_1
                        ?? $item->discount
                        ?? 0
                    );

                    $d2 = (float) (
                        $item->discount_2
                        ?? 0
                    );

                    $d3 = (float) (
                        $item->discount_3
                        ?? 0
                    );

                    $d4 = (float) (
                        $item->discount_4
                        ?? 0
                    );


                    /*
                     * Perhitungan diskon bertingkat
                     */
                    $netPrice = $price;

                    $netPrice =
                        $netPrice
                        * (1 - ($d1 / 100))
                        * (1 - ($d2 / 100))
                        * (1 - ($d3 / 100))
                        * (1 - ($d4 / 100));


                    $lineTotal = $netPrice * $qty;


                    /*
                     * Tampilkan diskon
                     */
                    $discountParts = [];

                    if ($d1 > 0) {
                        $discountParts[] = $d1 . '%';
                    }

                    if ($d2 > 0) {
                        $discountParts[] = $d2 . '%';
                    }

                    if ($d3 > 0) {
                        $discountParts[] = $d3 . '%';
                    }

                    if ($d4 > 0) {
                        $discountParts[] = $d4 . '%';
                    }

                    $discountText = count($discountParts)
                        ? implode(' + ', $discountParts)
                        : '-';

                @endphp


                <tr>

                    {{-- NO --}}
                    <td class="text-center">
                        {{ $index + 1 }}
                    </td>


                    {{-- KODE BARANG --}}
                    <td class="text-left">
                        {{ $item->supplierProduct->sku ?? '-' }}
                    </td>


                    {{-- NAMA BARANG --}}
                    <td class="text-left">
                        {{ $item->supplierProduct->item_name ?? '-' }}
                    </td>


                    {{-- QTY --}}
                    <td class="text-center">
                        {{ number_format($qty, 0, ',', '.') }}
                    </td>


                    {{-- UNIT --}}
                    <td class="text-center">

                        {{ strtoupper(
                            $item->supplierProduct->unit
                            ?? 'PCS'
                        ) }}

                    </td>


                    {{-- HARGA --}}
                    <td class="text-right">

                        {{ number_format(
                            $price,
                            2,
                            ',',
                            '.'
                        ) }}

                    </td>


                    {{-- DISKON --}}
                    <td class="text-center">

                        {{ $discountText }}

                    </td>


                    {{-- TOTAL --}}
                    <td class="text-right">

                        {{ number_format(
                            $lineTotal,
                            2,
                            ',',
                            '.'
                        ) }}

                    </td>


                    {{-- WH --}}
                    <td class="text-center">

                        {{ $item->warehouse_code ?? 'OKG' }}

                    </td>

                </tr>

            @empty

                <tr>

                    <td
                        colspan="9"
                        class="text-center"
                    >
                        Tidak ada barang.
                    </td>

                </tr>

            @endforelse

        </tbody>

    </table>


    {{-- =========================================================
         TERBILANG + TOTAL
    ========================================================== --}}

    <div class="bottom-area">

        {{-- =========================
             KIRI
        ========================== --}}

        <div class="bottom-left">

            <div class="terbilang">

                <span class="terbilang-label">
                    Terbilang :
                </span>

                {{ $supplierPo->terbilang ?? '-' }}

            </div>


            <div class="notes">

                <div class="notes-label">
                    Keterangan :
                </div>

                {{ $supplierPo->notes ?? '-' }}

            </div>

        </div>


        {{-- =========================
             KANAN
        ========================== --}}

        <div class="bottom-right">

            <table class="totals">

                {{-- SUB TOTAL --}}
                <tr>

                    <td class="total-label">
                        Sub Total
                    </td>

                    <td class="total-value">

                        Rp
                        {{ number_format(
                            $supplierPo->subtotal
                            ?? $supplierPo->total_amount
                            ?? 0,
                            2,
                            ',',
                            '.'
                        ) }}

                    </td>

                </tr>


                {{-- DPP --}}
                <tr>

                    <td class="total-label">
                        DPP (11%)
                    </td>

                    <td class="total-value">

                        Rp
                        {{ number_format(
                            $supplierPo->dpp ?? 0,
                            2,
                            ',',
                            '.'
                        ) }}

                    </td>

                </tr>


                {{-- VAT --}}
                <tr>

                    <td class="total-label">
                        VAT
                    </td>

                    <td class="total-value">

                        Rp
                        {{ number_format(
                            $supplierPo->tax_amount ?? 0,
                            2,
                            ',',
                            '.'
                        ) }}

                    </td>

                </tr>


                {{-- GRAND TOTAL --}}
                <tr class="grand-total">

                    <td class="total-label">
                        Grand Total
                    </td>

                    <td class="total-value">

                        Rp
                        {{ number_format(
                            $supplierPo->total_amount ?? 0,
                            2,
                            ',',
                            '.'
                        ) }}

                    </td>

                </tr>

            </table>

        </div>

    </div>


    {{-- =========================================================
         TANDA TANGAN
    ========================================================== --}}

    <div class="signature">

        <div class="signature-left">

            <strong>
                {{ $supplierPo->supplier->name ?? 'SUPPLIER' }}
            </strong>

            <div class="signature-space"></div>

            (................................)

        </div>


        <div class="signature-right">

            <strong>
                PT. SAKTINDO JAYA BERSAMA
            </strong>

            <div class="signature-space"></div>

            (................................)

        </div>

    </div>


    {{-- =========================================================
         FOOTER
    ========================================================== --}}

    <div class="footer">

        Dokumen faktur pembelian berdasarkan Supplier PO:
        <strong>{{ $supplierPo->po_number }}</strong>

    </div>

    
    <div class="page-number">
        Halaman 1 dari 1
    </div>

</div>

</body>
</html>