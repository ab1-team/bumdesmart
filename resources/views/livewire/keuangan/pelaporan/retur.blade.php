@extends('layouts.pdf')

@section('content')
    <h3>A. Retur Penjualan (dari Customer)</h3>
    @if ($salesReturns->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>No. Return</th>
                    <th>Tanggal</th>
                    <th>No. Invoice</th>
                    <th>Customer</th>
                    <th class="text-right">Nilai Return</th>
                    <th>Alasan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($salesReturns as $index => $sr)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $sr->no_return }}</td>
                        <td>{{ \Carbon\Carbon::parse($sr->tanggal_return)->format('d/m/Y') }}</td>
                        <td>{{ $sr->sale->no_invoice ?? '-' }}</td>
                        <td>{{ $sr->sale->customer->nama_pelanggan ?? 'Guest' }}</td>
                        <td class="text-right">Rp {{ number_format($sr->total_return, 2, '.', ',') }}</td>
                        <td>{{ $sr->alasan_return ?? '-' }}</td>
                        <td class="text-center">{{ $sr->status }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5" class="text-right">Total Retur Penjualan</th>
                    <th class="text-right">Rp {{ number_format($salesReturns->sum('total_return'), 2, '.', ',') }}</th>
                    <th colspan="2"></th>
                </tr>
            </tfoot>
        </table>
    @else
        <p style="color: #999;">Tidak ada data retur penjualan.</p>
    @endif

    <h3 style="margin-top: 25px;">B. Retur Pembelian (ke Supplier)</h3>
    @if ($purchaseReturns->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>No. Return</th>
                    <th>Tanggal</th>
                    <th>No. Pembelian</th>
                    <th>Supplier</th>
                    <th class="text-right">Nilai Return</th>
                    <th>Alasan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($purchaseReturns as $index => $pr)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $pr->no_return }}</td>
                        <td>{{ \Carbon\Carbon::parse($pr->tanggal_return)->format('d/m/Y') }}</td>
                        <td>{{ $pr->purchase->no_pembelian ?? '-' }}</td>
                        <td>{{ $pr->purchase->supplier->nama_supplier ?? '-' }}</td>
                        <td class="text-right">Rp {{ number_format($pr->total_return, 2, '.', ',') }}</td>
                        <td>{{ $pr->alasan_return ?? '-' }}</td>
                        <td class="text-center">{{ $pr->status }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5" class="text-right">Total Retur Pembelian</th>
                    <th class="text-right">Rp {{ number_format($purchaseReturns->sum('total_return'), 2, '.', ',') }}</th>
                    <th colspan="2"></th>
                </tr>
            </tfoot>
        </table>
    @else
        <p style="color: #999;">Tidak ada data retur pembelian.</p>
    @endif
@endsection
