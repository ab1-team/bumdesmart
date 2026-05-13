@extends('layouts.pdf')

@section('content')
    <table style="width: 100%; border: 0; border-collapse: collapse;">
        @foreach ($labaRugi as $index => $lr)
            <tr style="background-color: #b0b0b0;">
                <td colspan="5"
                    style="padding: 5px; text-align: center; font-weight: bold; text-transform: uppercase; border: 0;">
                    {{ $lr['nama'] }}
                </td>
            </tr>
            <tr style="background-color: #d0d0d0; font-weight: bold;">
                <td style="width: 12%; padding: 5px; text-align: left; border: 0;">Kode</td>
                <td style="width: 34%; padding: 5px; text-align: left; border: 0;">Nama Akun</td>
                <td style="width: 18%; padding: 5px; text-align: right; border: 0;">S/D Bln Lalu</td>
                <td style="width: 18%; padding: 5px; text-align: right; border: 0;">Bln Ini</td>
                <td style="width: 18%; padding: 5px; text-align: right; border: 0;">S/D Bln Ini</td>
            </tr>

            @foreach ($lr['kode'] as $index2 => $kode)
                @php
                    $isHeader = !empty($kode['is_bold']);
                    $bgColor = $index2 % 2 == 0 ? '#f0f0f0' : '#fefefe';
                    if ($isHeader) {
                        $bgColor = '#e0e0e0';
                    }
                @endphp
                <tr style="background-color: {{ $bgColor }};">
                    <td style="padding: 4px; text-align: left; border: 0;">
                        {{ $kode['kode'] }}
                    </td>
                    <td style="padding: 4px; {{ $isHeader ? 'font-weight: bold;' : '' }} border: 0;">
                        {{ $kode['nama'] }}
                    </td>
                    <td style="padding: 4px; text-align: right; {{ $isHeader ? 'font-weight: bold;' : '' }} border: 0;">
                        {{ number_format($kode['saldo_sd_lalu'], 2) }}
                    </td>
                    <td style="padding: 4px; text-align: right; {{ $isHeader ? 'font-weight: bold;' : '' }} border: 0;">
                        {{ number_format($kode['saldo_bulan_ini'], 2) }}
                    </td>
                    <td style="padding: 4px; text-align: right; {{ $isHeader ? 'font-weight: bold;' : '' }} border: 0;">
                        {{ number_format($kode['saldo_sd_ini'], 2) }}
                    </td>
                </tr>
            @endforeach

            @if ($index >= 0)
                <tr style="background-color: #d0d0d0;">
                    <td colspan="2" style="padding: 5px; font-weight: bold; text-align: left; border: 0;">Total
                        {{ $lr['nama'] }}</td>
                    <td style="padding: 5px; text-align: right; font-weight: bold; border: 0;">
                        {{ number_format($lr['jumlah_sd_lalu'], 2) }}
                    </td>
                    <td style="padding: 5px; text-align: right; font-weight: bold; border: 0;">
                        {{ number_format($lr['jumlah_bulan_ini'], 2) }}
                    </td>
                    <td style="padding: 5px; text-align: right; font-weight: bold; border: 0;">
                        {{ number_format($lr['jumlah_sd_ini'], 2) }}
                    </td>
                </tr>
            @endif

            @php
                $footerLabel = null;
                $secondaryFooter = null;
                if ($index == 0) {
                    $footerLabel = 'Total Pendapatan';
                } elseif ($index == 1) {
                    $footerLabel = 'LABA KOTOR';
                } elseif ($index == 2) {
                    $footerLabel = 'Total Beban';
                    $secondaryFooter = 'Laba Sebelum Pajak';
                } elseif ($index == 3) {
                    $footerLabel = 'Laba Bersih';
                }
            @endphp

            @if ($footerLabel)
                <tr style="background-color: #b0b0b0;">
                    <td colspan="2"
                        style="padding: 5px; font-weight: bold; text-align: left; text-transform: uppercase; border: 0;">
                        {{ $footerLabel }}
                    </td>
                    <td style="padding: 5px; text-align: right; font-weight: bold; border: 0;">
                        {{ number_format($lr['total_sd_lalu'], 2) }}
                    </td>
                    <td style="padding: 5px; text-align: right; font-weight: bold; border: 0;">
                        {{ number_format($lr['total_bulan_ini'], 2) }}
                    </td>
                    <td style="padding: 5px; text-align: right; font-weight: bold; border: 0;">
                        {{ number_format($lr['total_sd_ini'], 2) }}
                    </td>
                </tr>
            @endif

            @if ($secondaryFooter)
                <tr style="background-color: #a0a0a0;">
                    <td colspan="2" style="padding: 5px; font-weight: bold; text-align: left; text-transform: uppercase; border: 0;">
                        {{ $secondaryFooter }}
                    </td>
                    <td style="padding: 5px; text-align: right; font-weight: bold; border: 0;">
                        {{ number_format($lr['total_sd_lalu'], 2) }}
                    </td>
                    <td style="padding: 5px; text-align: right; font-weight: bold; border: 0;">
                        {{ number_format($lr['total_bulan_ini'], 2) }}
                    </td>
                    <td style="padding: 5px; text-align: right; font-weight: bold; border: 0;">
                        {{ number_format($lr['total_sd_ini'], 2) }}
                    </td>
                </tr>
            @endif

            <tr>
                <td colspan="5" style="height: 10px; border: 0;"></td>
            </tr>
        @endforeach
    </table>
@endsection
