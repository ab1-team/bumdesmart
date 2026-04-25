<?php

namespace App\Livewire;

use App\Traits\WithTable;
use App\Utils\TableUtil;
use Livewire\Attributes\On;
use Livewire\Component;

class Member extends Component
{
    use WithTable;

    public $title;

    public $titleModal;

    public $id;

    public $businessId;

    public $namaGroup;

    public $deskripsi;

    public $diskon = 0;

    protected function rules()
    {
        return [
            'namaGroup' => 'required',
            'deskripsi' => 'nullable',
            'diskon' => 'nullable|numeric',
        ];
    }

    public function resetForm()
    {
        $this->reset('namaGroup', 'deskripsi', 'diskon', 'id');
        $this->diskon = 0;
        $this->deskripsi = '-';
    }

    public function create()
    {
        $this->resetForm();
        $this->titleModal = 'Tambah Member';

        $this->dispatch('show-modal', modalId: 'memberModal');
    }

    public function edit($id)
    {
        $this->resetForm();
        $this->titleModal = 'Ubah Member';

        $customerGroup = \App\Models\CustomerGroup::find($id);

        $this->namaGroup = $customerGroup->nama_group;
        $this->deskripsi = $customerGroup->deskripsi;
        $this->diskon = $customerGroup->diskon_persen;
        $this->id = $customerGroup->id;

        $this->dispatch('show-modal', modalId: 'memberModal');
    }

    public function store()
    {
        $this->validate();

        $data = [
            'business_id' => $this->businessId,
            'nama_group' => $this->namaGroup,
            'deskripsi' => $this->deskripsi,
            'diskon_persen' => $this->diskon,
        ];

        if ($this->id) {
            \App\Models\CustomerGroup::find($this->id)->update($data);
            $message = 'Member berhasil diubah';
        } else {
            \App\Models\CustomerGroup::create($data);
            $message = 'Member berhasil ditambahkan';
        }

        $this->dispatch('hide-modal', modalId: 'memberModal');
        $this->dispatch('alert', type: 'success', message: $message);
        $this->resetForm();
    }

    #[On('delete-confirmed')]
    public function destroy($id)
    {
        if (\App\Models\Customer::where('customer_group_id', $id)->exists()) {
            $this->dispatch('alert', type: 'error', message: 'Member tidak dapat dihapus karena ada pelanggan yang terkait');

            return;
        }

        \App\Models\CustomerGroup::find($id)->delete();
        $this->dispatch('alert', type: 'success', message: 'Member berhasil dihapus');
    }

    public function render()
    {
        $this->title = 'Member';
        $this->businessId = auth()->user()->business_id;

        $query = \App\Models\CustomerGroup::where('business_id', $this->businessId);

        $headers = [
            TableUtil::setTableHeader('id', '#', false, false),
            TableUtil::setTableHeader('nama_group', 'Nama Member', true, true),
            TableUtil::setTableHeader('deskripsi', 'Deskripsi', true, true),
            TableUtil::setTableHeader('diskon_persen', 'Diskon', true, true),
            TableUtil::setTableHeader('aksi', 'Aksi', false, false),
        ];

        $customerGroups = TableUtil::paginate($this, $query, $headers, 10);

        return view('livewire.member', [
            'customerGroups' => $customerGroups,
            'headers' => $headers,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
