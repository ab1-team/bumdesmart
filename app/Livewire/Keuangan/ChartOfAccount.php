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

    public function toggleDefault($accountId, $type)
    {
        $account = \App\Models\Account::find($accountId);
        if (!$account) return;

        $field = ($type == 'transfer') ? 'is_default_transfer' : 'is_default_qris';
        
        // If already default, just toggle off
        if ($account->$field) {
            $account->update([$field => false]);
        } else {
            // Reset other defaults for this type
            \App\Models\Account::where('business_id', auth()->user()->business_id)
                ->update([$field => false]);
            $account->update([$field => true]);
        }
        
        $this->dispatch('alert', type: 'success', message: 'Default berhasil diperbarui!');
    }
}
