@extends('layouts.pdf')

@section('content')
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th class="text-center">Qty Terjual</th>
                <th class="text-right">Total Revenue</th>
                <th class="text-right">Total Profit</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->product->nama_produk ?? '-' }}</td>
                    <td>{{ $item->product->category->nama_kategori ?? '-' }}</td>
                    <td class="text-center">{{ number_format($item->total_terjual) }}</td>
                    <td class="text-right">Rp {{ number_format($item->total_revenue, 2, '.', ',') }}</td>
                    <td class="text-right">Rp {{ number_format($item->total_profit, 2, '.', ',') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-right">Total</th>
                <th class="text-center">{{ number_format($products->sum('total_terjual')) }}</th>
                <th class="text-right">Rp {{ number_format($products->sum('total_revenue'), 2, '.', ',') }}</th>
                <th class="text-right">Rp {{ number_format($products->sum('total_profit'), 2, '.', ',') }}</th>
            </tr>
        </tfoot>
    </table>
@endsection
