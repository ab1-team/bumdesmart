@extends('layouts.pdf')

@section('content')
    <table>
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th class="text-center">No. Invoice</th>
                <th class="text-center">Tanggal</th>
                <th>Pelanggan</th>
                <th class="text-center">Kasir</th>
                <th class="text-center">Item</th>
                <th class="text-right">Total Penjualan</th>
                <th class="text-right">HPP</th>
                <th class="text-right">Untung</th>
                <th class="text-right">Rugi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sales as $index => $row)
                @php
                    $sumUntung = (float) $row->sum_untung;
                    $sumRugi = (float) $row->sum_rugi;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $row->no_invoice ?? '-' }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($row->tanggal_transaksi)->format('d/m/Y H:i') }}</td>
                    <td>{{ $row->customer->nama_pelanggan ?? 'Guest' }}</td>
                    <td class="text-center">{{ $row->user->initial ?? '-' }}</td>
                    <td class="text-center">{{ (int) $row->total_item }}</td>
                    <td class="text-right">Rp {{ number_format((float) $row->total_penjualan, 2, '.', ',') }}</td>
                    <td class="text-right">Rp {{ number_format((float) $row->sum_hpp, 2, '.', ',') }}</td>
                    <td class="text-right" style="color: {{ $sumUntung >= 0 ? 'green' : 'inherit' }};">
                        Rp {{ number_format($sumUntung, 2, '.', ',') }}
                    </td>
                    <td class="text-right" style="color: {{ $sumRugi > 0 ? 'red' : 'inherit' }};">
                        Rp {{ number_format($sumRugi, 2, '.', ',') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 20px;">No data available in table</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="6" class="text-right">Total</th>
                <th class="text-right">Rp {{ number_format($totals['total_penjualan'], 2, '.', ',') }}</th>
                <th class="text-right">Rp {{ number_format($totals['sum_hpp'], 2, '.', ',') }}</th>
                <th class="text-right">Rp {{ number_format($totals['sum_untung'], 2, '.', ',') }}</th>
                <th class="text-right">Rp {{ number_format($totals['sum_rugi'], 2, '.', ',') }}</th>
            </tr>
        </tfoot>
    </table>
@endsection