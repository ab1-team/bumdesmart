@php
    $totalArusKas = 0;
@endphp

@extends('layouts.pdf')

@section('content')
    <table style="width: 100%; border: 0; ">
        <tr>
            <td colspan="2">Nama Akun</td>
            <td>Saldo</td>
        </tr>
        <tr>
            <td colspan="3"></td>
        </tr>

        @foreach ($arusKas as $index => $ak)
            @if ($ak['header'])
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $ak['header']->nama_akun }}</td>
                    <td>
                        @if ($index == 0)
                            {{ number_format($saldoKas, 2, ',', '.') }}
                        @endif
                    </td>
                </tr>
            @endif

            @php
                $grandTotal = [];
            @endphp
            @foreach ($ak['groups'] as $indexGroup => $group)
                <tr>
                    <td colspan="3"></td>
                </tr>

                @if ($group['subheader'])
                    <tr>
                        <td></td>
                        <td>{{ $group['subheader']->nama_akun }}</td>
                        <td></td>
                    </tr>
                @endif

                @php
                    $total = 0;
                @endphp
                @foreach ($group['items'] as $item)
                    <tr>
                        <td></td>
                        <td>{{ $item->nama_akun }}</td>
                        <td>{{ number_format($item->total, 2, ',', '.') }}</td>
                    </tr>

                    @php
                        $total += $item->total;
                    @endphp
                @endforeach

                @php
                    $titleJumlah = $ak['header']->nama_akun ?? '';
                    if ($group['subheader']) {
                        $titleJumlah = $group['subheader']->nama_akun;
                    }
                    $grandTotal[$indexGroup] = $total;
                @endphp

                <tr>
                    <td></td>
                    <td>Jumlah {{ $titleJumlah }}</td>
                    <td>{{ number_format($total, 2, ',', '.') }}</td>
                </tr>
            @endforeach

            @if ($index > 0)
                @php
                    $totalBawah = 0;
                    foreach ($grandTotal as $indexGrandTotal => $jumlahBawah) {
                        if ($indexGrandTotal == 0) {
                            $totalBawah += $jumlahBawah;
                        } else {
                            $totalBawah -= $jumlahBawah;
                        }
                    }

                    $totalArusKas += $totalBawah;
                @endphp

                <tr>
                    <td></td>
                    <td>
                        @if ($index == 1)
                            Kas Bersih yang diperoleh dari aktivitas Operasi (A-B)
                        @elseif ($index == 2)
                            Kas Bersih yang diperoleh dari aktivitas Investasi (A-B)
                        @elseif ($index == 3)
                            Kas Bersih yang diperoleh dari aktivitas Pendanaan (A-B)
                        @endif
                    </td>
                    <td>{{ number_format($totalBawah, 2, ',', '.') }}</td>
                </tr>
            @endif

            <tr>
                <td colspan="3"></td>
            </tr>
        @endforeach

        <tr>
            <td></td>
            <td>II.  Kenaikan/(Penurunan) Kas dan Setara Kas (Nilai 1C + 2C + 3C)</td>
            <td>{{ number_format($totalArusKas, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td></td>
            <td>SALDO AKHIR KAS SETARA KAS (Nilai I + II)</td>
            <td>{{ number_format($totalArusKas + $saldoKas, 2, ',', '.') }}</td>
        </tr>
    </table>
@endsection
