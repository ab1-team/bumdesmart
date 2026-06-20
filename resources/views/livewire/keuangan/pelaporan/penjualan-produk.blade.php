@extends('layouts.pdf')

@section('content')
    <table>
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th class="text-center">Product ID</th>
                <th>Produk</th>
                <th>Nama Pelanggan</th>
                <th class="text-center">Nomor Faktur</th>
                <th class="text-center">Tanggal</th>
                <th class="text-center">Kuantitas</th>
                <th class="text-right">Harga Jual Satuan</th>
                <th class="text-right">Sub Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sales as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $row->product_id }}</td>
                    <td>{{ $row->product->nama_produk ?? '-' }}</td>
                    <td>{{ $row->sale->customer->nama_pelanggan ?? 'Guest' }}</td>
                    <td class="text-center">{{ $row->sale->no_invoice ?? '-' }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($row->sale->tanggal_transaksi)->format('d/m/Y H:i') }}</td>
                    <td class="text-center">{{ number_format($row->jumlah, 2, ',','.') }}</td>
                    <td class="text-right">Rp {{ number_format($row->harga_satuan, 2, ',','.') }}</td>
                    <td class="text-right">Rp {{ number_format($row->subtotal, 2, ',','.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px;">No data available in table</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="8" class="text-right">Total:</th>
                <th class="text-right">Rp {{ number_format($total, 2, ',','.') }}</th>
            </tr>
        </tfoot>
    </table>
@endsection