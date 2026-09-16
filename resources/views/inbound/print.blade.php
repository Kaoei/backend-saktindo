<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <title>
        Tanda Terima Barang Masuk - {{ $inbound->id }}
    </title>

    <style>
        @page {
            size: A4;
            margin: 12mm 14mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #000;
        }

        .container {
            width: 100%;
        }

        /* HEADER */

        .header {
            text-align: center;
            margin-bottom: 8px;
        }

        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 0.3px;
        }

        .header .number {
            margin-top: 5px;
            font-size: 12px;
        }

        .header-line {
            border-top: 2px solid #000;
            margin-top: 10px;
        }

        /* INFORMATION */

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            margin-bottom: 18px;
        }

        .info-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .info-label {
            width: 105px;
        }

        .info-separator {
            width: 10px;
            text-align: center;
        }

        .info-value {
            font-weight: 700;
        }

        .info-right-label {
            width: 110px;
        }

        .info-right-value {
            font-weight: 700;
        }

        /* ITEMS */

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 7px 6px;
        }

        .items-table th {
            text-align: center;
            font-weight: 700;
            font-size: 11px;
        }

        .items-table td {
            font-size: 11px;
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

        .items-table .name {
            font-weight: 600;
        }

        .col-name {
            width: 24%;
        }

        .col-sku {
            width: 17%;
        }

        .col-qty {
            width: 10%;
        }

        .col-rusak {
            width: 10%;
        }

        .col-kurang {
            width: 10%;
        }

        .col-hpp {
            width: 17%;
        }

        /* SIGNATURE */

        .signature {
            width: 100%;
            margin-top: 65px;
            border-collapse: collapse;
        }

        .signature td {
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-line {
            width: 80%;
            margin: 0 auto 5px auto;
            border-top: 1px solid #000;
            height: 1px;
        }

        .signature-title {
            font-weight: 700;
            font-size: 12px;
        }
    </style>
</head>

<body>

<div class="container">

    {{-- HEADER --}}
    <div class="header">

        <h1>TANDA TERIMA BARANG MASUK</h1>

        <div class="number">
            Nomor:
            <strong>{{ $inbound->id }}</strong>

            &nbsp;•&nbsp;

            Tanggal Cetak:
            <strong>
                {{ now()->format('d/m/Y H:i') }}
            </strong>
        </div>

        <div class="header-line"></div>

    </div>


    {{-- INFORMATION --}}
    <table class="info-table">

        <tr>
            <td class="info-label">
                ID Inbound
            </td>

            <td class="info-separator">
                :
            </td>

            <td class="info-value">
                {{ $inbound->id }}
            </td>

            <td class="info-right-label">
                Supplier
            </td>

            <td class="info-separator">
                :
            </td>

            <td class="info-right-value">
                {{ $inbound->supplier->name ?? '-' }}
            </td>
        </tr>


        <tr>
            <td class="info-label">
                Tanggal Diterima
            </td>

            <td class="info-separator">
                :
            </td>

            <td class="info-value">
                {{ \Carbon\Carbon::parse($inbound->received_date)->format('d/m/Y') }}
            </td>

            <td class="info-right-label">
                No. PO Supplier
            </td>

            <td class="info-separator">
                :
            </td>

            <td class="info-right-value">
                {{ $inbound->supplierPo->id ?? $inbound->supplier_po_id ?? '-' }}
            </td>
        </tr>


        <tr>
            <td class="info-label">
                Status
            </td>

            <td class="info-separator">
                :
            </td>

            <td class="info-value">
                {{ strtoupper($inbound->status ?? 'PENDING') }}
            </td>

            <td class="info-right-label">
                No. Invoice
            </td>

            <td class="info-separator">
                :
            </td>

            <td class="info-right-value">
                {{ $inbound->invoice_number ?? '-' }}
            </td>
        </tr>

    </table>


    {{-- ITEMS --}}
    <table class="items-table">

        <thead>
            <tr>

                <th class="col-name">
                    Nama Barang
                </th>

                <th class="col-sku">
                    SKU
                </th>

                <th class="col-qty">
                    Qty<br>Diterima
                </th>

                <th class="col-rusak">
                    Qty<br>Rusak
                </th>

                <th class="col-kurang">
                    Qty<br>Kurang
                </th>

                <th class="col-hpp">
                    HPP<br>(Rp)
                </th>

            </tr>
        </thead>

        <tbody>

            <tr>

                <td class="text-left">
                    <span class="name">
                        {{ $inbound->supplierProduct->item_name ?? '-' }}
                    </span>
                </td>

                <td class="text-left">
                    {{ $inbound->supplierProduct->sku ?? '-' }}
                </td>

                <td class="text-center">
                    {{ number_format($inbound->qty_received ?? 0, 0, ',', '.') }}
                </td>

                <td class="text-center">
                    {{ number_format($inbound->qty_damaged ?? 0, 0, ',', '.') }}
                </td>

                <td class="text-center">
                    {{ number_format($inbound->qty_missing ?? 0, 0, ',', '.') }}
                </td>

                <td class="text-right">
                    Rp {{ number_format($inbound->hpp ?? 0, 0, ',', '.') }}
                </td>

            </tr>

        </tbody>

    </table>


    {{-- SIGNATURE --}}
    <table class="signature">

        <tr>

            <td>
                <div class="signature-line"></div>

                <div class="signature-title">
                    Dibuat Oleh
                </div>
            </td>


            <td>
                <div class="signature-line"></div>

                <div class="signature-title">
                    Petugas Gudang
                </div>
            </td>


            <td>
                <div class="signature-line"></div>

                <div class="signature-title">
                    Mengetahui
                </div>
            </td>

        </tr>

    </table>

</div>

</body>
</html>