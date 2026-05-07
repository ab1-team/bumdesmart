<?php

namespace App\Livewire\Master;

use App\Models\Owner;
use App\Traits\WithTable;
use App\Utils\TableUtil;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

class MasterOwner extends Component
{
    use WithTable;

    public $titleModal;

    public $id;
    public $namaUsaha;
    public $tanggalPenggunaan;
    public $domain;
    public $domainAlternatif;

    protected function rules()
    {
        return [
            'namaUsaha'         => 'required|string|max:255',
            'tanggalPenggunaan' => 'required|date',
            'domain'            => 'required|string|max:255|unique:domains,domain,' . $this->id,
            'domainAlternatif'  => 'nullable|string|max:255',
        ];
    }

    public function resetForm()
    {
        $this->reset('id', 'namaUsaha', 'tanggalPenggunaan', 'domain', 'domainAlternatif');
    }

    public function create()
    {
        $this->resetForm();
        $this->resetValidation();
        $this->tanggalPenggunaan = now()->toDateString();
        $this->titleModal = 'Tambah Owner';
        $this->dispatch('show-modal', modalId: 'masterOwnerModal');
    }

    public function edit($id)
    {
        $this->resetForm();
        $this->resetValidation();
        $this->titleModal = 'Ubah Owner';

        $owner = Owner::with('domains')->findOrFail($id);
        $this->id                 = $owner->id;
        $this->namaUsaha          = $owner->nama_usaha;
        $this->tanggalPenggunaan  = $owner->tanggal_penggunaan;
        
        // Load domains from relationship
        $domains = $owner->domains->pluck('domain')->toArray();
        $this->domain             = $domains[0] ?? null;
        $this->domainAlternatif   = $domains[1] ?? null;

        $this->dispatch('show-modal', modalId: 'masterOwnerModal');
    }

    public function store()
    {
        $this->validate();

        $data = [
            'nama_usaha'        => $this->namaUsaha,
            'tanggal_penggunaan'=> $this->tanggalPenggunaan,
            'logo'              => 'logo.png',
        ];

        if (!$this->id) {
            $data['id'] = Str::slug($this->namaUsaha, '_');
        }

        if ($this->id) {
            $owner = Owner::findOrFail($this->id);
            $owner->update($data);
            
            // Sync domains ONLY in the domains table
            $owner->domains()->delete();
            if ($this->domain) {
                $owner->domains()->create(['domain' => $this->domain]);
            }
            if ($this->domainAlternatif) {
                $owner->domains()->create(['domain' => $this->domainAlternatif]);
            }

            $message = 'Owner berhasil diubah';
        } else {
            // 1. Create Owner (Tenant)
            $owner = Owner::create($data);
            
            // 2. Create domains
            if ($this->domain) {
                $owner->domains()->create(['domain' => $this->domain]);
            }
            if ($this->domainAlternatif) {
                $owner->domains()->create(['domain' => $this->domainAlternatif]);
            }

            // 3. Create Central Business Record (Agar Dashboard Pusat tidak 0)
            \App\Models\Business::create([
                'owner_id'   => $owner->id,
                'nama_usaha' => $this->namaUsaha,
                'alamat'     => '-',
                'no_telp'    => '-',
                'email'      => Str::slug($this->namaUsaha) . '@bumdes.com',
            ]);

            $message = "Owner & Bisnis Berhasil Dibuat. Silahkan masuk ke menu Master Business untuk melengkapi data dan sinkronisasi database operasional.";
        }

        $this->dispatch('hide-modal', modalId: 'masterOwnerModal');
        $this->dispatch('alert', type: 'success', message: $message);
        $this->resetForm();
    }

    #[On('delete-confirmed')]
    public function destroy($id)
    {
        $owner = Owner::find($id);
        if ($owner) {
            if ($owner->businesses()->count() > 0) {
                $this->dispatch('alert', type: 'error', message: 'Owner tidak bisa dihapus karena masih memiliki business terdaftar.');
                return;
            }
            // domains will be deleted automatically if cascade is set, or manually here
            $owner->domains()->delete();
            $owner->delete();
            $this->dispatch('alert', type: 'success', message: 'Owner berhasil dihapus');
        }
    }

    #[Layout('layouts.app')]
    #[Title('Master Owner')]
    public function render()
    {
        $query = Owner::withCount('businesses')->with('domains');

        $headers = [
            TableUtil::setTableHeader('id', '#', false, false),
            TableUtil::setTableHeader('nama_usaha', 'Nama Usaha', true, true),
            TableUtil::setTableHeader('tanggal_penggunaan', 'Tgl. Penggunaan', true, true),
            TableUtil::setTableHeader('domains_list', 'Domains (Primary & Alt)', false, false),
            TableUtil::setTableHeader('businesses_count', 'Jumlah Business', false, false),
            TableUtil::setTableHeader('aksi', 'Aksi', false, false),
        ];

        $owners = TableUtil::paginate($this, $query, $headers, 10);

        return view('livewire.master.master-owner', [
            'owners'  => $owners,
            'headers' => $headers,
        ]);
    }
}
