@extends('layouts.pdf')

@section('content')
    <div style="margin-bottom: 20px;">
        <strong>Deskripsi:</strong><br>
        Laporan posisi stok produk pada periode terpilih, mencakup stok awal periode, mutasi masuk/keluar, dan stok akhir.
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>SKU</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th>Satuan</th>
                <th>Rak</th>
                <th>Stok Awal</th>
                <th>Masuk</th>
                <th>Keluar</th>
                <th>Stok Akhir</th>
                <th>HPP</th>
                <th>Nilai Stok</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $index => $p)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>{{ $p->sku ?? '-' }}</td>
                    <td>{{ $p->nama_produk }}</td>
                    <td>{{ $p->category->nama_kategori ?? '-' }}</td>
                    <td style="text-align: center;">{{ $p->unit->nama_satuan ?? '-' }}</td>
                    <td style="text-align: center;">{{ $p->shelf->nama_rak ?? '-' }}</td>
                    <td style="text-align: center;">{{ $p->stok_awal_periode }}</td>
                    <td style="text-align: center; color: green;">{{ $p->stok_masuk }}</td>
                    <td style="text-align: center; color: red;">{{ $p->stok_keluar }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ $p->stok_akhir }}</td>
                    <td style="text-align: right;">{{ number_format($p->biaya_rata_rata, 0, ',', '.') }}</td>
                    <td style="text-align: right;">{{ number_format($p->nilai_stok, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="9" style="text-align: right;">Total</th>
                <th style="text-align: center;">{{ $summary['total_stok_akhir'] }}</th>
                <th></th>
                <th style="text-align: right;">{{ number_format($summary['total_nilai_stok'], 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 20px; font-size: 11px;">
        <strong>Ringkasan:</strong><br>
        Total Produk: {{ $summary['total_produk'] }} item<br>
        Total Nilai Stok: Rp {{ number_format($summary['total_nilai_stok'], 0, ',', '.') }}
    </div>
@endsection
