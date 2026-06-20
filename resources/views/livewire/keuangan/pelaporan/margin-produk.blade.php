@extends('layouts.pdf')

@section('content')
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>SKU</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th class="text-right">HPP</th>
                <th class="text-right">Harga Jual</th>
                <th class="text-right">Margin (Rp)</th>
                <th class="text-right">Margin (%)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $index => $product)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $product->sku }}</td>
                    <td>{{ $product->nama_produk }}</td>
                    <td>{{ $product->category->nama_kategori ?? '-' }}</td>
                    <td class="text-right">Rp {{ number_format($product->biaya_rata_rata, 2, '.', ',') }}</td>
                    <td class="text-right">Rp {{ number_format($product->harga_jual, 2, '.', ',') }}</td>
                    <td class="text-right" style="color: {{ $product->margin_rp >= 0 ? 'green' : 'red' }};">
                        Rp {{ number_format($product->margin_rp, 2, '.', ',') }}
                    </td>
                    <td class="text-right"
                        style="color: {{ $product->margin_pct >= 20 ? 'green' : ($product->margin_pct >= 10 ? 'orange' : 'red') }};">
                        {{ number_format($product->margin_pct, 1) }}%
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
