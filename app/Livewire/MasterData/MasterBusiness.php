<?php

namespace App\Livewire\MasterData;

use App\Models\Business;
use App\Models\Owner;
use App\Models\Role;
use App\Models\User;
use App\Traits\WithTable;
use App\Utils\TableUtil;
use Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

class MasterBusiness extends Component
{
    use WithTable;

    public $titleModal;

    public $id;      // Business ID
    public $ownerId; // Selected Owner ID

    // Form fields
    public $businessName;
    public $address;
    public $phone;
    public $email;

    // Owners list for dropdown
    public $ownersList = [];

    protected function rules()
    {
        return [
            'ownerId'      => 'required|exists:owners,id',
            'businessName' => 'required|string|max:255',
            'address'      => 'required|string',
            'phone'        => 'required|string|max:25',
            'email'        => 'required|email|max:255',
        ];
    }

    public function mount()
    {
        $this->loadOwners();
    }

    public function loadOwners()
    {
        $this->ownersList = Owner::orderBy('nama_usaha')->pluck('nama_usaha', 'id')->toArray();
    }

    public function resetForm()
    {
        $this->reset('id', 'ownerId', 'businessName', 'address', 'phone', 'email');
    }

    public function create()
    {
        $this->resetForm();
        $this->resetValidation();
        $this->titleModal = 'Tambah Business';
        $this->dispatch('show-modal', modalId: 'masterBusinessModal');
    }

    public function edit($id)
    {
        $this->resetForm();
        $this->resetValidation();
        $this->titleModal = 'Ubah Business';

        $business = Business::with('owner')->findOrFail($id);

        $this->id           = $business->id;
        $this->ownerId      = $business->owner_id;
        $this->businessName = $business->nama_usaha;
        $this->address      = $business->alamat;
        $this->phone        = $business->no_telp;
        $this->email        = $business->email;

        $this->dispatch('show-modal', modalId: 'masterBusinessModal');
    }

    public function store()
    {
        $this->validate();

        if ($this->id) {
            // Update existing
            $business = Business::findOrFail($this->id);
            $business->update([
                'owner_id'   => $this->ownerId,
                'nama_usaha' => $this->businessName,
                'alamat'     => $this->address,
                'no_telp'    => $this->phone,
                'email'      => $this->email,
            ]);

            $message = 'Business berhasil diubah';
        } else {
            // Create new Business under selected owner
            $business = Business::create([
                'owner_id'   => $this->ownerId,
                'nama_usaha' => $this->businessName,
                'alamat'     => $this->address,
                'no_telp'    => $this->phone,
                'email'      => $this->email,
            ]);

            // Create default roles for the new business
            $roles = [
                [
                    'business_id' => $business->id,
                    'nama_role'   => 'owner',
                    'deskripsi'   => 'Role owner',
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ],
                [
                    'business_id' => $business->id,
                    'nama_role'   => 'admin',
                    'deskripsi'   => 'Role admin',
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ],
            ];
            Role::insert($roles);

            // Create default user (owner)
            $ownerRole   = Role::where('business_id', $business->id)->where('nama_role', 'owner')->first();
            $baseUsername = strtolower(str_replace(' ', '_', $this->businessName)) . '_owner';
            $username    = $baseUsername;
            $counter     = 1;

            while (User::where('username', $username)->exists()) {
                $username = $baseUsername . $counter;
                $counter++;
            }

            User::create([
                'business_id'  => $business->id,
                'role_id'      => $ownerRole->id,
                'nama_lengkap' => Owner::find($this->ownerId)->nama_usaha,
                'initial'      => substr(Owner::find($this->ownerId)->nama_usaha, 0, 3),
                'no_hp'        => $this->phone,
                'username'     => $username,
                'password'     => Hash::make('password'),
            ]);

            $message = "Business berhasil ditambahkan. Username Default: {$username} / Password: password";
        }

        $this->dispatch('hide-modal', modalId: 'masterBusinessModal');
        $this->dispatch('alert', type: 'success', message: $message);
        $this->resetForm();
    }

    #[On('delete-confirmed')]
    public function destroy($id)
    {
        $business = Business::find($id);
        if ($business) {
            User::where('business_id', $business->id)->delete();
            Role::where('business_id', $business->id)->delete();
            $business->delete();

            $this->dispatch('alert', type: 'success', message: 'Business berhasil dihapus');
        }
    }

    #[Layout('layouts.app')]
    #[Title('Master Business')]
    public function render()
    {
        $query = Business::with('owner');

        $headers = [
            TableUtil::setTableHeader('id', '#', false, false),
            TableUtil::setTableHeader('owner.nama_usaha', 'Owner', true, true),
            TableUtil::setTableHeader('nama_usaha', 'Business Name', true, true),
            TableUtil::setTableHeader('email', 'Email', true, true),
            TableUtil::setTableHeader('no_telp', 'No. Telp', true, true),
            TableUtil::setTableHeader('alamat', 'Address', true, true),
            TableUtil::setTableHeader('aksi', 'Aksi', false, false),
        ];

        $businesses = TableUtil::paginate($this, $query, $headers, 10);

        return view('livewire.master-data.master-business', [
            'businesses' => $businesses,
            'headers'    => $headers,
        ]);
    }
}
