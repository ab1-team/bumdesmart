@extends('layouts.pdf')

@section('content')
    <div style="margin-bottom: 20px;">
        <strong>Rangkuman:</strong><br>
        <table style="width: 100%; border: 1px solid #ddd; margin-top: 10px;">
            <tr>
                <th style="background: #f4f4f4;">Total Penjualan</th>
                <th style="background: #f4f4f4;">Jumlah Transaksi</th>
                <th style="background: #f4f4f4;">Rata-rata</th>
            </tr>
            <tr>
                <td style="text-align: right;">Rp {{ number_format($summary['total_sales'], 0, ',', '.') }}</td>
                <td style="text-align: center;">{{ $summary['total_transactions'] }}</td>
                <td style="text-align: right;">Rp {{ number_format($summary['avg_transaction'], 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    @foreach ($groups as $groupName => $groupData)
        @if (count($groupData['items']) > 0)
            <h3 style="margin-top: 20px;">{{ $groupName }}</h3>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No. Invoice</th>
                        <th>Waktu</th>
                        <th>Pelanggan</th>
                        <th>Pembayaran</th>
                        <th>Nominal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($groupData['items'] as $index => $item)
                        <tr>
                            <td style="text-align: center;">{{ $index + 1 }}</td>
                            <td>{{ $item['sale']->no_invoice }}</td>
                            <td>{{ $item['sale']->tanggal_transaksi }}</td>
                            <td>{{ $item['sale']->customer->nama_pelanggan ?? 'Guest' }}</td>
                            <td>{{ ucfirst($item['metode']) }}</td>
                            <td style="text-align: right;">Rp {{ number_format($item['amount'], 0, ',', '.') }}</td>
                            <td
                                style="text-align: center; color: {{ $item['sale']->status === 'paid' || $item['sale']->status === 'completed' ? 'green' : ($item['sale']->status === 'cancelled' ? 'red' : 'orange') }}">
                                {{ ucfirst($item['sale']->status ?? 'paid') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" style="text-align: right;">Total {{ $groupName }}</th>
                        <th style="text-align: right;">Rp {{ number_format($groupData['total'], 0, ',', '.') }}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        @endif
    @endforeach
@endsection
