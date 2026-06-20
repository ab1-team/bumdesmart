@extends('layouts.pdf')

@section('content')
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Produk</th>
                <th>Kategori</th>
                <th class="text-center">Stok</th>
                <th class="text-right">Nilai Stok</th>
                <th class="text-center">Terjual (30hr)</th>
                <th class="text-center">Turnover</th>
                <th class="text-center">Days in Inv.</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $index => $product)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $product->nama_produk }}</td>
                    <td>{{ $product->category->nama_kategori ?? '-' }}</td>
                    <td class="text-center">{{ $product->stok_aktual }}</td>
                    <td class="text-right">Rp {{ number_format($product->nilai_stok, 2, '.', ',') }}</td>
                    <td class="text-center">{{ $product->terjual_30hari }}</td>
                    <td class="text-center"
                        style="color: {{ $product->turnover_ratio >= 2 ? 'green' : ($product->turnover_ratio >= 1 ? 'orange' : 'red') }}; font-weight: bold;">
                        {{ $product->turnover_ratio }}x
                    </td>
                    <td class="text-center">
                        {{ $product->days_in_inventory !== null ? $product->days_in_inventory . ' hari' : '-' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 15px; font-size: 9pt; color: #555;">
        <strong>Keterangan:</strong><br>
        🟢 Turnover ≥ 2x = Fast Moving &nbsp;|&nbsp;
        🟡 Turnover 1-2x = Normal &nbsp;|&nbsp;
        🔴 Turnover < 1x=Slow Moving </div>
        @endsection
