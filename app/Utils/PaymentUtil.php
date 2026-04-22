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
            $rekeningDebit = ($jenisPembayaran == 'credit' || $jenisPembayaran == 'preorder') ? '1.1.04.01' : '1.1.03.01';

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
            // Revenue Entry (Gross)
            $rekeningRevenue = '4.1.01.01'; // Penjualan
            $rekeningAsset = $rekeningKas;  // Kas / Bank
            if ($jenisPembayaran == 'credit') {
                $rekeningAsset = '1.1.04.01'; // Piutang
            }

            $return['sales'] = [
                'rekening_kredit' => $rekeningRevenue,
                'rekening_debit' => $rekeningAsset,
            ];

            // COGS Entry (HPP)
            $return['hpp'] = [
                'rekening_kredit' => '1.1.03.01', // Persediaan
                'rekening_debit' => '5.1.01.01',  // Beban Pokok Pendapatan
            ];

            // Discount
            $return['sales-diskon'] = [
                'rekening_kredit' => $rekeningAsset,
                'rekening_debit' => '5.1.01.02', // Beban Diskon
            ];

            // Cashback
            $return['sales-cashback'] = [
                'rekening_kredit' => $rekeningAsset,
                'rekening_debit' => '4.1.01.02', // Pendapatan Cashback (or Expense if given to customer?)
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
