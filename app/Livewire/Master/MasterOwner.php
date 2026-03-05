<?php

namespace App\Livewire\Master;

use App\Models\Owner;
use App\Traits\WithTable;
use App\Utils\TableUtil;
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
            'domain'            => 'nullable|string|max:255',
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

        $owner = Owner::findOrFail($id);
        $this->id                 = $owner->id;
        $this->namaUsaha          = $owner->nama_usaha;
        $this->tanggalPenggunaan  = $owner->tanggal_penggunaan;
        $this->domain             = $owner->domain;
        $this->domainAlternatif   = $owner->domain_alternatif;

        $this->dispatch('show-modal', modalId: 'masterOwnerModal');
    }

    public function store()
    {
        $this->validate();

        $data = [
            'nama_usaha'        => $this->namaUsaha,
            'tanggal_penggunaan'=> $this->tanggalPenggunaan,
            'domain'            => $this->domain ?: null,
            'domain_alternatif' => $this->domainAlternatif ?: null,
            'logo'              => 'logo.png',
        ];

        if ($this->id) {
            Owner::findOrFail($this->id)->update($data);
            $message = 'Owner berhasil diubah';
        } else {
            Owner::create($data);
            $message = 'Owner berhasil ditambahkan';
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
            $owner->delete();
            $this->dispatch('alert', type: 'success', message: 'Owner berhasil dihapus');
        }
    }

    #[Layout('layouts.app')]
    #[Title('Master Owner')]
    public function render()
    {
        $query = Owner::withCount('businesses');

        $headers = [
            TableUtil::setTableHeader('id', '#', false, false),
            TableUtil::setTableHeader('nama_usaha', 'Nama Owner', true, true),
            TableUtil::setTableHeader('tanggal_penggunaan', 'Tgl. Penggunaan', true, true),
            TableUtil::setTableHeader('domain', 'Domain', true, true),
            TableUtil::setTableHeader('domain_alternatif', 'Domain Alternatif', true, true),
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
