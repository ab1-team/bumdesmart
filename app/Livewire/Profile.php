<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Profile extends Component
{
    use WithFileUploads;

    public $title;

    public $nama_lengkap;

    public $no_hp;

    public $username;

    public $password;

    public $password_confirmation;

    public $foto;

    public $fotoPath;

    public function mount()
    {
        $user = auth()->user();
        $this->nama_lengkap = $user->nama_lengkap;
        $this->no_hp = $user->no_hp;
        $this->username = $user->username;
        $this->fotoPath = $user->foto;

        $this->title = 'Profil Pengguna';
    }

    public function updateProfile()
    {
        $this->validate([
            'nama_lengkap' => 'required|string|max:255',
            'no_hp' => 'nullable|string|max:20',
            'username' => 'required|string|max:255|unique:users,username,'.auth()->id(),
            'foto' => 'nullable|image|max:2048', // Max 2MB
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $user = User::find(auth()->id());

        if ($this->foto) {
            if ($user->foto) {
                Storage::delete('public/'.$user->foto);
            }
            $user->foto = $this->foto->store('fotos', 'public');
            $this->fotoPath = $user->foto;
            $this->foto = null;
        }

        $user->nama_lengkap = $this->nama_lengkap;
        $user->no_hp = $this->no_hp;
        $user->username = $this->username;

        if (! empty($this->password)) {
            $user->password = Hash::make($this->password);
        }

        $user->save();

        session()->flash('success', 'Profil berhasil diperbarui.');
    }

    public function render()
    {
        return view('livewire.profile')->layout('layouts.app', ['title' => $this->title]);
    }
}
