<?php

namespace App\Livewire;

use App\Traits\WithTable;
use App\Utils\TableUtil;
use Livewire\Attributes\On;
use Livewire\Component;

class Satuan extends Component
{
    use WithTable;

    public $title;

    public $titleModal;

    public $id;

    public $businessId;

    public $namaSatuan;

    public $inisialSatuan;

    public $deskripsi;

    public $ijinkanDesimal;

    protected $casts = [
        'ijinkanDesimal' => 'boolean',
    ];

    protected function rules()
    {
        return [
            'namaSatuan' => 'required',
            'inisialSatuan' => 'required',
            'deskripsi' => 'nullable',
            'ijinkanDesimal' => 'nullable',
        ];
    }

    public function resetForm()
    {
        $this->reset('namaSatuan', 'inisialSatuan', 'deskripsi', 'ijinkanDesimal', 'id');
        $this->ijinkanDesimal = false;
    }

    public function create()
    {
        $this->resetForm();
        $this->titleModal = 'Tambah Satuan';

        $this->dispatch('show-modal', modalId: 'satuanModal');
    }

    public function edit($id)
    {
        $this->resetForm();
        $this->resetValidation();
        $this->titleModal = 'Ubah Satuan';

        $unit = \App\Models\Unit::find($id);

        $this->namaSatuan = $unit->nama_satuan;
        $this->inisialSatuan = $unit->inisial_satuan;
        $this->deskripsi = $unit->deskripsi;
        $this->ijinkanDesimal = (bool) $unit->desimal;
        $this->id = $unit->id;

        $this->dispatch('show-modal', modalId: 'satuanModal');
    }

    public function store()
    {
        $this->validate();

        $data = [
            'business_id' => $this->businessId,
            'nama_satuan' => $this->namaSatuan,
            'inisial_satuan' => $this->inisialSatuan,
            'deskripsi' => $this->deskripsi,
            'desimal' => $this->ijinkanDesimal,
        ];

        if ($this->id) {
            \App\Models\Unit::find($this->id)->update($data);
            $message = 'Satuan berhasil diubah';
        } else {
            \App\Models\Unit::create($data);
            $message = 'Satuan berhasil ditambahkan';
        }

        $this->dispatch('hide-modal', modalId: 'satuanModal');
        $this->dispatch('alert', type: 'success', message: $message);
        $this->resetForm();
    }

    #[On('delete-confirmed')]
    public function destroy($id)
    {
        if (\App\Models\Product::where('unit_id', $id)->exists()) {
            $this->dispatch('alert', type: 'error', message: 'Satuan tidak dapat dihapus karena ada produk yang terkait');

            return;
        }

        \App\Models\Unit::find($id)->delete();
        $this->dispatch('alert', type: 'success', message: 'Satuan berhasil dihapus');
    }

    public function render()
    {
        $this->title = 'Satuan';
        $this->businessId = auth()->user()->business_id;

        $query = \App\Models\Unit::where('business_id', $this->businessId);

        $headers = [
            TableUtil::setTableHeader('id', '#', false, false),
            TableUtil::setTableHeader('nama_satuan', 'Nama Satuan', true, true),
            TableUtil::setTableHeader('inisial_satuan', 'Inisial', true, true),
            TableUtil::setTableHeader('deskripsi', 'Deskripsi', true, true),
            TableUtil::setTableHeader('desimal', 'Ijinkan Desimal', true, true),
            TableUtil::setTableHeader('aksi', 'Aksi', false, false),
        ];

        $units = TableUtil::paginate($this, $query, $headers, 10);

        return view('livewire.satuan', [
            'units' => $units,
            'headers' => $headers,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
