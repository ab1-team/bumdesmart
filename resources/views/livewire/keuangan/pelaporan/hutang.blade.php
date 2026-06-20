@extends('layouts.pdf')

@section('content')
    <div style="margin-bottom: 15px; padding: 10px; background: #f8d7da;">
        <strong>Total Hutang: Rp {{ number_format($totalHutang, 2, ',','.') }}</strong>
    </div>

    @foreach ($grouped as $group)
        <h3 style="margin-top: 15px; margin-bottom: 5px; color: #333;">
            {{ $group['supplier']->nama_supplier ?? '-' }}
            <span style="font-size: 9pt; color: #777;">({{ $group['jumlah_po'] }} PO)</span>
        </h3>
        <table>
            <thead>
                <tr>
                    <th>No. Pembelian</th>
                    <th>Tanggal</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Dibayar</th>
                    <th class="text-right">Sisa Hutang</th>
                    <th class="text-center">Umur (Hari)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($group['items'] as $purchase)
                    @php
                        $umur = \Carbon\Carbon::parse($purchase->tanggal_pembelian)->diffInDays(now());
                    @endphp
                    <tr>
                        <td>{{ $purchase->no_pembelian }}</td>
                        <td>{{ \Carbon\Carbon::parse($purchase->tanggal_pembelian)->format('d/m/Y') }}</td>
                        <td class="text-right">Rp {{ number_format($purchase->total, 2, ',','.') }}</td>
                        <td class="text-right">Rp {{ number_format($purchase->dibayar, 2, ',','.') }}</td>
                        <td class="text-right" style="color: red; font-weight: bold;">Rp
                            {{ number_format($purchase->jumlah_utang, 2, ',','.') }}</td>
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
                    <th class="text-right" style="color: red;">Rp {{ number_format($group['total_hutang'], 2, ',','.') }}
                    </th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    @endforeach
@endsection
