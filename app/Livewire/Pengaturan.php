<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;

class Pengaturan extends Component
{
    use WithFileUploads;

    public $title;

    public $business_id;

    public $nama_toko;

    public $alamat_toko;

    public $no_telp_toko;

    public $email_toko;

    public $owner_id;

    public $nama_perusahaan;

    public $logo;

    public $new_logo;

    public function mount()
    {
        $user = auth()->user();
        $business = \App\Models\Business::find($user->business_id);

        if ($business) {
            $this->business_id = $business->id;
            $this->nama_toko = $business->nama_usaha;
            $this->alamat_toko = $business->alamat;
            $this->no_telp_toko = $business->no_telp;
            $this->email_toko = $business->email;

            $owner = \App\Models\Owner::find($business->owner_id);
            if ($owner) {
                $this->owner_id = $owner->id;
                $this->nama_perusahaan = $owner->nama_usaha;
                $this->logo = $owner->logo;
            }
        }
    }

    public function updateSettings()
    {
        $this->validate([
            'nama_toko' => 'required',
            'alamat_toko' => 'required',
            'no_telp_toko' => 'nullable',
            'email_toko' => 'nullable|email',
            'nama_perusahaan' => 'required',
            'new_logo' => 'nullable|image|max:2048', // max 2MB
        ]);

        if ($this->business_id) {
            $business = \App\Models\Business::find($this->business_id);
            if ($business) {
                $business->update([
                    'nama_usaha' => $this->nama_toko,
                    'alamat' => $this->alamat_toko,
                    'no_telp' => $this->no_telp_toko,
                    'email' => $this->email_toko,
                ]);

                if ($this->owner_id) {
                    $owner = \App\Models\Owner::find($this->owner_id);
                    if ($owner) {
                        $updateData = [
                            'nama_usaha' => $this->nama_perusahaan,
                            // Domain is intentionally omitted to be readonly
                        ];

                        if ($this->new_logo) {
                            $logoPath = $this->new_logo->store('owner_logos', 'public');
                            $updateData['logo'] = $logoPath;
                            $this->logo = $logoPath; // Update temporary view
                        }

                        $owner->update($updateData);
                    }
                }

                $this->dispatch('alert', type: 'success', message: 'Pengaturan berhasil disimpan!');
            }
        }
    }

    public function render()
    {
        $this->title = 'Pengaturan';

        return view('livewire.pengaturan')->layout('layouts.app', ['title' => $this->title]);
    }
}
