@extends('layouts.pdf')

@section('content')
    <div style="margin-bottom: 20px;">
        <strong>Deskripsi:</strong><br>
        Daftar produk yang stoknya berada di bawah batas minimum dan memerlukan pemesanan ulang (reorder).
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Produk</th>
                <th>Nama Produk</th>
                <th>Stok Saat Ini</th>
                <th>Stok Minimum</th>
                <th>Defisit</th>
                <th>Saran Order</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $index => $product)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>{{ $product->sku ?? ($product->product_code ?? '-') }}</td>
                    <td>{{ $product->nama_produk ?? $product->product_name }}</td>
                    <td style="text-align: center;">{{ \App\Utils\NumberUtil::formatQty($product->stok_aktual) }}</td>
                    <td style="text-align: center;">{{ \App\Utils\NumberUtil::formatQty($product->stok_minimal) }}</td>
                    <td style="text-align: center; color: red; font-weight: bold;">{{ \App\Utils\NumberUtil::formatQty($product->kekurangan) }}</td>
                    <td style="text-align: center; color: blue;">{{ \App\Utils\NumberUtil::formatQty($product->suggested_order) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
