<?php

namespace App\Livewire\Keuangan;

use App\Models\AkunLevel1;
use Livewire\Component;

class ChartOfAccount extends Component
{
    public function render()
    {
        $coa = AkunLevel1::with([
            'akunLevel2' => function ($q) {
                $q->orderBy('kode');
            },
            'akunLevel2.akunLevel3' => function ($q) {
                $q->orderBy('kode');
            },
            'akunLevel2.akunLevel3.accounts' => function ($q) {
                $q->orderBy('kode');
            }
        ])->orderBy('kode')->get();

        return view('livewire.keuangan.chart-of-account', [
            'coa' => $coa
        ])->layout('layouts.app', ['title' => 'Chart of Accounts']);
    }
}
