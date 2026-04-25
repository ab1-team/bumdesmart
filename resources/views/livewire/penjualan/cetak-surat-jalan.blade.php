<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan {{ $sale->no_invoice }}</title>
    <style>
        @page {
            size: 11cm 15cm;
            margin: 3mm;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 9px;
            color: #000;
            margin: 0;
            padding: 0;
            background-color: #fff;
            line-height: 1.2;
        }

        .container {
            width: 100%;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        .header-table td {
            border: none;
            padding: 1px;
            vertical-align: top;
        }

        .business-logo {
            max-width: 40px;
            max-height: 30px;
            margin-bottom: 2px;
        }

        .doc-title {
            font-size: 12px;
            font-weight: bold;
            margin: 0;
            line-height: 1;
        }

        .label-col {
            width: 65px;
        }

        .value-col {
            width: 5px;
            text-align: center;
        }

        table.main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        table.main-table th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px 2px;
            text-align: left;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
        }

        table.main-table td {
            padding: 2px;
            border-bottom: 0.1px solid #eee;
            font-size: 8px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }

        .footer-section {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
        }

        .signature-section {
            width: 100%;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            width: 45%;
            text-align: center;
        }

        .signature-space {
            height: 30px;
        }

        .total-box {
            width: 35%;
            text-align: right;
            border-top: 1px solid #000;
            padding-top: 3px;
        }

        .notes {
            font-size: 9px;
            margin-top: 10px;
            border-top: 1px dotted #000;
            padding-top: 5px;
        }

        @media print {
            .d-print-none { display: none; }
            body { padding: 0; }
        }
    </style>
</head>

<body onload="window.print()">
    <div class="container">
        <!-- Control Buttons -->
        <div class="d-print-none" style="margin-bottom: 20px; text-align: center; padding: 10px; background: #f0f0f0;">
            <button onclick="window.print()" style="padding: 8px 16px; cursor: pointer; background: #28a745; color: white; border: none; border-radius: 4px; font-weight: bold;">🖨️ CETAK</button>
            <button onclick="window.close()" style="padding: 8px 16px; cursor: pointer; background: #6c757d; color: white; border: none; border-radius: 4px; font-weight: bold; margin-left: 10px;">❌ TUTUP</button>
        </div>

        @php
            $logoUrl = $owner && $owner->logo ? asset('storage/' . $owner->logo) : null;
            $totalQty = $sale->saleDetails->sum('jumlah');
            $tanggal = date('d-m-Y', strtotime($sale->tanggal_transaksi));
            $tempo = $sale->jumlah_utang > 0 ? date('d-m-Y', strtotime($sale->tanggal_transaksi . ' + 30 days')) : '-';
        @endphp

        <!-- Header -->
        <table class="header-table">
            <tr>
                <!-- Business Info & Title -->
                <td style="text-align: center; border-bottom: 1px double #000; padding-bottom: 5px;">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo" class="business-logo">
                    @else
                        <div style="font-size: 14px; font-weight: bold;">{{ $business->nama_usaha ?? '' }}</div>
                    @endif
                    <div class="doc-title" style="margin: 5px 0;">SURAT JALAN</div>
                    <div style="font-size: 8px;">{{ $business->no_telp ?? '-' }}</div>
                </td>
            </tr>
            <tr>
                <!-- Transaction Info -->
                <td style="padding-top: 5px;">
                    <table style="width: 100%;">
                        <tr>
                            <td class="label-col">No. Transaksi</td>
                            <td class="value-col">:</td>
                            <td>{{ $sale->no_invoice }}</td>
                            <td class="label-col" style="text-align: right;">Tanggal</td>
                            <td class="value-col">:</td>
                            <td style="text-align: right;">{{ $tanggal }}</td>
                        </tr>
                        <tr>
                            <td class="label-col">Kepada</td>
                            <td class="value-col">:</td>
                            <td class="fw-bold">{{ $sale->customer->nama_pelanggan ?? 'Umum' }}</td>
                            <td class="label-col" style="text-align: right;">Admin</td>
                            <td class="value-col">:</td>
                            <td style="text-align: right;">{{ strtoupper($sale->user->nama_lengkap ?? 'Admin') }}</td>
                        </tr>
                        <tr>
                            <td class="label-col">Status</td>
                            <td class="value-col">:</td>
                            <td style="text-align: right;" class="fw-bold">
                                @php
                                    $payment = $sale->payments->whereNotIn('metode_pembayaran', ['system', 'internal'])->first();
                                    $metode = $payment ? strtoupper($payment->metode_pembayaran) : 'TUNAI';
                                    
                                    $statusPrefix = 'LUNAS';
                                    if ($sale->jumlah_utang > 0) {
                                        $statusPrefix = 'UTANG';
                                    }
                                    $statusBayar = $statusPrefix . ' ' . $metode;
                                    
                                    if (in_array($metode, ['TRANSFER', 'QRIS']) && $payment && $payment->no_referensi) {
                                        $statusBayar .= ' (' . $payment->no_referensi . ')';
                                    }
                                @endphp
                                {{ $statusBayar }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Items Table -->
        <table class="main-table">
            <thead>
                <tr>
                    <th style="width: 60px;">Barcode</th>
                    <th>Nama Barang</th>
                    <th style="width: 70px;" class="text-center">Isi Per Dus</th>
                    <th style="width: 40px;" class="text-right">Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->saleDetails as $item)
                    <tr>
                        <td>{{ $item->product->barcode ?? $item->product->sku ?? '-' }}</td>
                        <td>{{ $item->product->nama_produk ?? 'Produk' }}</td>
                        <td class="text-center">@1 {{ $item->product->unit->nama_satuan ?? 'PCS' }}</td>
                        <td class="text-right">{{ number_format($item->jumlah, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="fw-bold" style="text-align: right; margin-right: 2px; font-size: 8px;">
            Total Qty {{ number_format($totalQty, 0, ',', '.') }}
        </div>

        <!-- Footer -->

        <div class="footer-section">
            <div class="signature-section">
                <div class="signature-box">
                    <p>Diterima Oleh,</p>
                    <div class="signature-space"></div>
                    <p>( ................................ )</p>
                </div>
                <div class="signature-box">
                    <p>Hormat Kami,</p>
                    <div class="signature-space"></div>
                    <p>( {{ strtoupper($sale->user->nama_lengkap ?? 'Admin') }} )</p>
                </div>
            </div>
        </div>

        <div class="notes">
            Printed on: {{ date('d/m/Y H:i:s') }}
        </div>
    </div>
</body>

</html>
