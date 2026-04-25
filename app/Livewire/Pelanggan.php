<?php

namespace App\Livewire;

use App\Traits\WithTable;
use App\Utils\TableUtil;
use Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Pelanggan extends Component
{
    use WithTable;

    public $title;

    public $titleModal;

    public $id;

    public $businessId;

    public $member;

    public $kodePelanggan;

    public $namaPelanggan;

    public $noHp;

    public $alamat;

    public $username;

    public $password;

    public $limitHutang;

    protected function rules()
    {
        return [
            'member' => 'required',
            'kodePelanggan' => [
                'required',
                Rule::unique('customers', 'kode_pelanggan')->ignore($this->id),
            ],
            'namaPelanggan' => 'required',
            'noHp' => 'required',
            'alamat' => 'nullable',
            'limitHutang' => 'nullable',
        ];
    }

    public function resetForm()
    {
        $this->reset('member', 'kodePelanggan', 'namaPelanggan', 'noHp', 'alamat', 'username', 'password', 'limitHutang', 'id');
        $this->limitHutang = 0;
        $this->noHp = '0';
        $this->alamat = '-';
    }

    public function create()
    {
        $this->resetForm();
        $this->titleModal = 'Tambah Pelanggan';

        // Set Default Values
        $this->kodePelanggan = $this->generateKodePelanggan();
        
        $firstGroup = \App\Models\CustomerGroup::where('business_id', $this->businessId)->first();
        if ($firstGroup) {
            $this->member = $firstGroup->id;
        }

        $this->dispatch('show-modal', modalId: 'pelangganModal');
    }

    private function generateKodePelanggan()
    {
        $count = \App\Models\Customer::where('business_id', $this->businessId)->count() + 1;
        return 'CUST-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    public function edit($id)
    {
        $this->resetForm();
        $this->resetValidation();
        $this->titleModal = 'Ubah Pelanggan';

        $customer = \App\Models\Customer::find($id);

        $this->member = $customer->customer_group_id;
        $this->kodePelanggan = $customer->kode_pelanggan;
        $this->namaPelanggan = $customer->nama_pelanggan;
        $this->noHp = $customer->no_hp;
        $this->alamat = $customer->alamat;
        $this->username = $customer->username;
        $this->limitHutang = number_format($customer->limit_hutang);
        $this->id = $customer->id;

        $this->dispatch('show-modal', modalId: 'pelangganModal');
    }

    public function store()
    {
        $this->validate();

        $data = [
            'business_id' => $this->businessId,
            'customer_group_id' => $this->member,
            'kode_pelanggan' => $this->kodePelanggan,
            'nama_pelanggan' => $this->namaPelanggan,
            'no_hp' => $this->noHp,
            'alamat' => $this->alamat,
            'limit_hutang' => $this->limitHutang ? floatval(str_replace(',', '', $this->limitHutang)) : 0,
            'username' => $this->username,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->id) {
            \App\Models\Customer::find($this->id)->update($data);
            $message = 'Pelanggan berhasil diubah';
        } else {
            \App\Models\Customer::create($data);
            $message = 'Pelanggan berhasil ditambahkan';
        }

        $this->dispatch('hide-modal', modalId: 'pelangganModal');
        $this->dispatch('alert', type: 'success', message: $message);
        $this->resetForm();
    }

    #[On('delete-confirmed')]
    public function destroy($id)
    {
        \App\Models\Customer::find($id)->delete();
        $this->dispatch('alert', type: 'success', message: 'Pelanggan berhasil dihapus');
    }

    public function render()
    {
        $this->title = 'Pelanggan';
        $this->businessId = auth()->user()->business_id;

        $query = \App\Models\Customer::where('business_id', $this->businessId)->with('customerGroup');
        $customerGroups = \App\Models\CustomerGroup::where('business_id', $this->businessId)->get();

        $headers = [
            TableUtil::setTableHeader('id', '#', false, false),
            TableUtil::setTableHeader('kode_pelanggan', 'Kode Pelanggan', true, true),
            TableUtil::setTableHeader('nama_pelanggan', 'Nama Pelanggan', true, true),
            TableUtil::setTableHeader('no_hp', 'No HP', true, true),
            TableUtil::setTableHeader('customerGroup.nama_group', 'Nama Group', true, true),
            TableUtil::setTableHeader('limit_hutang', 'Limit Hutang', true, true),
            TableUtil::setTableHeader('aksi', 'Aksi', false, false),
        ];

        $customers = TableUtil::paginate($this, $query, $headers, 10);

        return view('livewire.pelanggan', [
            'customers' => $customers,
            'customerGroups' => $customerGroups,
            'headers' => $headers,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
