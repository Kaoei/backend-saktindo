<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penawaran - {{ $proposal->title }}</title>

    <!-- Fix iOS Dark Mode -->
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">

    <style>
        :root {
            color-scheme: light;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4 !important;
            margin: 0;
            padding: 20px;
            -webkit-text-size-adjust: 100%;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff !important;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        /* ===== HEADER BARU ===== */
        .header {
            background: linear-gradient(135deg, #751204 0%, #a31606 100%) !important;
            padding: 40px 20px;
            text-align: center;
            color: #ffffff !important;
            position: relative;
        }

        .header img {
            max-width: 160px;
            margin-bottom: 12px;
        }

        .header-title {
            font-size: 20px;
            font-weight: bold;
            margin: 8px 0 0;
            letter-spacing: 1px;
                      color: #ffffff !important;

        }

        .header-subtitle {
            font-size: 13px;
            opacity: 0.9;
            margin-top: 6px;
                        color: #ffffff !important;
        }

        .header::after {
            content: "";
            display: block;
            width: 60px;
            height: 3px;
            background: #ffffff;
            margin: 18px auto 0;
            border-radius: 2px;
        }

        /* ===== BODY ===== */
        .body {
            padding: 30px;
        }

        .body h2 {
            color: #751204 !important;
            margin-top: 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eeeeee;
            font-size: 14px;
        }

        .detail-label {
            color: #555555;
            font-weight: bold;
            width: 40%;
        }

        .detail-value {
            color: #222222;
            width: 58%;
            text-align: right;
        }

        .amount-box {
            background-color: #f3eaea !important;
            border-left: 4px solid #751204;
            padding: 18px 20px;
            margin: 20px 0;
            border-radius: 6px;
        }

        .amount-box .label {
            font-size: 13px;
            color: #555;
            margin: 0;
        }

        .amount-box .value {
            font-size: 22px;
            font-weight: bold;
            color: #751204 !important;
            margin: 5px 0 0;
        }

        .desc-box {
            background-color: #fafafa !important;
            border: 1px solid #e0e0e0;
            padding: 15px;
            border-radius: 6px;
            font-size: 14px;
            line-height: 1.6;
        }

        /* ===== FOOTER ===== */
        .footer {
            background-color: #f5f5f5 !important;
            padding: 20px 30px;
            text-align: center;
            font-size: 13px;
            color: #777777 !important;
            border-top: 1px solid #e0e0e0;
        }

        /* Extra protection for iOS dark mode */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #f4f4f4 !important;
            }
            .container {
                background-color: #ffffff !important;
            }
            .header{
                color: #ffffff !important;
            }
        }
    </style>
</head>

<body>
    <div class="container">

        <!-- HEADER -->
        <div class="header">
            <img src="{{ asset('src/img/gapuraWhite.png') }}"
                 width="160"
                 style="display:block;margin:0 auto;max-width:160px;"
                 alt="Gapura Logo">
            <div class="header-title">Penawaran Resmi</div>
            <div class="header-subtitle">Lorem ipsum dolor sit amet consectetur.</div>
        </div>

        <!-- BODY -->
        <div class="body">
            <h2>{{ $proposal->title }}</h2>

            <p>Yth. Bapak/Ibu <strong>{{ $proposal->client->name }}</strong>,</p>
            <p>Berikut kami sampaikan penawaran layanan kami kepada Anda:</p>

            <div class="detail-row">
                <span class="detail-label">No. Penawaran</span>
                <span class="detail-value">
                    {{ $proposal->id }}
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Tanggal Penawaran</span>
                <span class="detail-value">
                    {{ $proposal->created_at->translatedFormat('d F Y') }}
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Penanggung Jawab</span>
                <span class="detail-value">
                    {{ $proposal->creator->name }}
                </span>
            </div>

            <div class="amount-box">
                <p class="label">Total Penawaran</p>
                <p class="value">
                    Rp {{ number_format($proposal->amount, 0, ',', '.') }}
                </p>
            </div>

            <p><strong>Deskripsi Layanan:</strong></p>
            <div class="desc-box">
                {!! nl2br(e($proposal->description)) !!}
            </div>

            @if($proposal->notes)
                <p style="margin-top:16px;"><strong>Catatan:</strong></p>
                <div class="desc-box">
                    {!! nl2br(e($proposal->notes)) !!}
                </div>
            @endif

            <p style="margin-top:24px;">
                Apabila ada pertanyaan, silakan hubungi tim Sales kami.
                Kami akan senang membantu Anda.
            </p>

            <p>
                Hormat kami,<br>
                <strong>Tim Gapura</strong>
            </p>
        </div>

        <!-- FOOTER -->
        <div class="footer">
            &copy; {{ date('Y') }} Gapura.
        </div>

    </div>
</body>
</html>