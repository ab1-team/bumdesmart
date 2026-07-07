@extends('layouts.pdf')

@section('content')
    <style>
        .header-row {
            width: 100%;
            padding-bottom: 8px;
            margin-bottom: 14px;
            border-bottom: 1px solid #999;
            position: relative;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table,
        .header-table th,
        .header-table td {
            border: 0 !important;
        }

        .header-table td {
            vertical-align: middle;
            padding: 0;
        }

        .header-logo {
            width: 160px;
        }

        .header-logo img {
            max-width: 150px;
            max-height: 150px;
        }

        .header-title {
            padding-left: 14px;
            line-height: 1.5;
            text-align: justify;
        }

        .header-title h1 {
            font-size: 18pt;
            margin: 0 0 6px;
            font-weight: bold;
            color: #1f5f6f;
            letter-spacing: 0.8px;
            text-align: left;
        }

        .header-title p {
            font-size: 9.5pt;
            margin: 2px 0;
            color: #1f5f6f;
            font-style: italic;
            line-height: 1.55;
        }

        .status-block {
            display: block;
            width: 100%;
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            letter-spacing: 2px;
            position: relative;
        }

        .status-text {
            display: block;
            margin-bottom: 4px;
            position: relative;
            width: 100%;
        }

        .status-label {
            display: block;
            font-size: 9pt;
            color: #555;
            font-weight: normal;
            letter-spacing: 0;
        }

        .status-value {
            display: inline-block;
            font-weight: bold;
            font-size: 16pt;
            letter-spacing: 4px;
            margin-top: 2px;
        }

        .status-block.paid .status-value {
            color: #dc2626;
        }

        .status-block.unpaid .status-value,
        .status-block.partial .status-value {
            color: #dc2626;
        }

        .lunas-stamp {
            max-width: 200px;
            max-height: 140px;
            opacity: 1;
            display: block;
            margin: 12px auto 0;
            border: 0;
            padding: 0;
        }

        .invoice-meta {
            width: 100%;
            margin-bottom: 18px;
            font-size: 10pt;
            border: 0;
        }

        .invoice-meta,
        .invoice-meta th,
        .invoice-meta td {
            border: 0;
        }

        .invoice-meta td {
            padding: 2px 0;
            vertical-align: top;
        }

        .invoice-meta .label {
            width: 130px;
            color: #555;
        }

        .diterima-box {
            border: 0;
            padding: 4px 0 12px;
            margin-bottom: 16px;
            background: transparent;
        }

        .diterima-box .title {
            font-weight: bold;
            margin-bottom: 4px;
        }

        .diterima-box .name {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .diterima-box .small {
            font-size: 9pt;
            color: #555;
            margin-top: 2px;
        }

        table.description-total {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        table.description-total th,
        table.description-total td {
            border: 0;
            padding: 6px 8px;
            font-size: 9.5pt;
        }

        table.description-total .num {
            text-align: right;
            white-space: nowrap;
        }

        .items-title {
            font-size: 11pt;
            font-weight: bold;
            color: #1f5f6f;
            margin: 70px 0 10px;
            text-align: left;
            letter-spacing: 0.5px;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        table.items th {
            background: #d9d8d8;
            border: 0;
            padding: 6px 8px;
            font-size: 9pt;
            text-align: left;
        }

        table.items td {
            border: 0;
            padding: 6px 8px;
            font-size: 9.5pt;
            vertical-align: middle;
        }

        table.items .num {
            text-align: right;
            white-space: nowrap;
        }

        .total-row td {
            background: #d9d8d8;
            font-weight: bold;
        }

        .sisa-row td {
            background: #d9d8d8;
            font-weight: bold;
        }

        .pembayaran-box {
            margin-top: 18px;
            font-size: 9.5pt;
            border: 0;
        }

        .pembayaran-box strong {
            display: block;
            margin-bottom: 4px;
        }

        .pembayaran-box ul {
            margin: 0;
            padding-left: 18px;
        }

        .footer-row {
            width: 100%;
            margin-top: 30px;
            font-size: 9pt;
            border: 0;
        }

        .footer-row,
        .footer-row th,
        .footer-row td {
            border: 0;
        }

        .footer-row td {
            vertical-align: top;
        }

        .footer-right {
            text-align: center;
            width: 45%;
            position: relative;
            min-height: 180px;
        }

        .footer-right > div,
        .footer-right > img {
            margin-left: auto;
            margin-right: auto;
        }

        .footer-right img.ttd {
            display: block;
            max-width: 240px;
            max-height: 170px;
            margin: 4px auto;
        }

        .footer-right .nama {
            position: absolute;
            bottom: 40px;
            left: 0;
            right: 0;
            padding-bottom: 2px;
            font-weight: bold;
            display: block;
            letter-spacing: 0.5px;
            z-index: 2;
        }

        .footer-right .nama span {
            display: inline-block;
            padding: 0 8px;
        }

        .keterangan-box {
            margin-top: 10px;
            padding: 8px 10px;
            border: 0;
            font-size: 9pt;
            background: #fcfcfc;
        }

        .keterangan-box .title {
            font-weight: bold;
            margin-bottom: 2px;
        }
    </style>

    <div class="header-row">
        <table class="header-table">
            <tr>
                <td class="header-logo">
                    @if (!empty($fileUrlAbt))
                        <img src="{{ $fileUrlAbt }}" alt="ABT">
                    @elseif (!empty($base64Abt))
                        <img src="{{ $base64Abt }}" alt="ABT">
                    @endif
                </td>
                <td class="header-title">
                    <h1>PT. ASTA BRATA TEKNOLOGI</h1>
                    <p>IT Colsulting, System, Training and Digital Audits</p>
                    <p>SK. Kementerian Hukum dan HAM RI Nomor. AHU-01329.40.10.2014 - NPWP. 66.867.912.9-524.000</p>
                    <p>Office : Jalan Perintis Kemerdekaan Km 1.5, Banyuurip Tegalrejo Magelang Jawa Tengah Indonesia</p>
                    <p>Telp.: (0293) 319 555 8 | E-mail: info@astabratagroup.com | Website: astabratagroup.com</p>
                </td>
            </tr>
        </table>
    </div>

    <table class="invoice-meta">
        <tr>
            <td style="width:55%; vertical-align:top;">
                <table style="width:100%; border:0;">
                    <tr>
                        <td class="label">Invoice No</td>
                        <td>: <strong>#{{ $invoice->no }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Tanggal Invoice</td>
                        <td>: {{ \Carbon\Carbon::parse($invoice->tanggal_invoice)->format('d/m/Y') }}</td>
                    </tr>
                    @if ($invoice->tanggal_diterima)
                        <tr>
                            <td class="label">Tanggal Lunas</td>
                            <td>: {{ \Carbon\Carbon::parse($invoice->tanggal_diterima)->format('d/m/Y') }}</td>
                        </tr>
                    @endif
                </table>
            </td>
            <td style="width:45%; vertical-align:top; text-align:right;">
                <div class="status-block {{ strtolower($invoice->status) }}">
                    <div class="status-text">
                        <span class="status-label">&nbsp;</span>
                        <span class="status-value">{{ strtoupper($invoice->status) }}</span>
                        @if (strtoupper($invoice->status) === 'PAID')
                            @if (!empty($fileUrlLunas))
                                <img class="lunas-stamp" src="{{ $fileUrlLunas }}" alt="Lunas">
                            @elseif (!empty($base64Lunas))
                                <img class="lunas-stamp" src="{{ $base64Lunas }}" alt="Lunas">
                            @endif
                        @endif
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="diterima-box">
        <div class="title">Telah diterima dari :</div>
        <div class="name">{{ strtoupper($owner->nama_usaha) }}</div>
        <div class="small">Pengguna Layanan Aplikasi BUMDesmart</div>
    </div>

    @php
        $subtotal = (int) $invoice->tagihan;
        $ppn = 100000;
        $discount = 100000;
        $total = $subtotal + $ppn - $discount;
        $masaPakai = $invoice->tanggal_diterima
            ? \Carbon\Carbon::parse($invoice->tanggal_diterima)->format('d/m/Y')
            : \Carbon\Carbon::parse($invoice->tanggal_invoice)->format('d/m/Y');
    @endphp

    <table class="description-total">
        <thead>
            <tr>
                <th colspan="3" style="background:#276c7d; color:#fff; text-align:center;">DESCRIPTION</th>
                <th style="background:#8eb1ba; color:#fff; text-align:center;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="3" style="text-align:center; padding-top:50px; padding-bottom:0; background:#f5f5f5;">
                    <strong>{{ $invoice->jenis_pembayaran }}</strong>
                </td>
                <td class="num" rowspan="2"
                    style="vertical-align:middle; text-align:right; padding:0 8px; height:120px; background:#f5f5f5;">
                    {{ number_format($subtotal, 2, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td colspan="3" style="text-align:center; color:#555; padding-top:0; padding-bottom:50px; background:#f5f5f5;">
                    Masa Pakai Sejak Tanggal {{ $masaPakai }}
                </td>
            </tr>
            <tr>
                <td colspan="3" style="text-align:right; font-weight:bold;">Sub Total</td>
                <td class="num" style="background:#8eb1ba; color:#fff; padding:6px 8px;">{{ number_format($subtotal, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="3" style="text-align:right; font-weight:bold;">PPN 11%</td>
                <td class="num" style="background:#8eb1ba; color:#fff; padding:6px 8px;">{{ number_format($ppn, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="3" style="text-align:right; font-weight:bold;">Discount 10%</td>
                <td class="num" style="background:#8eb1ba; color:#fff; padding:6px 8px;">-{{ number_format($discount, 2, ',', '.') }}</td>
            </tr>
            <tr style="font-weight:bold;">
                <td colspan="3" style="text-align:right;">TOTAL</td>
                <td class="num" style="background:#8eb1ba; color:#fff; padding:6px 8px;">{{ number_format($total, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="items-title">Transaksi</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width:30px;">No</th>
                <th>Tanggal</th>
                <th>Keterangan / Metode Pembayaran</th>
                <th class="num" style="width:140px;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @if (strtoupper($invoice->status) === 'PAID')
                <tr>
                    <td>1</td>
                    <td>{{ $invoice->tanggal_diterima ? \Carbon\Carbon::parse($invoice->tanggal_diterima)->format('Y-m-d') : \Carbon\Carbon::parse($invoice->tanggal_invoice)->format('Y-m-d') }}
                    </td>
                    <td>{{ $invoice->jenis_pembayaran }}@if ($invoice->metode_pembayaran)
                            <br><small style="color:#555;">Metode: {{ $invoice->metode_pembayaran }}</small>
                        @endif
                    </td>
                    <td class="num">{{ number_format($invoice->saldo, 0, ',', '.') }}</td>
                </tr>
                <tr class="total-row">
                    <td colspan="3" style="text-align:right;">Total Pembayaran</td>
                    <td class="num">{{ number_format($invoice->saldo, 0, ',', '.') }}</td>
                </tr>
                <tr class="sisa-row">
                    <td colspan="3" style="text-align:right;">Sisa Tagihan</td>
                    <td class="num">{{ number_format($sisaTagihan, 0, ',', '.') }}</td>
                </tr>
            @else
                <tr>
                    <td colspan="4" style="text-align:center; color:#777; padding:14px;">
                        Belum ada transaksi pembayaran untuk invoice ini.
                    </td>
                </tr>
                <tr class="sisa-row">
                    <td colspan="3" style="text-align:right;">Sisa Tagihan</td>
                    <td class="num">{{ number_format($invoice->tagihan, 0, ',', '.') }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="pembayaran-box">
        <strong>Pembayaran Transfer Via :</strong>
        <ul>
            <li>Bank Mandiri &mdash; No. Rekening : 185-00-487-8888-6 an. PT. Asta Brata Teknologi</li>
            <li>Bank BRI &mdash; No. Rekening : 0048-01-057317-50-5 an. Santoso</li>
        </ul>
    </div>

    <table class="footer-row">
        <tr>
            <td style="width:55%;">
                &nbsp;
            </td>
            <td class="footer-right">
                <div>Hormat Kami,</div>
                <div>Direktur PT. Asta Brata Teknologi</div>
                @if (!empty($fileUrlTtd))
                    <img class="ttd" src="{{ $fileUrlTtd }}" alt="TTD">
                @elseif (!empty($base64Ttd))
                    <img class="ttd" src="{{ $base64Ttd }}" alt="TTD">
                @endif
                <div class="nama"><span>Santoso</span></div>
            </td>
        </tr>
    </table>
@endsection