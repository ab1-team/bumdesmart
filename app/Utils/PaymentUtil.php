<?php

namespace App\Utils;

use App\Models\Account;

class PaymentUtil
{
    public static function ambilRekening($jenisTransaksi = 'purchase', $jenisPembayaran = 'cash', $metodeBayar = '', $noRekening = null)
    {
        $rekeningKas = '1.1.01.01';
        if ($metodeBayar == 'transfer') {
            $rekeningKas = '1.1.01.03';
            if ($noRekening) {
                $rekeningBank = Account::where('business_id', auth()->user()->business_id)
                    ->where('no_rek_bank', $noRekening)
                    ->first();

                if ($rekeningBank) {
                    $rekeningKas = $rekeningBank->kode;
                }
            }
        }

        $return = [];
        if ($jenisTransaksi == 'purchase') {
            $rekeningKredit = $rekeningKas;
            $rekeningDebit = '1.1.03.01';

            if ($jenisPembayaran == 'credit') {
                $rekeningKredit = $rekeningKas;
            }

            $return['purchase'] = [
                'rekening_kredit' => $rekeningKredit,
                'rekening_debit' => $rekeningDebit,
            ];

            $rekeningDebit = '5.1.01.02';
            $return['purchase-diskon'] = [
                'rekening_kredit' => $rekeningDebit,
                'rekening_debit' => $rekeningKredit,
            ];

            $rekeningDebit = '4.1.01.02';
            $return['purchase-cashback'] = [
                'rekening_kredit' => $rekeningKredit,
                'rekening_debit' => $rekeningDebit,
            ];
        }

        if ($jenisTransaksi == 'sales') {
            $rekeningKredit = '1.1.03.01';
            $rekeningDebit = $rekeningKas;

            if ($jenisPembayaran == 'credit') {
                $rekeningDebit = '1.1.04.01';
            }

            $return['sales'] = [
                'rekening_kredit' => $rekeningKredit,
                'rekening_debit' => $rekeningDebit,
            ];

            $return['laba'] = [
                'rekening_kredit' => '4.1.01.01',
                'rekening_debit' => $rekeningDebit,
            ];

            $rekeningDebit = '5.1.01.02';
            $return['sales-diskon'] = [
                'rekening_kredit' => $rekeningKas,
                'rekening_debit' => $rekeningDebit,
            ];

            $rekeningDebit = '4.1.01.02';
            $return['sales-cashback'] = [
                'rekening_kredit' => $rekeningKas,
                'rekening_debit' => $rekeningDebit,
            ];
        }

        if ($jenisTransaksi == 'stock_opname' || $jenisTransaksi == 'stock_adjustment') {
            // Rekening Persediaan
            $rekeningPersediaan = '1.1.03.01';
            // Laba/Rugi Penyesuaian Stok
            $rekeningVarians = '6.1.09.01';

            $return['variance'] = [
                'rekening_debit' => $rekeningVarians,
                'rekening_kredit' => $rekeningPersediaan,
            ];
        }

        return $return;
    }
}
