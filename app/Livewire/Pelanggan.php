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

    public function mount()
    {
        $this->businessId = auth()->user()->business_id;
    }

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

        $this->kodePelanggan = $this->generateKodePelanggan();

        $umumGroup = \App\Models\CustomerGroup::where('business_id', $this->businessId)
            ->where('nama_group', 'LIKE', '%Umum%')
            ->first();

        if ($umumGroup) {
            $this->member = (string) $umumGroup->id;
        } else {
            $firstGroup = \App\Models\CustomerGroup::where('business_id', $this->businessId)->first();
            if ($firstGroup) {
                $this->member = (string) $firstGroup->id;
            }
        }

        $this->dispatch('show-modal', modalId: 'pelangganModal', value: $this->member);
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
        $this->limitHutang = \App\Utils\NumberUtil::format($customer->limit_hutang);
        $this->id = $customer->id;

        $this->dispatch('show-modal', modalId: 'pelangganModal', value: $this->member);
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
            'limit_hutang' => \App\Utils\NumberUtil::parse($this->limitHutang),
            'username' => $this->username,
        ];

        if ($this->id) {
            $user = \App\Models\Customer::find($this->id);
            $user->update($data);
            $this->dispatch('alert', type: 'success', message: 'Data pelanggan berhasil diubah');
        } else {
            $data['password'] = Hash::make('123456');
            \App\Models\Customer::create($data);
            $this->dispatch('alert', type: 'success', message: 'Data pelanggan berhasil ditambah');
        }

        $this->resetForm();
        $this->dispatch('hide-modal', modalId: 'pelangganModal');
    }

    #[On('confirm-delete')]
    public function delete($id)
    {
        \App\Models\Customer::find($id)->delete();
        $this->dispatch('alert', type: 'success', message: 'Data pelanggan berhasil dihapus');
    }

    public function render()
    {
        $this->title = 'Pelanggan';

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
