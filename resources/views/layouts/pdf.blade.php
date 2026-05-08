<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $title ?? 'Laporan' }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            margin: 0;
            letter-spacing: 0.3px;
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

        /* ============ SIGNATURE ============ */
        .signature-area table, 
        .signature-area th, 
        .signature-area td {
            border: 0 !important;
        }
    </style>
</head>

<body>
    {{-- JUDUL LAPORAN --}}
    <div class="report-title">
        <h2>{{ $title }}</h2>
        @if (isset($subtitle))
            <p>{{ $subtitle }}</p>
        @endif
    </div>

    {{-- KONTEN LAPORAN --}}
    <div class="content">
        @yield('content')
    </div>

    {{-- SIGNATURE LAPORAN --}}
    @if(isset($business) && !empty($business->signature_laporan))
        <div class="signature-area" style="margin-top: 40px; page-break-inside: avoid;">
            {!! $business->signature_laporan !!}
        </div>
    @endif
</body>

</html>
