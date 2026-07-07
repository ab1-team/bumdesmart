<?php

namespace App\Livewire\Master;

use App\Models\Owner;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class MasterDashboard extends Component
{
    public $totalOwners = 0;

    public $totalInvoices = 0;

    public $totalUnpaid = 0;

    public $totalTagihan = 0;

    public $recentInvoices = [];

    public function mount()
    {
        $this->totalOwners = Owner::count();

        $totalInvoices = 0;
        $totalUnpaid = 0;
        $totalTagihan = 0;
        $recent = [];

        foreach (Owner::all() as $owner) {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
            tenancy()->initialize($owner);

            try {
                $totalInvoices += DB::table('invoices')->count();
                $totalUnpaid += DB::table('invoices')->where('status', 'UNPAID')->count();
                $totalTagihan += (float) DB::table('invoices')->sum('tagihan');

                $rows = DB::table('invoices')
                    ->orderByDesc('created_at')
                    ->limit(5)
                    ->get(['id', 'business_id', 'no', 'jenis_pembayaran', 'tanggal_invoice', 'tagihan', 'saldo', 'status']);

                foreach ($rows as $row) {
                    $row->nama_usaha = $owner->nama_usaha;
                    $row->owner_id = $owner->id;
                    $recent[] = (array) $row;
                }
            } finally {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }
        }

        usort($recent, fn ($a, $b) => strcmp($b['tanggal_invoice'] ?? '', $a['tanggal_invoice'] ?? ''));
        $this->recentInvoices = array_slice($recent, 0, 5);

        $this->totalInvoices = $totalInvoices;
        $this->totalUnpaid = $totalUnpaid;
        $this->totalTagihan = $totalTagihan;
    }

    #[Layout('layouts.app')]
    #[Title('Master Dashboard')]
    public function render()
    {
        return view('livewire.master.master-dashboard');
    }
}