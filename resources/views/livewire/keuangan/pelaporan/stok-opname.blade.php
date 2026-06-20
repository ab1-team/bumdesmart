@extends('layouts.pdf')

@section('content')
    @forelse ($opnames as $opname)
        <h3 style="margin-top: 15px; margin-bottom: 5px;">
            {{ $opname->no_opname }}
            <span style="font-size: 9pt; color: #777;">
                | {{ \Carbon\Carbon::parse($opname->tanggal_opname)->format('d/m/Y') }}
                | Status: {{ $opname->status }}
                | Petugas: {{ $opname->user->name ?? '-' }}
            </span>
        </h3>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Produk</th>
                    <th class="text-center">Stok Sistem</th>
                    <th class="text-center">Stok Fisik</th>
                    <th class="text-center">Selisih</th>
                    <th>Jenis</th>
                    <th class="text-right">Nilai Selisih</th>
                    <th>Alasan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($opname->details as $detail)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>{{ $detail->product->nama_produk ?? '-' }}</td>
                        <td class="text-center">{{ $detail->stok_sistem }}</td>
                        <td class="text-center">{{ $detail->stok_fisik }}</td>
                        <td class="text-center"
                            style="color: {{ $detail->selisih < 0 ? 'red' : ($detail->selisih > 0 ? 'green' : 'black') }}; font-weight: bold;">
                            {{ $detail->selisih > 0 ? '+' : '' }}{{ $detail->selisih }}
                        </td>
                        <td>{{ $detail->jenis_selisih }}</td>
                        <td class="text-right">Rp {{ number_format($detail->total_harga, 2, '.', ',') }}</td>
                        <td>{{ $detail->alasan ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p style="text-align: center; color: #999;">Tidak ada data stok opname pada periode ini.</p>
    @endforelse
@endsection
