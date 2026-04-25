<?php

namespace App\Livewire;

use App\Traits\WithTable;
use App\Utils\TableUtil;
use Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class User extends Component
{
    use WithTable;

    public $title;

    public $titleModal;

    public $id;

    public $businessId;

    public $role;

    public $namaLengkap;

    public $inisial;

    public $noHp;

    public $username;

    public $password;

    protected function rules()
    {
        return [
            'role' => 'required',
            'namaLengkap' => 'required',
            'inisial' => 'required',
            'noHp' => 'required',
            'username' => [
                'required',
                Rule::unique('users', 'username')->ignore($this->id),
            ],
            'password' => [
                Rule::requiredIf($this->id == null),
            ],
        ];
    }

    public function resetForm()
    {
        $this->reset('role', 'namaLengkap', 'inisial', 'noHp', 'username', 'password', 'id');
        $this->noHp = '0';
    }

    public function create()
    {
        $this->resetForm();
        $this->titleModal = 'Tambah User';

        $firstRole = \App\Models\Role::where('business_id', $this->businessId)->first();
        if ($firstRole) {
            $this->role = $firstRole->id;
        }

        $this->dispatch('show-modal', modalId: 'userModal');
    }

    public function updatedNamaLengkap($value)
    {
        if (empty($this->inisial)) {
            $words = explode(' ', $value);
            $initial = '';
            foreach ($words as $word) {
                $initial .= strtoupper(substr($word, 0, 1));
            }
            $this->inisial = substr($initial, 0, 3);
        }
    }

    public function edit($id)
    {
        $this->resetForm();
        $this->resetValidation();
        $this->titleModal = 'Ubah User';

        $user = \App\Models\User::find($id);

        $this->role = $user->role_id;
        $this->namaLengkap = $user->nama_lengkap;
        $this->inisial = $user->initial;
        $this->noHp = $user->no_hp;
        $this->username = $user->username;
        $this->id = $user->id;

        $this->dispatch('show-modal', modalId: 'userModal');
    }

    public function store()
    {
        $this->validate();

        $data = [
            'business_id' => $this->businessId,
            'role_id' => $this->role,
            'nama_lengkap' => $this->namaLengkap,
            'initial' => $this->inisial,
            'no_hp' => $this->noHp,
            'username' => $this->username,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->id) {
            \App\Models\User::find($this->id)->update($data);
            $message = 'User berhasil diubah';
        } else {
            \App\Models\User::create($data);
            $message = 'User berhasil ditambahkan';
        }

        $this->dispatch('hide-modal', modalId: 'userModal');
        $this->dispatch('alert', type: 'success', message: $message);
        $this->resetForm();
    }

    #[On('delete-confirmed')]
    public function destroy($id)
    {
        \App\Models\User::find($id)->delete();
        $this->dispatch('alert', type: 'success', message: 'User berhasil dihapus');
    }

    public function render()
    {
        $this->title = 'User';
        $this->businessId = auth()->user()->business_id;

        $query = \App\Models\User::where('business_id', $this->businessId)->with('role');
        $roles = \App\Models\Role::where('business_id', $this->businessId)->get();

        $headers = [
            TableUtil::setTableHeader('id', '#', false, false),
            TableUtil::setTableHeader('nama_lengkap', 'Nama Lengkap', true, true),
            TableUtil::setTableHeader('no_hp', 'No HP', true, true),
            TableUtil::setTableHeader('role.nama_role', 'Nama Role', true, true),
            TableUtil::setTableHeader('aksi', 'Aksi', false, false),
        ];

        $users = TableUtil::paginate($this, $query, $headers, 10);

        return view('livewire.user', [
            'users' => $users,
            'roles' => $roles,
            'headers' => $headers,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
