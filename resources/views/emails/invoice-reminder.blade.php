<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemberitahuan Tagihan Invoice</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f6f9;
            color: #333333;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .email-container {
            max-width: 620px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
        .header {
            background-color: #1e293b;
            color: #ffffff;
            padding: 24px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 13px;
            color: #94a3b8;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 15px;
            margin-bottom: 16px;
        }
        .invoice-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 18px;
            margin: 20px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13.5px;
        }
        .info-row:last-child {
            margin-bottom: 0;
        }
        .info-label {
            color: #64748b;
        }
        .info-value {
            font-weight: 600;
            color: #0f172a;
        }
        .highlight-amount {
            font-size: 18px;
            font-weight: 700;
            color: #dc2626;
        }
        .bank-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 16px;
            margin: 20px 0;
        }
        .bank-box h4 {
            margin: 0 0 10px;
            color: #166534;
            font-size: 14px;
        }
        .bank-item {
            font-size: 13px;
            color: #15803d;
            margin-bottom: 6px;
        }
        .footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 18px 30px;
            font-size: 12px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $customerName = $invoice->salesOrder->customer_name ?? $invoice->salesOrders->first()?->customer_name ?? 'Pelanggan Yth.';
        $dueDateFormatted = $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d F Y') : '-';
        $invoiceDateFormatted = $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('d F Y') : '-';
    @endphp

    <div class="email-container">
        <div class="header">
            <h1>Pemberitahuan Tagihan / Invoice</h1>
            <p>PT Saktindo Jayatama Samudera</p>
        </div>

        <div class="content">
            <div class="greeting">
                Kepada Yth. <strong>{{ $customerName }}</strong>,
            </div>

            <p style="font-size: 14px; color: #475569;">
                Bersama email ini, kami menginformasikan rincian tagihan invoice yang belum melakukan pelunasan penuh. Berikut adalah rincian tagihan Anda:
            </p>

            @if(!empty($customMessage))
                <div style="background-color: #eff6ff; border-left: 4px solid #3b82f6; padding: 12px; margin: 15px 0; font-size: 13.5px; color: #1e40af;">
                    <strong>Catatan Khusus:</strong><br>
                    {{ $customMessage }}
                </div>
            @endif

            <div class="invoice-card">
                <table style="width: 100%; border-collapse: collapse; font-size: 13.5px;">
                    <tr>
                        <td style="padding: 5px 0; color: #64748b;">Nomor Invoice</td>
                        <td style="padding: 5px 0; text-align: right; font-weight: 600;">{{ $invoice->invoice_number }}</td>
                    </tr>
                    @if($invoice->faktur_number)
                    <tr>
                        <td style="padding: 5px 0; color: #64748b;">Nomor Faktur</td>
                        <td style="padding: 5px 0; text-align: right; font-weight: 600;">{{ $invoice->faktur_number }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 5px 0; color: #64748b;">Tanggal Invoice</td>
                        <td style="padding: 5px 0; text-align: right; font-weight: 600;">{{ $invoiceDateFormatted }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: #64748b;">Jatuh Tempo</td>
                        <td style="padding: 5px 0; text-align: right; font-weight: 600; color: #b91c1c;">{{ $dueDateFormatted }}</td>
                    </tr>
                    <tr style="border-top: 1px dashed #cbd5e1;">
                        <td style="padding: 8px 0 5px; color: #64748b;">Total Tagihan</td>
                        <td style="padding: 8px 0 5px; text-align: right; font-weight: 600;">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: #64748b;">Sudah Terbayar</td>
                        <td style="padding: 5px 0; text-align: right; font-weight: 600; color: #16a34a;">Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr style="border-top: 1px solid #cbd5e1;">
                        <td style="padding: 8px 0 0; font-weight: 700; color: #0f172a;">Sisa Tagihan (Outstanding)</td>
                        <td style="padding: 8px 0 0; text-align: right;" class="highlight-amount">Rp {{ number_format($invoice->outstanding_amount, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>

            @if(isset($rekenings) && $rekenings->count() > 0)
                <div class="bank-box">
                    <h4>Informasi Rekening Pembayaran:</h4>
                    @foreach($rekenings as $rek)
                        <div class="bank-item">
                            <strong>{{ $rek->bank_name }}</strong>: {{ $rek->account_number }} a/n {{ $rek->account_name }}
                        </div>
                    @endforeach
                </div>
            @endif

            <p style="font-size: 13.5px; color: #475569;">
                Mohon untuk segera melakukan pelunasan sebelum atau pada tanggal jatuh tempo. Apabila Anda telah melakukan pembayaran, mohon abaikan email ini atau kirimkan bukti transfer kepada kami.
            </p>

            <p style="font-size: 13.5px; color: #475569; margin-top: 20px;">
                Terima kasih atas kerjasama dan kepercayaannya.<br>
                <strong>Finance & Billing Department</strong>
            </p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} PT Saktindo Jayatama Samudera. Semua hak dilindungi.
        </div>
    </div>
</body>
</html>
