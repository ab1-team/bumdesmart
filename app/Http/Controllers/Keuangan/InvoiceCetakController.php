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

        $data = [
            'invoice' => $invoice,
            'owner' => $owner,
            'sisaTagihan' => max(0, (int) $invoice->tagihan - (int) $invoice->saldo),
            'base64Abt' => $this->toBase64(public_path('assets/img/logo/logo_abt.png')),
            'base64Lunas' => $this->toBase64(public_path('assets/img/logo/lunas.png')),
            'base64Ttd' => $this->toBase64(public_path('assets/img/logo/ttd_santoso.png')),
        ];

        $html = view('livewire.master.pdf.invoice', $data)->render();

        $pdf = PDF::loadHTML($html)
            ->setPaper('a4')
            ->setOrientation('portrait')
            ->setOption('margin-top', '15mm')
            ->setOption('margin-bottom', '20mm')
            ->setOption('margin-left', '15mm')
            ->setOption('margin-right', '15mm');

        return $pdf->inline('invoice-'.$invoice->no.'.pdf');
    }

    private function toBase64($path)
    {
        if (! file_exists($path)) {
            return null;
        }
        $type = mime_content_type($path) ?: 'image/'.pathinfo($path, PATHINFO_EXTENSION);

        return 'data:'.$type.';base64,'.base64_encode(file_get_contents($path));
    }
}