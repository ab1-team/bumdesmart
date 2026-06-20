@extends('layouts.pdf')

@section('content')
    <style>
        .shift-container {
            margin-bottom: 30px;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            background-color: #fff;
        }

        .shift-header {
            background-color: #f4f4f4;
            padding: 10px 15px;
            border-bottom: 2px solid #eee;
        }

        .shift-header table {
            width: 100%;
            border: none !important;
            margin-bottom: 0 !important;
        }

        .shift-header td {
            border: none !important;
            padding: 0 !important;
            vertical-align: middle;
        }

        .shift-title {
            font-size: 11pt;
            font-weight: bold;
            color: #333;
        }

        .shift-time {
            font-size: 9pt;
            color: #666;
            text-align: right;
        }

        .shift-items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 !important;
            border: none !important;
        }

        .shift-items-table th {
            background-color: #fafafa;
            border-bottom: 1px solid #eee !important;
            border-top: none !important;
            border-left: none !important;
            border-right: none !important;
            color: #555;
            font-size: 8pt;
            text-transform: uppercase;
        }

        .shift-items-table td {
            border-bottom: 1px solid #f0f0f0 !important;
            border-top: none !important;
            border-left: none !important;
            border-right: none !important;
            font-size: 9pt;
            padding: 8px 15px;
        }

        .shift-empty {
            padding: 20px;
            text-align: center;
            color: #999;
            font-style: italic;
            font-size: 9pt;
        }

        .shift-summary {
            background-color: #fcfcfc;
            padding: 12px 15px;
            border-top: 1px solid #eee;
        }

        .shift-summary table {
            width: 100%;
            border: none !important;
            margin-bottom: 0 !important;
        }

        .shift-summary td {
            border: none !important;
            padding: 2px 0 !important;
            font-size: 9pt;
        }

        .summary-label {
            color: #777;
        }

        .summary-value {
            font-weight: bold;
            color: #333;
        }

        .selisih-negatif {
            color: #dc3545 !important;
        }

        .selisih-positif {
            color: #28a745 !important;
        }
    </style>

    @foreach ($sessions as $session)
        <div class="shift-container">
            <div class="shift-header">
                <table>
                    <tr>
                        <td class="shift-title">
                            Kasir: {{ $session->user->nama_lengkap ?? ($session->user->name ?? '-') }}
                        </td>
                        <td class="shift-time">
                            Buka: {{ \Carbon\Carbon::parse($session->tanggal_buka)->format('d/m/Y H:i') }} |
                            Tutup:
                            {{ $session->tanggal_tutup ? \Carbon\Carbon::parse($session->tanggal_tutup)->format('d/m/Y H:i') : 'SEKARANG' }}
                        </td>
                    </tr>
                </table>
            </div>

            @if ($session->sales_items->count() > 0)
                <table class="shift-items-table">
                    <thead>
                        <tr>
                            <th class="text-left">Produk</th>
                            <th class="text-center" style="width: 80px;">Qty</th>
                            <th class="text-right" style="width: 150px;">Total Penjualan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($session->sales_items as $item)
                            <tr>
                                <td>{{ $item->product->nama_produk }}</td>
                                <td class="text-center">{{ $item->total_qty }}</td>
                                <td class="text-right">Rp {{ number_format($item->total_amount, 2, ',','.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="shift-empty">Tidak ada transaksi tercatat dalam shift ini.</div>
            @endif

            <div class="shift-summary">
                <table>
                    <tr>
                        <td style="width: 25%;">
                            <span class="summary-label">Saldo Awal:</span><br>
                            <span class="summary-value">Rp {{ number_format($session->saldo_awal, 2, ',','.') }}</span>
                        </td>
                        <td style="width: 25%;">
                            <span class="summary-label">Saldo Akhir (App):</span><br>
                            <span class="summary-value">Rp
                                {{ number_format($session->saldo_akhir_aplikasi, 2, ',','.') }}</span>
                        </td>
                        <td style="width: 25%;">
                            <span class="summary-label">Saldo Akhir (Manual):</span><br>
                            <span class="summary-value">Rp {{ number_format($session->saldo_akhir, 2, ',','.') }}</span>
                        </td>
                        <td style="width: 25%; text-align: right;">
                            <span class="summary-label">Selisih:</span><br>
                            <span
                                class="summary-value {{ $session->selisih < 0 ? 'selisih-negatif' : ($session->selisih > 0 ? 'selisih-positif' : '') }}">
                                Rp {{ number_format($session->selisih, 2, ',','.') }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    @endforeach
@endsection
