<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Penjualan {{ $sale->no_invoice }}</title>
    <style>
        @page {
            size: A5 landscape;
            margin: 0;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            color: #000;
            margin: 0;
            padding: 15px;
            background-color: #fff;
        }

        .container {
            width: 100%;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
        }

        .business-info {
            width: 55%;
        }

        .business-info h2 {
            margin: 0;
            font-size: 18px;
            color: #1a1a1a;
        }

        .business-info p {
            margin: 1px 0;
            font-size: 11px;
            color: #444;
        }

        .document-title {
            width: 40%;
            text-align: right;
        }

        .document-title h1 {
            margin: 0;
            font-size: 22px;
            color: #000;
            letter-spacing: 2px;
        }

        .document-title p {
            margin: 2px 0;
            font-weight: bold;
        }

        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .info-box {
            width: 48%;
        }

        .info-box table {
            width: 100%;
        }

        .info-box td {
            border: none;
            padding: 1px 0;
            font-size: 11px;
        }

        .label {
            width: 80px;
            color: #555;
        }

        table.main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table.main-table th {
            background-color: #f8f9fa;
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
            font-size: 11px;
            text-transform: uppercase;
        }

        table.main-table td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: middle;
            font-size: 11px;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }

        .summary-section {
            display: flex;
            justify-content: space-between;
        }

        .signature-section {
            width: 60%;
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }

        .signature-box {
            width: 45%;
            text-align: center;
        }

        .signature-space {
            height: 45px;
        }

        .totals-box {
            width: 35%;
        }

        .totals-box table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-box td {
            padding: 3px 0;
            border: none;
        }

        .grand-total {
            border-top: 1px solid #000 !important;
            margin-top: 5px;
            padding-top: 5px !important;
            font-size: 14px;
            font-weight: bold;
        }

        .terbilang {
            font-style: italic;
            font-size: 10px;
            margin-top: 5px;
            color: #333;
            border: 1px dashed #ccc;
            padding: 5px;
        }

        @media print {
            body { padding: 0; }
            .d-print-none { display: none; }
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
        @endphp

        <!-- Header -->
        <div class="header">
            <div class="business-info">
                <div style="display: flex; align-items: center;">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo" style="max-width: 60px; max-height: 60px; margin-right: 12px;">
                    @endif
                    <div>
                        <h2>{{ $owner->nama_usaha ?? env('APP_NAME', 'BUMDESMART') }}</h2>
                        <p>{{ $owner->alamat ?? 'Alamat Toko Belum Diatur' }}</p>
                        <p>Telp: {{ $owner->telepon ?? '-' }} | Email: {{ $owner->email ?? '-' }}</p>
                    </div>
                </div>
            </div>
            <div class="document-title">
                <h1>NOTA PENJUALAN</h1>
                <p>#{{ $sale->no_invoice }}</p>
            </div>
        </div>

        <!-- Info Section -->
        <div class="info-section">
            <div class="info-box">
                <table>
                    <tr>
                        <td class="label">Tanggal</td>
                        <td>: {{ date('d F Y', strtotime($sale->tanggal_transaksi)) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Kasir</td>
                        <td>: {{ $sale->user->nama_lengkap ?? 'Admin' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Pembayaran</td>
                        <td>: <span style="text-transform: uppercase;">{{ $sale->jenis_pembayaran }}</span></td>
                    </tr>
                </table>
            </div>
            <div class="info-box">
                <table>
                    <tr>
                        <td class="label">Pelanggan</td>
                        <td>: <strong>{{ $sale->customer->nama_pelanggan ?? 'Umum' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Alamat</td>
                        <td>: {{ $sale->customer->alamat ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">No. HP</td>
                        <td>: {{ $sale->customer->no_hp ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Items Table -->
        <table class="main-table">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th>Nama Barang</th>
                    <th style="width: 60px;">Qty</th>
                    <th style="width: 80px;">Harga</th>
                    <th style="width: 80px;">Disc</th>
                    <th style="width: 100px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->saleDetails as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item->product->nama_produk ?? 'Produk' }}</td>
                        <td class="text-center">{{ $item->jumlah }} {{ $item->product->unit->nama_satuan ?? '' }}</td>
                        <td class="text-right">{{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                        <td class="text-right">{{ $item->jumlah_diskon > 0 ? number_format($item->jumlah_diskon, 0, ',', '.') : '-' }}</td>
                        <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary & Signatures -->
        <div class="summary-section">
            <div class="signature-section">
                <div class="signature-box">
                    <p>Penerima,</p>
                    <div class="signature-space"></div>
                    <p>( ................................ )</p>
                </div>
                <div class="signature-box">
                    <p>Hormat Kami,</p>
                    <div class="signature-space"></div>
                    <p>( {{ $sale->user->nama_lengkap ?? 'Admin' }} )</p>
                </div>
                <div style="flex-grow: 1; padding-left: 20px;">
                    <div class="terbilang">
                        <strong>Terbilang:</strong><br>
                        {{ \App\Utils\NumberUtil::terbilang($sale->total) }} Rupiah
                    </div>
                    @if($sale->keterangan)
                        <div style="font-size: 9px; margin-top: 5px; color: #666;">
                            <strong>Catatan:</strong> {{ $sale->keterangan }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="totals-box">
                <table>
                    <tr>
                        <td>Subtotal</td>
                        <td class="text-right">{{ number_format($sale->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if ($sale->jumlah_diskon > 0)
                        <tr>
                            <td>Diskon Total</td>
                            <td class="text-right text-danger">-{{ number_format($sale->jumlah_diskon, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if ($sale->jumlah_cashback > 0)
                        <tr>
                            <td>Cashback</td>
                            <td class="text-right text-success">{{ number_format($sale->jumlah_cashback, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="grand-total">
                        <td class="fw-bold">TOTAL</td>
                        <td class="text-right fw-bold">{{ number_format($sale->total, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Dibayar</td>
                        <td class="text-right">{{ number_format($sale->dibayar, 0, ',', '.') }}</td>
                    </tr>
                    @if($sale->kembalian > 0)
                        <tr>
                            <td>Kembali</td>
                            <td class="text-right">{{ number_format($sale->kembalian, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if($sale->jumlah_utang > 0)
                        <tr>
                            <td class="fw-bold text-danger">Sisa Tagihan</td>
                            <td class="text-right fw-bold text-danger">{{ number_format($sale->jumlah_utang, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>

        <div style="margin-top: 15px; font-size: 9px; color: #888; border-top: 1px dotted #ccc; padding-top: 5px;">
            <p>1. Barang yang sudah dibeli tidak dapat ditukar atau dikembalikan.<br>
            2. Nota ini adalah bukti pembayaran yang sah.</p>
        </div>
    </div>
</body>

</html>
