<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Penjualan {{ $sale->no_invoice }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000;
            margin: 0;
            padding: 10px;
            width: 100%;
            max-width: 58mm;
            /* Ukuran printer thermal biasa */
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

        .item-row td {
            padding-bottom: 2px;
        }

        @media print {
            @page {
                size: 58mm auto;
                /* 58mm width, variable height */
                margin: 0;
            }

            body {
                width: 58mm;
                margin: 0;
                padding: 0;
                /* Optional height reset for print auto paper roll cutting */
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
            <button onclick="window.print()" style="padding: 5px 10px; cursor: pointer;">Cetak Ulang</button>
            <button onclick="window.close()" style="padding: 5px 10px; cursor: pointer;">Tutup</button>
        </div>

        <div class="text-center">
            <h3>{{ env('APP_NAME', 'Toko Anda') }}</h3>
            <p>{{ $owner->alamat ?? 'Alamat Toko' }}</p>
            <p>Telp: {{ $owner->telepon ?? '-' }}</p>
        </div>

        <div class="divider"></div>

        <table>
            <tr>
                <td class="text-left">No.</td>
                <td class="text-right">{{ $sale->no_invoice }}</td>
            </tr>
            <tr>
                <td class="text-left">Tgl</td>
                <td class="text-right">{{ date('d/m/Y H:i', strtotime($sale->created_at)) }}</td>
            </tr>
            <tr>
                <td class="text-left">Kasir</td>
                <td class="text-right">{{ $sale->user->nama_lengkap ?? 'Kasir' }}</td>
            </tr>
            <tr>
                <td class="text-left">Plg</td>
                <td class="text-right">{{ $sale->customer->nama_pelanggan ?? 'Umum' }}</td>
            </tr>
        </table>

        <div class="divider"></div>

        <table>
            @foreach ($sale->saleDetails as $item)
                <tr class="item-row">
                    <td colspan="3">
                        {{ $item->product->nama_produk ?? 'Produk' }}
                    </td>
                </tr>
                <tr>
                    <td style="width: 30%">{{ $item->jumlah }} x</td>
                    <td style="width: 35%" class="text-right">{{ number_format($item->harga_satuan, 0, ',', '.') }}
                    </td>
                    <td style="width: 35%" class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @if ($item->jumlah_diskon > 0)
                    <tr>
                        <td colspan="2" class="text-right">Disc.</td>
                        <td class="text-right">-{{ number_format($item->jumlah_diskon, 0, ',', '.') }}</td>
                    </tr>
                @endif
            @endforeach
        </table>

        <div class="double-divider"></div>

        <table>
            <tr>
                <td class="text-left" colspan="2">Subtotal</td>
                <td class="text-right">{{ number_format($sale->subtotal, 0, ',', '.') }}</td>
            </tr>

            @if ($sale->jumlah_diskon > 0)
                <tr>
                    <td class="text-left" colspan="2">Diskon Total</td>
                    <td class="text-right">-{{ number_format($sale->jumlah_diskon, 0, ',', '.') }}</td>
                </tr>
            @endif

            @if ($sale->jumlah_cashback > 0)
                <tr>
                    <td class="text-left" colspan="2">Cashback</td>
                    <td class="text-right">{{ number_format($sale->jumlah_cashback, 0, ',', '.') }}</td>
                </tr>
            @endif

            <tr>
                <td class="text-left fw-bold" colspan="2">TOTAL</td>
                <td class="text-right fw-bold">{{ number_format($sale->total, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-left" colspan="2">Bayar</td>
                <td class="text-right">{{ number_format($sale->dibayar, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-left" colspan="2">Kembali</td>
                <td class="text-right">{{ number_format($sale->dibayar - $sale->total, 0, ',', '.') }}</td>
            </tr>
        </table>

        <div class="divider"></div>

        <div class="text-center">
            <p>Terima kasih atas kunjungan Anda!</p>
            <p>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.</p>
        </div>
    </div>

</body>

</html>
