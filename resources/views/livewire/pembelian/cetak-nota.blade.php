<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Pembelian {{ $purchase->no_invoice }}</title>
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
            width: 60%;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            width: 45%;
            text-align: center;
        }

        .signature-space {
            height: 40px;
        }

        .total-box {
            width: 35%;
            text-align: right;
            border-top: 1px solid #000;
            padding-top: 5px;
        }

        .total-row td {
            padding: 1px 0;
        }

        .notes {
            font-size: 9px;
            margin-top: 10px;
            border-top: 1px dotted #000;
            padding-top: 5px;
        }

        .badge-status {
            font-weight: bold;
            padding: 2px 5px;
            border: 1px solid #000;
            display: inline-block;
            text-transform: uppercase;
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
            <button onclick="window.print()" style="padding: 8px 16px; cursor: pointer; background: #28a745; color: white; border: none; border-radius: 4px; font-weight: bold;">🖨️ CETAK NOTA</button>
            <button onclick="window.close()" style="padding: 8px 16px; cursor: pointer; background: #6c757d; color: white; border: none; border-radius: 4px; font-weight: bold; margin-left: 10px;">❌ TUTUP</button>
        </div>

        @php
            $logoUrl = $owner && $owner->logo ? asset('storage/' . $owner->logo) : null;
            $tanggal = date('d-m-Y', strtotime($purchase->tanggal_transaksi));
            
            // Payment Method Logic
            $payment = $purchase->payments->whereNotIn('metode_pembayaran', ['system', 'internal'])->first();
            $metode = $payment ? strtoupper($payment->metode_pembayaran) : 'TUNAI';
            if (in_array($metode, ['TRANSFER', 'QRIS']) && $payment && $payment->no_referensi) {
                $metode .= ' (' . $payment->no_referensi . ')';
            }

            // Status Pembayaran Logic
            $statusPrefix = 'LUNAS';
            if ($purchase->jumlah_utang > 0) {
                $statusPrefix = 'UTANG';
            }
            $statusBayar = $statusPrefix . ' ' . $metode;
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
                    <div class="doc-title" style="margin: 5px 0;">NOTA PEMBELIAN</div>
                    <div style="font-size: 8px;">{{ $business->no_telp ?? '-' }}</div>
                </td>
            </tr>
            <tr>
                <!-- Transaction Info -->
                <td style="padding-top: 5px;">
                    <table style="width: 100%;">
                        <tr>
                            <td class="label-col">No. Nota</td>
                            <td class="value-col">:</td>
                            <td>{{ $purchase->no_invoice }}</td>
                            <td class="label-col" style="text-align: right;">Tanggal</td>
                            <td class="value-col">:</td>
                            <td style="text-align: right;">{{ $tanggal }}</td>
                        </tr>
                        <tr>
                            <td class="label-col">Supplier</td>
                            <td class="value-col">:</td>
                            <td class="fw-bold">{{ $purchase->supplier->nama_supplier ?? '-' }}</td>
                            <td class="label-col" style="text-align: right;">Status</td>
                            <td class="value-col">:</td>
                            <td style="text-align: right;" class="fw-bold">{{ $statusBayar }}</td>
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
                    <th>Barang</th>
                    <th style="width: 50px;" class="text-right">Harga</th>
                    <th style="width: 30px;" class="text-center">Qty</th>
                    <th style="width: 50px;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($purchase->purchaseDetails as $item)
                    <tr>
                        <td>{{ $item->product->barcode ?? $item->product->sku ?? '-' }}</td>
                        <td>{{ $item->product->nama_produk ?? 'Produk' }}</td>
                        <td class="text-right">{{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                        <td class="text-center">{{ number_format($item->jumlah, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Footer Section -->
        <div style="display: flex; justify-content: space-between; font-size: 8px;">
            <div class="totals-box" style="width: 100%; text-align: right; margin-top: 2px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="text-align: left;">Subtotal</td>
                        <td style="text-align: center; width: 10px;">:</td>
                        <td class="text-right">{{ number_format($purchase->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if ($purchase->jumlah_diskon > 0)
                    <tr>
                        <td style="text-align: left;">Diskon</td>
                        <td style="text-align: center;">:</td>
                        <td class="text-right">-{{ number_format($purchase->jumlah_diskon, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="fw-bold" style="border-top: 1px solid #000;">
                        <td style="text-align: left;">TOTAL</td>
                        <td style="text-align: center;">:</td>
                        <td class="text-right">{{ number_format($purchase->total, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left;">Dibayar</td>
                        <td style="text-align: center;">:</td>
                        <td class="text-right">{{ number_format($purchase->dibayar, 0, ',', '.') }}</td>
                    </tr>
                    @if($purchase->jumlah_utang > 0)
                    <tr class="fw-bold">
                        <td style="text-align: left;">Sisa Hutang</td>
                        <td style="text-align: center;">:</td>
                        <td class="text-right">{{ number_format($purchase->jumlah_utang, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Signatures -->
        <div style="width: 100%; display: flex; justify-content: space-between; margin-top: 10px; font-size: 8px;">
            <div class="signature-box" style="width: 45%; text-align: center;">
                <p>Supplier,</p>
                <div class="signature-space" style="height: 25px;"></div>
                <p>( ................ )</p>
            </div>
            <div class="signature-box" style="width: 45%; text-align: center;">
                <p>Hormat Kami,</p>
                <div class="signature-space" style="height: 25px;"></div>
                <p>( {{ strtoupper(auth()->user()->nama_lengkap ?? 'Admin') }} )</p>
            </div>
        </div>

        <div class="terbilang" style="font-style: italic; font-size: 10px; margin-top: 5px; border: 1px dashed #ccc; padding: 3px;">
            Terbilang: {{ \App\Utils\NumberUtil::terbilang($purchase->total) }} Rupiah
        </div>

        <div class="notes">
            Printed on: {{ date('d/m/Y H:i:s') }}
        </div>
    </div>
</body>

</html>
