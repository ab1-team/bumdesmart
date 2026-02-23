<?php

namespace App\Livewire\Keuangan;

use App\Models\Account;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Laporan')]
class Pelaporan extends Component
{
    // Filter Properties
    public $tahun;

    public $bulan;

    public $periode;

    public $jenis_laporan;

    public $jenis_sub_laporan;

    public $daftarAkun = [];

    public function mount()
    {
        $this->tahun = date('Y');
        $this->bulan = date('m');
        $this->periode = '-';

        $this->jenis_laporan = '';
        $this->jenis_sub_laporan = '';

        $account = Account::where('business_id', auth()->user()->business_id)->get();
        $this->daftarAkun = $account->map(fn ($a) => [
            'id' => $a->id,
            'kode' => $a->kode,
            'nama' => $a->nama,
        ])->toArray();
    }

    public function render()
    {
        return view('livewire.keuangan.pelaporan');
    }
}
