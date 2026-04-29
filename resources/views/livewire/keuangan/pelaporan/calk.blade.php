@php
    use App\Utils\KeuanganUtil;

    $saldoAkunLevel1 = [];
    $totalLiabilitasEkuitas = 0;
@endphp

@extends('layouts.pdf')

@section('content')
    <table style="width: 100%; border: 0;">
        @foreach ($akunLevel1s as $akunLevel1)
            <tr style="background-color: #b0b0b0;">
                <td colspan="3" style="text-align: center; border: 0; font-weight: bold;">
                    {{ $akunLevel1->kode }}. {{ $akunLevel1->nama }}
                </td>
            </tr>

            @php
                $saldoAkunLevel1[$akunLevel1->id] = 0;
            @endphp
            @foreach ($akunLevel1->akunLevel2 as $akunLevel2)
                <tr style="background-color: #d0d0d0;">
                    <td style="border: 0; font-weight: bold; width: 10%;">
                        {{ $akunLevel2->kode }}.
                    </td>
                    <td colspan="2" style="border: 0; font-weight: bold;">
                        {{ $akunLevel2->nama }}
                    </td>
                </tr>

                @foreach ($akunLevel2->akunLevel3 as $akunLevel3)
                    <tr style="background-color: #e8e8e8;">
                        <td style="border: 0; padding-left: 15px; font-weight: bold;">
                            {{ $akunLevel3->kode }}.
                        </td>
                        <td colspan="2" style="border: 0; font-style: italic; font-weight: bold;">
                            {{ $akunLevel3->nama }}
                        </td>
                    </tr>

                    @php
                        $saldoAkunLevel3 = 0;
                    @endphp
                    @foreach ($akunLevel3->accounts as $index => $account)
                        @php
                            $saldo = KeuanganUtil::sumSaldo($account, $bulan);
                            if ($account->kode == '3.2.01.01') {
                                $saldo = KeuanganUtil::saldoLabaRugi($tahun, $bulan);
                            }
                            $saldoAkunLevel3 += $saldo;
                            $saldoAkunLevel1[$akunLevel1->id] += $saldo;
                        @endphp

                        <tr style="background-color: {{ $index % 2 == 0 ? '#f9f9f9' : '#ffffff' }};">
                            <td style="border: 0; padding-left: 30px; font-size: 0.9em; width: 15%;">
                                {{ $account->kode }}
                            </td>
                            <td style="border: 0; font-size: 0.9em; width: 55%;">
                                {{ $account->nama }}
                            </td>
                            <td style="border: 0; text-align: right; width: 30%; font-size: 0.9em;">
                                {{ number_format($saldo, 2) }}
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            @endforeach

            <tr style="background-color: #b0b0b0;">
                <td colspan="2" style="text-align: left; border: 0; font-weight: bold;">
                    Jumlah {{ $akunLevel1->nama }}
                </td>
                <td style="text-align: right; border: 0; font-weight: bold;">
                    {{ number_format($saldoAkunLevel1[$akunLevel1->id], 2) }}
                </td>
            </tr>

            @php
                if ($akunLevel1->id == 2 || $akunLevel1->id == 3) {
                    $totalLiabilitasEkuitas += $saldoAkunLevel1[$akunLevel1->id];
                }
            @endphp

            <tr>
                <td style="height: 20px !important; border: 0; padding: 0;"></td>
            </tr>
        @endforeach

        <tr style="background-color: #b0b0b0;">
            <td colspan="2" style="text-align: left; border: 0; font-weight: bold;">
                Jumlah Liabilitas + Ekuitas
            </td>
            <td style="text-align: right; border: 0; font-weight: bold;">
                {{ number_format($totalLiabilitasEkuitas, 2) }}
            </td>
        </tr>
    </table>
@endsection
