<?php

namespace App\Livewire\Master;

use App\Models\Business;
use App\Models\Owner;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Dashboard extends Component
{
    #[Layout('layouts.app')]
    #[Title('Master Dashboard')]
    public function render()
    {
        $totalBusiness = Business::count();
        $totalOwner = Owner::count();

        return view('livewire.master.dashboard', [
            'totalBusiness' => $totalBusiness,
            'totalOwner'    => $totalOwner,
        ]);
    }
}
