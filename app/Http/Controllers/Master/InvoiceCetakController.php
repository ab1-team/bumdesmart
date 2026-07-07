<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceCetakController extends Controller
{
    public function cetak(Request $request, $invoiceId, $ownerId)
    {
        $owner = Owner::findOrFail($ownerId);

        if (tenancy()->initialized) {
            tenancy()->end();
        }
        tenancy()->initialize($owner);

        try {
            $invoice = DB::table('invoices')->where('id', $invoiceId)->first();
            if (! $invoice) {
                abort(404, 'Invoice tidak ditemukan');
            }
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        }

        $data = [
            'invoice' => $invoice,
            'owner' => $owner,
            'sisaTagihan' => max(0, (int) $invoice->tagihan - (int) $invoice->saldo),
            'base64Abt' => $this->toBase64(resource_path('logo_invoice/logo_abt.png')),
            'logoAbtUri' => $this->toFileUri(resource_path('logo_invoice/logo_abt.png')),
            'base64Lunas' => $this->toBase64(resource_path('logo_invoice/lunas.png')),
            'logoLunasUri' => $this->toFileUri(resource_path('logo_invoice/lunas.png')),
            'base64Ttd' => $this->toBase64(resource_path('logo_invoice/ttd_santoso.png')),
            'logoTtdUri' => $this->toFileUri(resource_path('logo_invoice/ttd_santoso.png')),
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
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $map = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'svg' => 'image/svg+xml'];
        $type = $map[$ext] ?? (mime_content_type($path) ?: 'image/png');

        return 'data:'.$type.';base64,'.base64_encode(file_get_contents($path));
    }

    private function toFileUri($path)
    {
        if (! file_exists($path)) {
            return null;
        }
        $real = realpath($path);
        $uri = 'file:///'.str_replace('\\', '/', $real);

        return str_replace(' ', '%20', $uri);
    }
}