@extends('layouts.pdf')

@section('content')
    <div style="margin-bottom: 15px; padding: 10px; background: #fff3cd;">
        <strong>Total Piutang: Rp {{ number_format($totalPiutang, 2, '.', ',') }}</strong>
    </div>

    @foreach ($grouped as $group)
        <h3 style="margin-top: 15px; margin-bottom: 5px; color: #333;">
            {{ $group['customer']->nama_pelanggan ?? 'Guest' }}
            <span style="font-size: 9pt; color: #777;">({{ $group['jumlah_invoice'] }} invoice)</span>
        </h3>
        <table>
            <thead>
                <tr>
                    <th>No. Invoice</th>
                    <th>Tanggal</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Dibayar</th>
                    <th class="text-right">Sisa Piutang</th>
                    <th class="text-center">Umur (Hari)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($group['items'] as $sale)
                    @php
                        $umur = \Carbon\Carbon::parse($sale->tanggal_transaksi)->diffInDays(now());
                    @endphp
                    <tr>
                        <td>{{ $sale->no_invoice }}</td>
                        <td>{{ \Carbon\Carbon::parse($sale->tanggal_transaksi)->format('d/m/Y') }}</td>
                        <td class="text-right">Rp {{ number_format($sale->total, 2, '.', ',') }}</td>
                        <td class="text-right">Rp {{ number_format($sale->dibayar, 2, '.', ',') }}</td>
                        <td class="text-right" style="color: red; font-weight: bold;">Rp
                            {{ number_format($sale->jumlah_utang, 2, '.', ',') }}</td>
                        <td class="text-center">
                            <span
                                class="badge {{ $umur > 60 ? 'badge-danger' : ($umur > 30 ? 'badge-warning' : 'badge-success') }}">
                                {{ $umur }} hari
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-right">Subtotal</th>
                    <th class="text-right" style="color: red;">Rp {{ number_format($group['total_piutang'], 2, '.', ',') }}
                    </th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    @endforeach
@endsection
