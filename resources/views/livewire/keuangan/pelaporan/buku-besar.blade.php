@php
    $saldoAwalDebit = $akun->balance->debit_00;
    $saldoAwalKredit = $akun->balance->kredit_00;
    $saldoAwal = $saldoAwalDebit - $saldoAwalKredit;

    $bulanLalu = $bulan - 1;
    $debitBulanLalu = 'debit_' . str_pad($bulanLalu, 2, '0', STR_PAD_LEFT);
    $kreditBulanLalu = 'kredit_' . str_pad($bulanLalu, 2, '0', STR_PAD_LEFT);

    $saldoBulanLaluDebit = 0;
    $saldoBulanLaluKredit = 0;
    $saldoBulanLalu = 0;
    if ($bulanLalu > 0) {
        $saldoBulanLaluDebit = $akun->balance->$debitBulanLalu;
        $saldoBulanLaluKredit = $akun->balance->$kreditBulanLalu;
        $saldoBulanLalu = $saldoBulanLaluDebit - $saldoBulanLaluKredit;
    }

    $totalDebit = 0;
    $totalKredit = 0;
    $totalSaldo = $saldoBulanLalu;
@endphp

@extends('layouts.pdf')

@section('content')
    <table style="width: 100%; border: 0; ">
        <tr style="background-color: #b0b0b0;">
            <td style="font-weight: bold; width: 5%; border: 0;">No</td>
            <td style="font-weight: bold; width: 15%; border: 0;">Tanggal</td>
            <td style="font-weight: bold; width: 5%; border: 0;">Ref</td>
            <td style="font-weight: bold; width: 40%; border: 0;">Keterangan</td>
            <td style="font-weight: bold; width: 10%; border: 0;">Debit</td>
            <td style="font-weight: bold; width: 10%; border: 0;">Kredit</td>
            <td style="font-weight: bold; width: 10%; border: 0;">Saldo</td>
            <td style="font-weight: bold; width: 5%; border: 0;">P</td>
        </tr>
        <tr style="background-color: #f0f0f0">
            <td style="border: 0;"></td>
            <td style="text-align: center; border: 0;">{{ $tahun . '-01-01' }}</td>
            <td style="border: 0;"></td>
            <td style="border: 0;">Komulatif Transaksi Awal Tahun {{ $tahun }}</td>
            <td style="border: 0;">{{ number_format($saldoAwalDebit, 2) }}</td>
            <td style="border: 0;">{{ number_format($saldoAwalKredit, 2) }}</td>
            <td style="border: 0;">
                @if ($saldoAwal < 0)
                    ({{ number_format($saldoAwal * -1, 2) }})
                @else
                    {{ number_format($saldoAwal, 2) }}
                @endif
            </td>
            <td style="border: 0;"></td>
        </tr>
        <tr style="background-color: #fefefe">
            <td style="border: 0;"></td>
            <td style="text-align: center; border: 0;">{{ $tahun . '-' . $bulan . '-01' }}</td>
            <td style="border: 0;"></td>
            <td style="border: 0;">Komulatif Transaksi s/d Bulan Lalu</td>
            <td style="border: 0;">{{ number_format($saldoBulanLaluDebit, 2) }}</td>
            <td style="border: 0;">{{ number_format($saldoBulanLaluKredit, 2) }}</td>
            <td style="border: 0;">
                @if ($saldoBulanLalu < 0)
                    ({{ number_format($saldoBulanLalu * -1, 2) }})
                @else
                    {{ number_format($saldoBulanLalu, 2) }}
                @endif
            </td>
            <td style="border: 0;"></td>
        </tr>
        @foreach ($payments as $index => $payment)
            @php
                $debit = 0;
                $kredit = 0;

                if ($payment->rekening_debit == $akun->kode) {
                    $debit = $payment->total_harga;
                }

                if ($payment->rekening_kredit == $akun->kode) {
                    $kredit = $payment->total_harga;
                }

                if ($akun->jenis_mutasi == 'debit') {
                    $saldo = $debit - $kredit;
                } else {
                    $saldo = $kredit - $debit;
                }

                $totalDebit += $debit;
                $totalKredit += $kredit;
                $totalSaldo += $saldo;
            @endphp

            <tr style="background-color: {{ $index % 2 == 0 ? '#f0f0f0' : '#fefefe' }};">
                <td style="text-align: center; border: 0;">{{ $loop->iteration }}</td>
                <td style="text-align: center; border: 0;">{{ date('Y-m-d', strtotime($payment->tanggal_pembayaran)) }}
                </td>
                <td style="text-align: center; border: 0;">{{ $payment->id }}</td>
                <td style="border: 0;">{{ $payment->catatan }}</td>
                <td style="text-align: right; border: 0;">{{ number_format($debit, 2) }}</td>
                <td style="text-align: right; border: 0;">{{ number_format($kredit, 2) }}</td>
                <td style="text-align: right; border: 0;">
                    @if ($totalSaldo < 0)
                        ({{ number_format($totalSaldo * -1, 2) }})
                    @else
                        {{ number_format($totalSaldo, 2) }}
                    @endif
                </td>
                <td style="text-align: center; border: 0;">{{ $payment->p }}</td>
            </tr>
        @endforeach

        <tr style="background-color: #d0d0d0;">
            <td colspan="4" style="text-align: left; border: 0; font-weight: bold;">
                Total Transaksi Bulan {{ $namaBulan }}
            </td>
            <td style="text-align: right; border: 0; font-weight: bold;">
                {{ number_format($totalDebit, 2) }}
            </td>
            <td style="text-align: right; border: 0; font-weight: bold;">
                {{ number_format($totalKredit, 2) }}
            </td>
            <td style="text-align: right; border: 0; font-weight: bold;" rowspan="3">
                @if ($totalSaldo < 0)
                    ({{ number_format($totalSaldo * -1, 2) }})
                @else
                    {{ number_format($totalSaldo, 2) }}
                @endif
            </td>
            <td style="text-align: center; border: 0;"></td>
        </tr>
        <tr style="background-color: #d0d0d0;">
            <td colspan="4" style="text-align: left; border: 0; font-weight: bold;">
                Total Transaksi Sampai Dengan Bulan {{ $namaBulan }}
            </td>
            <td style="text-align: right; border: 0; font-weight: bold;">
                {{ number_format($totalDebit + $saldoBulanLaluDebit, 2) }}
            </td>
            <td style="text-align: right; border: 0; font-weight: bold;">
                {{ number_format($totalKredit + $saldoBulanLaluKredit, 2) }}
            </td>
            <td style="text-align: center; border: 0;"></td>
        </tr>
        <tr style="background-color: #d0d0d0;">
            <td colspan="4" style="text-align: left; border: 0; font-weight: bold;">
                Total Transaksi Komulatif Sampai Dengan {{ $tahun }}
            </td>
            <td style="text-align: right; border: 0; font-weight: bold;">
                {{ number_format($totalDebit + $saldoBulanLaluDebit + $saldoAwalDebit, 2) }}
            </td>
            <td style="text-align: right; border: 0; font-weight: bold;">
                {{ number_format($totalKredit + $saldoBulanLaluKredit + $saldoAwalKredit, 2) }}
            </td>
            <td style="text-align: center; border: 0;"></td>
        </tr>
    </table>
@endsection
