<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Owner;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceCetakController extends Controller
{
    public function cetak(Request $request, $invoiceId)
    {
        $invoice = DB::table('invoices')->where('id', $invoiceId)->first();
        if (! $invoice) {
            abort(404, 'Invoice tidak ditemukan');
        }

        $business = Business::withTrashed()->find($invoice->business_id);
        if (! $business || ! $business->owner_id) {
            abort(404, 'Owner tidak ditemukan untuk invoice ini');
        }

        $owner = Owner::findOrFail($business->owner_id);

        $logoAbt = public_path('assets/img/logo/logo_abt.png');
        $logoLunas = public_path('assets/img/logo/lunas.png');
        $ttdSantoso = public_path('assets/img/logo/ttd_santoso.png');

        $base64Abt = $this->toBase64($logoAbt);
        $base64Lunas = $this->toBase64($logoLunas);
        $base64Ttd = $this->toBase64($ttdSantoso);

        $fileUrlAbt = $this->toFileUrl($logoAbt);
        $fileUrlLunas = $this->toFileUrl($logoLunas);
        $fileUrlTtd = $this->toFileUrl($ttdSantoso);

        $logoUrlAbt = url('assets/img/logo/logo_abt.png');
        $logoUrlLunas = url('assets/img/logo/lunas.png');
        $logoUrlTtd = url('assets/img/logo/ttd_santoso.png');

        $sisaTagihan = max(0, (int) $invoice->tagihan - (int) $invoice->saldo);

        $data = [
            'invoice' => $invoice,
            'owner' => $owner,
            'sisaTagihan' => $sisaTagihan,
            'base64Abt' => $base64Abt,
            'base64Lunas' => $base64Lunas,
            'base64Ttd' => $base64Ttd,
            'fileUrlAbt' => $fileUrlAbt,
            'fileUrlLunas' => $fileUrlLunas,
            'fileUrlTtd' => $fileUrlTtd,
            'logoUrlAbt' => $logoUrlAbt,
            'logoUrlLunas' => $logoUrlLunas,
            'logoUrlTtd' => $logoUrlTtd,
        ];

        $html = view('livewire.master.pdf.invoice', $data)->render();

        $pdf = PDF::loadHTML($html)
            ->setPaper('a4')
            ->setOrientation('portrait')
            ->setOption('margin-top', '15mm')
            ->setOption('margin-bottom', '20mm')
            ->setOption('margin-left', '15mm')
            ->setOption('margin-right', '15mm')
            ->setOption('enable-local-file-access', true);

        return $pdf->inline('invoice-'.$invoice->no.'.pdf');
    }

    private function toBase64($path)
    {
        if (! file_exists($path)) {
            return null;
        }
        $type = mime_content_type($path) ?: 'image/'.pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);

        return 'data:'.$type.';base64,'.base64_encode($data);
    }

    private function toFileUrl($path)
    {
        if (! file_exists($path)) {
            return null;
        }

        $real = realpath($path);
        $isWin = DIRECTORY_SEPARATOR === '\\';
        $prefix = $isWin ? 'file:///' : 'file://';

        return $prefix.str_replace('\\', '/', $real);
    }
}