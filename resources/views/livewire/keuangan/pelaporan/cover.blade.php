<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 0;
            padding: 0;
        }
        .content-middle {
            /* Padding untuk mendorong konten ke tengah area body Snappy */
            padding-top: 60mm;
        }
        .report-title {
            font-size: 36pt;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .report-subtitle {
            font-size: 18pt;
            color: #333;
            margin-top: 10px;
        }
        .report-period {
            font-size: 16pt;
            font-weight: bold;
            margin-top: 50px;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            display: inline-block;
            padding: 10px 30px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="content-middle">
        <div class="report-title">LAPORAN KEUANGAN</div>
        <div class="report-subtitle">Unit Usaha BUMDes</div>
        <div class="report-period">PERIODE: {{ $periode }}</div>
    </div>
</body>
</html>
