@extends('layouts.pdf')

@section('content')
    <div style="margin-bottom: 15px;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 25%; border: none; padding: 8px; background: #f8f8f8;">
                    <strong>Jumlah PO</strong><br>{{ $summary['total_po'] }}
                </td>
                <td style="width: 25%; border: none; padding: 8px; background: #f8f8f8;">
                    <strong>Total Pembelian</strong><br>Rp {{ number_format($summary['total_pembelian'], 2, ',','.') }}
                </td>
                <td style="width: 25%; border: none; padding: 8px; background: #f8f8f8;">
                    <strong>Total Dibayar</strong><br>Rp {{ number_format($summary['total_dibayar'], 2, ',','.') }}
                </td>
                <td style="width: 25%; border: none; padding: 8px; background: #f8d7da;">
                    <strong>Total Hutang</strong><br>Rp {{ number_format($summary['total_hutang'], 2, ',','.') }}
                </td>
            </tr>
        </table>
    </div>

    <h3>Rincian Pembelian</h3>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No. Pembelian</th>
                <th>Tanggal</th>
                <th>Supplier</th>
                <th>Pembayaran</th>
                <th class="text-right">Total</th>
                <th class="text-right">Hutang</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchases as $index => $purchase)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $purchase->no_pembelian }}</td>
                    <td>{{ \Carbon\Carbon::parse($purchase->tanggal_pembelian)->format('d/m/Y') }}</td>
                    <td>{{ $purchase->supplier->nama_supplier ?? '-' }}</td>
                    <td>{{ ucfirst($purchase->jenis_pembayaran) }}</td>
                    <td class="text-right">Rp {{ number_format($purchase->total, 2, ',','.') }}</td>
                    <td class="text-right" style="color: {{ $purchase->jumlah_utang > 0 ? 'red' : 'green' }};">
                        Rp {{ number_format($purchase->jumlah_utang, 2, ',','.') }}
                    </td>
                    <td class="text-center">{{ $purchase->status }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="text-right">Total</th>
                <th class="text-right">Rp {{ number_format($purchases->sum('total'), 2, ',', '.') }}</th>
                <th class="text-right">Rp {{ number_format($purchases->sum('jumlah_utang'), 2, ',', '.') }}</th>
                <th></th>
            </tr>
        </tfoot>
    </table>
@endsection
