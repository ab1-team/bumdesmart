<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $title ?? 'Laporan' }}</title>
    <style>
        @page {
            margin: 30mm 15mm 20mm 15mm;
        }

        body {
            font-family: sans-serif;
            font-size: 10pt;
            margin: 0;
        }

        /* ============ KOP SURAT (fixed di setiap halaman) ============ */
        .kop {
            position: fixed;
            top: -25mm;
            left: 0;
            right: 0;
            height: 20mm;
            border-bottom: 3px double #333;
            padding-bottom: 5px;
        }

        .kop-table {
            width: 100%;
            border: none;
            border-collapse: collapse;
        }

        .kop-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .kop-logo {
            width: 70px;
            text-align: center;
        }

        .kop-logo img {
            width: 55px;
            height: 55px;
        }

        .kop-text {
            padding-left: 10px;
        }

        .kop-nama {
            font-size: 16pt;
            font-weight: bold;
            color: #222;
            margin: 0;
            letter-spacing: 1px;
        }

        .kop-alamat {
            font-size: 9pt;
            color: #555;
            margin: 2px 0 0 0;
            line-height: 1.4;
        }

        /* ============ JUDUL LAPORAN ============ */
        .report-title {
            text-align: center;
            margin-bottom: 15px;
        }

        .report-title h2 {
            margin: 0;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .report-title p {
            margin: 3px 0 0 0;
            font-size: 9pt;
            color: #666;
        }

        /* ============ TABLE ============ */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 9pt;
        }

        table,
        th,
        td {
            border: 1px solid #ccc;
        }

        th {
            background-color: #f0f0f0;
            padding: 6px 8px;
            text-align: left;
            font-weight: bold;
        }

        td {
            padding: 5px 8px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* ============ BADGE ============ */
        .badge {
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8pt;
            color: white;
            background-color: #666;
        }

        .badge-success {
            background-color: #28a745;
        }

        .badge-warning {
            background-color: #ffc107;
            color: black;
        }

        .badge-danger {
            background-color: #dc3545;
        }

        /* ============ FOOTER (fixed di setiap halaman) ============ */
        .footer {
            position: fixed;
            bottom: -15mm;
            left: 0;
            right: 0;
            font-size: 8pt;
            text-align: right;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }

        .page-number:before {
            content: "Hal. " counter(page);
        }
    </style>
</head>

<body>
    {{-- KOP SURAT --}}
    <div class="kop">
        <table class="kop-table">
            <tr>
                @php
                    // Pastikan ambil data dari database secara aman
                    $business = $business ?? \App\Models\Business::with('owner')->find(auth()->user()?->business_id);
                    if (!$business) {
                        $business = \App\Models\Business::with('owner')->first();
                    }
                    $owner = $business?->owner ?? \App\Models\Owner::first();
                    
                    $namaUsaha = $business?->nama_usaha ?? ($owner?->nama_usaha ?? env('APP_NAME', 'BUMDes Smart'));
                    $alamatUsaha = $business?->alamat ?? '';
                    $telpUsaha = $business?->no_telp ?? '';
                    $emailUsaha = $business?->email ?? '';

                    $logoPath = $owner && $owner->logo ? storage_path('app/public/' . $owner->logo) : null;
                    $base64Logo = null;
                    if ($logoPath && file_exists($logoPath)) {
                        $type = pathinfo($logoPath, PATHINFO_EXTENSION);
                        $data = file_get_contents($logoPath);
                        $base64Logo = 'data:image/' . $type . ';base64,' . base64_encode($data);
                    }
                @endphp
                @if ($base64Logo)
                    <td class="kop-logo">
                        <img src="{{ $base64Logo }}" alt="Logo">
                    </td>
                @endif
                <td class="kop-text">
                    <p class="kop-nama">{{ $namaUsaha }}</p>
                    <p class="kop-alamat">
                        {{ $alamatUsaha }}
                        @if ($telpUsaha)
                            <br>Telp: {{ $telpUsaha }}
                        @endif
                        @if ($emailUsaha)
                            | Email: {{ $emailUsaha }}
                        @endif
                    </p>
                </td>
            </tr>
        </table>
    </div>

    {{-- JUDUL LAPORAN --}}
    <div class="report-title">
        <h2>{{ $title }}</h2>
        @if (isset($subtitle))
            <p>{{ $subtitle }}</p>
        @endif
    </div>

    {{-- FOOTER --}}
    <div class="footer">
        Dicetak pada: {{ date('d/m/Y H:i') }} | <span class="page-number"></span>
    </div>

    {{-- KONTEN LAPORAN --}}
    <div class="content">
        @yield('content')
    </div>
</body>

</html>
