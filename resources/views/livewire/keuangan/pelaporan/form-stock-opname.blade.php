@extends('layouts.pdf')

@section('content')
    <div style="margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px;">
        <table style="border: none; width: 100%;">
            <tr>
                <td style="border: none; width: 60%; vertical-align: top;">
                    <h2 style="margin: 0; color: #1a5632;">FORM STOCK OPNAME</h2>
                    <p style="margin: 5px 0; font-size: 11pt;">Lembar Kerja Fisik</p>
                </td>
                <td style="border: none; width: 40%; text-align: right; vertical-align: top;">
                    <p style="margin: 0; font-size: 10pt;">Dicetak: {{ date('d F Y H:i') }}</p>
                    <p style="margin: 5px 0; font-size: 10pt;">Oleh: {{ auth()->user()->nama_lengkap }}</p>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-bottom: 20px;">
        <table style="border: none; width: 100%; font-size: 10pt;">
            <tr>
                <td style="border: none; width: 15%;">Lokasi/Rak</td>
                <td style="border: none; width: 35%;">: {{ $shelfName }}</td>
                <td style="border: none; width: 15%;">Kategori</td>
                <td style="border: none; width: 35%;">: {{ $categoryName }}</td>
            </tr>
            <tr>
                <td style="border: none;">Catatan</td>
                <td style="border: none;" colspan="3">: {{ $catatan }}</td>
            </tr>
        </table>
    </div>

    <table style="width: 100%; border-collapse: collapse; font-size: 10pt;">
        <thead>
            <tr>
                <th style="border: 1px solid #000; padding: 8px; width: 5%;">No</th>
                <th style="border: 1px solid #000; padding: 8px; width: 15%;">Kode Produk</th>
                <th style="border: 1px solid #000; padding: 8px; width: 40%;">Nama Produk</th>
                <th style="border: 1px solid #000; padding: 8px; width: 10%;">Sistem</th>
                <th style="border: 1px solid #000; padding: 8px; width: 15%;">Fisik</th>
                <th style="border: 1px solid #000; padding: 8px; width: 15%;">Ket.</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $i => $product)
                <tr>
                    <td style="border: 1px solid #000; padding: 8px; text-align: center;">{{ $i + 1 }}</td>
                    <td style="border: 1px solid #000; padding: 8px;">{{ $product->sku ?? $product->kode_produk }}</td>
                    <td style="border: 1px solid #000; padding: 8px;">{{ $product->nama_produk }}</td>
                    <td style="border: 1px solid #000; padding: 8px; text-align: center; color: #777;">
                        {{ \App\Utils\NumberUtil::format($product->stok_aktual) }}
                    </td>
                    <td style="border: 1px solid #000; padding: 8px;"></td>
                    <td style="border: 1px solid #000; padding: 8px;"></td>
                </tr>
            @endforeach
            @if($products->isEmpty())
                <tr>
                    <td colspan="6" style="border: 1px solid #000; padding: 20px; text-align: center; color: #777;">
                        <i>Tidak ada produk yang dipilih. Silakan filter kategori/rak terlebih dahulu.</i>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <div style="margin-top: 40px;">
        <table style="border: none; width: 100%;">
            <tr>
                <td style="border: none; width: 33%; text-align: center;">
                    <p>Petugas Penghitung</p>
                    <br><br><br>
                    <p>( ____________________ )</p>
                </td>
                <td style="border: none; width: 33%; text-align: center;">
                    <p>Saksi</p>
                    <br><br><br>
                    <p>( ____________________ )</p>
                </td>
                <td style="border: none; width: 33%; text-align: center;">
                    <p>Penanggung Jawab</p>
                    <br><br><br>
                    <p>( ____________________ )</p>
                </td>
            </tr>
        </table>
    </div>

    <div style="position: fixed; bottom: 0; width: 100%; font-size: 8pt; color: #777; text-align: center; border-top: 1px solid #ddd; padding-top: 5px;">
        Form Stock Opname - BumdesMart - Dicetak pada {{ date('d/m/Y H:i:s') }}
    </div>
@endsection
