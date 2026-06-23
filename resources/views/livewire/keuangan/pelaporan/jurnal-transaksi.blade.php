@extends('layouts.pdf')

@section('content')
    <table style="width: 100%; border: 0;">
        <tr style="background-color: #b0b0b0; font-weight: bold;">
            <td style="width: 5%; text-align: center; border: 0;">No</td>
            <td style="width: 15%; text-align: center; border: 0;">Tanggal</td>
            <td style="width: 5%; text-align: center; border: 0;">Ref ID.</td>
            <td style="width: 10%; text-align: center; border: 0;">Kode Akun</td>
            <td style="width: 30%; text-align: center; border: 0;">Keterangan</td>
            <td style="width: 15%; text-align: center; border: 0;">Debit</td>
            <td style="width: 15%; text-align: center; border: 0;">Kredit</td>
            <td style="width: 5%; text-align: center; border: 0;">Ins</td>
        </tr>

        @php
            $totalDebit = 0;
            $totalKredit = 0;
        @endphp
        @forelse ($payments as $index => $payment)
            @php
                $totalDebit += $payment->total_harga;
                $totalKredit += $payment->total_harga;
            @endphp
            <tr style="background-color: {{ $index % 2 == 0 ? '#f0f0f0' : '#fefefe' }};">
                <td rowspan="2" style="text-align: center; border: 0;">{{ $loop->iteration }}</td>
                <td rowspan="2" style="text-align: center; border: 0;">
                    {{ date('Y-m-d', strtotime($payment->tanggal_pembayaran)) }}
                </td>
                <td rowspan="2" style="text-align: center; border: 0;">{{ $payment->transaction_id }}</td>
                <td style="text-align: center; border: 0;">{{ $payment->rekening_debit }}</td>
                <td style="border: 0;">
                    {{ $payment->accountDebit->nama }}
                </td>
                <td style="text-align: right; border: 0;">{{ number_format($payment->total_harga, 2, '.', ',') }}</td>
                <td style="text-align: right; border: 0;">0</td>
                <td rowspan="2" style="text-align: center; border: 0;">{{ $payment->user->initial }}</td>
            </tr>

            <tr style="background-color: {{ $index % 2 == 0 ? '#f0f0f0' : '#fefefe' }};">
                <td style="text-align: center; border: 0;">{{ $payment->rekening_kredit }}</td>
                <td style="border: 0;">
                    {{ $payment->accountKredit->nama }}
                </td>
                <td style="text-align: right; border: 0;">0</td>
                <td style="text-align: right; border: 0;">{{ number_format($payment->total_harga, 2, '.', ',') }}</td>
            </tr>
        @empty
            <tr style="background-color: {{ $index % 2 == 0 ? '#f0f0f0' : '#fefefe' }};">
                <td colspan="8" style="text-align: center; border: 0;">Tidak ada data</td>
            </tr>
        @endforelse

        <tr style="background-color: #d0d0d0; font-weight: bold;">
            <td colspan="5" style="text-align: right; border: 0;">Total</td>
            <td style="text-align: right; border: 0;">{{ number_format($totalDebit, 2, '.', ',') }}</td>
            <td style="text-align: right; border: 0;">{{ number_format($totalKredit, 2, '.', ',') }}</td>
            <td style="border: 0;"></td>
        </tr>
    </table>
@endsection
