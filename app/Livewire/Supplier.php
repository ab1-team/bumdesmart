<?php

namespace App\Livewire;

use App\Traits\WithTable;
use App\Utils\TableUtil;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Supplier extends Component
{
    use WithTable;

    public $title;

    public $titleModal;

    public $id;

    public $businessId;

    public $kodeSupplier;

    public $namaSupplier;

    public $noHp;

    public $alamat;

    public $email;

    protected function rules()
    {
        return [
            'kodeSupplier' => [
                'required',
                Rule::unique('suppliers', 'kode_supplier')->ignore($this->id),
            ],
            'namaSupplier' => 'required',
            'noHp' => 'required',
            'alamat' => 'nullable',
            'email' => [
                'required',
                Rule::unique('suppliers', 'email')->ignore($this->id),
            ],
        ];
    }

    public function resetForm()
    {
        $this->reset('kodeSupplier', 'namaSupplier', 'noHp', 'alamat', 'email', 'id');
        $this->noHp = '0';
        $this->alamat = '-';
    }

    public function create()
    {
        $this->resetForm();
        $this->titleModal = 'Tambah Supplier';

        $this->kodeSupplier = $this->generateKodeSupplier();

        $this->dispatch('show-modal', modalId: 'supplierModal');
    }

    private function generateKodeSupplier()
    {
        $count = \App\Models\Supplier::where('business_id', $this->businessId)->count() + 1;
        return 'SUPP-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    public function edit($id)
    {
        $this->resetForm();
        $this->resetValidation();
        $this->titleModal = 'Ubah Supplier';

        $supplier = \App\Models\Supplier::find($id);

        $this->kodeSupplier = $supplier->kode_supplier;
        $this->namaSupplier = $supplier->nama_supplier;
        $this->noHp = $supplier->no_hp;
        $this->alamat = $supplier->alamat;
        $this->email = $supplier->email;
        $this->id = $supplier->id;

        $this->dispatch('show-modal', modalId: 'supplierModal');
    }

    public function store()
    {
        $this->validate();

        $data = [
            'business_id' => $this->businessId,
            'kode_supplier' => $this->kodeSupplier,
            'nama_supplier' => $this->namaSupplier,
            'no_hp' => $this->noHp,
            'alamat' => $this->alamat,
            'email' => $this->email,
        ];

        if ($this->id) {
            \App\Models\Supplier::find($this->id)->update($data);
            $message = 'Supplier berhasil diubah';
        } else {
            \App\Models\Supplier::create($data);
            $message = 'Supplier berhasil ditambahkan';
        }

        $this->dispatch('hide-modal', modalId: 'supplierModal');
        $this->dispatch('alert', type: 'success', message: $message);
        $this->resetForm();
    }

    #[On('delete-confirmed')]
    public function destroy($id)
    {
        \App\Models\Supplier::find($id)->delete();
        $this->dispatch('alert', type: 'success', message: 'Supplier berhasil dihapus');
    }

    public function render()
    {
        $this->title = 'Supplier';
        $this->businessId = auth()->user()->business_id;

        $query = \App\Models\Supplier::where('business_id', $this->businessId);

        $headers = [
            TableUtil::setTableHeader('id', '#', false, false),
            TableUtil::setTableHeader('kode_supplier', 'Kode Supplier', true, true),
            TableUtil::setTableHeader('nama_supplier', 'Nama Supplier', true, true),
            TableUtil::setTableHeader('alamat', 'Alamat', true, true),
            TableUtil::setTableHeader('no_hp', 'No HP', true, true),
            TableUtil::setTableHeader('email', 'Email', true, true),
            TableUtil::setTableHeader('aksi', 'Aksi', false, false),
        ];

        $suppliers = TableUtil::paginate($this, $query, $headers, 10);

        return view('livewire.supplier', [
            'suppliers' => $suppliers,
            'headers' => $headers,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
