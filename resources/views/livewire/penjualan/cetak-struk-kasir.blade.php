<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Tutup Kasir #{{ $cashDrawer->id }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000;
            margin: 0;
            padding: 10px;
            width: 100%;
            max-width: 58mm;
        }

        h3 {
            margin: 0;
            font-size: 14px;
            text-align: center;
        }

        p {
            margin: 2px 0;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .fw-bold {
            font-weight: bold;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .double-divider {
            border-top: 2px dashed #000;
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 2px 0;
            vertical-align: top;
        }

        @media print {
            @page {
                size: 58mm auto;
                margin: 0;
            }

            body {
                width: 58mm;
                margin: 0;
                padding: 0;
            }

            .d-print-none {
                display: none;
            }
        }
    </style>
</head>

<body onload="window.print()">
    <div>
        <div class="d-print-none" style="margin-bottom: 10px; text-align: center;">
            <button onclick="window.print()" style="padding: 5px 10px; cursor: pointer;">Cetak</button>
            <button onclick="window.close()" style="padding: 5px 10px; cursor: pointer;">Tutup</button>
        </div>

        <div class="receipt-header text-center">
            <h3>LAPORAN TUTUP KASIR</h3>
            <p>{{ env('APP_NAME', 'BUMDESMART') }}</p>
            <p>{{ $owner->alamat ?? '' }}</p>
        </div>

        <div class="divider"></div>

        <table>
            <tr>
                <td class="text-left">Kasir</td>
                <td class="text-right">{{ $cashDrawer->user->nama_lengkap }}</td>
            </tr>
            <tr>
                <td class="text-left">Buka</td>
                <td class="text-right">{{ $cashDrawer->tanggal_buka->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <td class="text-left">Tutup</td>
                <td class="text-right">{{ $cashDrawer->tanggal_tutup ? $cashDrawer->tanggal_tutup->format('d/m/Y H:i') : '-' }}</td>
            </tr>
            <tr>
                <td class="text-left">Status</td>
                <td class="text-right">{{ $cashDrawer->status }}</td>
            </tr>
        </table>

        <div class="divider"></div>

        <table>
            <tr>
                <td class="text-left">Saldo Awal</td>
                <td class="text-right">{{ number_format($cashDrawer->saldo_awal, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-left">Total Penjualan</td>
                <td class="text-right">{{ number_format($salesTotal, 0, ',', '.') }}</td>
            </tr>
            <tr class="fw-bold">
                <td class="text-left">Saldo Seharusnya</td>
                <td class="text-right">{{ number_format($cashDrawer->saldo_akhir_aplikasi, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="2" class="divider"></td>
            </tr>
            <tr>
                <td class="text-left">Saldo Fisik</td>
                <td class="text-right">{{ number_format($cashDrawer->saldo_akhir, 0, ',', '.') }}</td>
            </tr>
            <tr class="fw-bold">
                <td class="text-left">Selisih</td>
                <td class="text-right">{{ number_format($cashDrawer->selisih, 0, ',', '.') }}</td>
            </tr>
        </table>

        @if($cashDrawer->catatan)
        <div class="divider"></div>
        <p class="fw-bold">Catatan:</p>
        <p>{{ $cashDrawer->catatan }}</p>
        @endif

        <div class="double-divider"></div>

        <div class="text-center" style="margin-top: 20px;">
            <p>Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</body>

</html>
