@extends('layouts.pdf')

@section('content')
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Customer</th>
                <th class="text-center">Jumlah Transaksi</th>
                <th class="text-right">Total Belanja</th>
                <th class="text-right">Rata-rata</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($customers as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->customer->nama_pelanggan ?? 'Guest' }}</td>
                    <td class="text-center">{{ number_format($item->jumlah_transaksi) }}</td>
                    <td class="text-right">Rp {{ number_format($item->total_belanja, 2, ',','.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->rata_rata, 2, ',','.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2" class="text-right">Total</th>
                <th class="text-center">{{ number_format($customers->sum('jumlah_transaksi')) }}</th>
                <th class="text-right">Rp {{ number_format($customers->sum('total_belanja'), 2, ',', '.') }}</th>
                <th></th>
            </tr>
        </tfoot>
    </table>
@endsection
