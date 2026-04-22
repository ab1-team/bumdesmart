<?php

namespace App\Livewire;

use App\Traits\WithTable;
use App\Utils\TableUtil;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Role extends Component
{
    use WithTable;

    public $title;

    public $titleModal;

    public $businessId;

    public $id;

    public $namaRole;

    public $deskripsi;

    public $selectedMenus = [];

    protected function rules()
    {
        return [
            'namaRole' => [
                'required',
                Rule::unique('roles', 'nama_role')->ignore($this->id),
            ],
            'deskripsi' => 'nullable',
            'selectedMenus' => 'nullable|array',
        ];
    }

    public function resetForm()
    {
        $this->reset('id', 'namaRole', 'deskripsi', 'selectedMenus');
    }

    public function create()
    {
        $this->resetForm();
        $this->titleModal = 'Tambah Role';

        $this->dispatch('show-modal', modalId: 'roleModal');
    }

    public function edit($id)
    {
        $this->resetForm();
        $this->titleModal = 'Ubah Role';

        $role = \App\Models\Role::with('menus')->find($id);

        $this->namaRole = $role->nama_role;
        $this->deskripsi = $role->deskripsi;
        $this->id = $role->id;
        $this->selectedMenus = $role->menus->pluck('id')->map(fn ($id) => (string) $id)->toArray();

        $this->dispatch('show-modal', modalId: 'roleModal');
    }

    public function store()
    {
        $this->validate();

        $data = [
            'business_id' => $this->businessId,
            'nama_role' => $this->namaRole,
            'deskripsi' => $this->deskripsi,
        ];

        if ($this->id) {
            $role = \App\Models\Role::find($this->id);
            $role->update($data);
            $role->menus()->sync($this->selectedMenus);
            $message = 'Role berhasil diubah';
        } else {
            $role = \App\Models\Role::create($data);
            $role->menus()->sync($this->selectedMenus);
            $message = 'Role berhasil ditambahkan';
        }

        $this->dispatch('alert', type: 'success', message: $message);
        $this->dispatch('hide-modal', modalId: 'roleModal');
        $this->resetForm();
    }

    #[On('delete-confirmed')]
    public function destroy($id)
    {
        if (\App\Models\User::where('role_id', $id)->exists()) {
            $this->dispatch('alert', type: 'error', message: 'Role tidak dapat dihapus karena ada user yang terkait');

            return;
        }

        \App\Models\Role::find($id)->delete();
        $this->dispatch('alert', type: 'success', message: 'Role berhasil dihapus');
    }

    public function toggleParent($parentId)
    {
        $menu = \App\Models\Menu::with('children')->find($parentId);
        $childrenIds = $menu->children->pluck('id')->map(fn ($id) => (string) $id)->toArray();

        // Di Laravel Livewire, saat wire:click dijalankan bersamaan dengan wire:model,
        // model biasanya sudah terupdate. Jadi kita cek apakah parent ada di selectedMenus.
        if (in_array((string) $parentId, $this->selectedMenus)) {
            // Jika parent dicentang, centang semua anak
            $this->selectedMenus = array_unique(array_merge($this->selectedMenus, $childrenIds));
        } else {
            // Jika parent tidak dicentang, hapus centang semua anak
            $this->selectedMenus = array_values(array_diff($this->selectedMenus, $childrenIds));
        }
    }

    public function toggleChild($parentId)
    {
        $parent = \App\Models\Menu::with('children')->find($parentId);
        $childrenIds = $parent->children->pluck('id')->map(fn ($id) => (string) $id)->toArray();

        $selectedChildren = array_intersect($this->selectedMenus, $childrenIds);

        if (count($selectedChildren) > 0) {
            // Jika ada minimal satu anak dicentang, maka parent otomatis ikut tercentang
            if (!in_array((string) $parentId, $this->selectedMenus)) {
                $this->selectedMenus[] = (string) $parentId;
            }
        } else {
            // Jika semua anak tidak dicentang, maka parent ikut tidak tercentang
            $this->selectedMenus = array_values(array_diff($this->selectedMenus, [(string) $parentId]));
        }
    }

    public function render()
    {
        $this->title = 'Role';
        $this->businessId = auth()->user()->business_id;

        $query = \App\Models\Role::where('business_id', $this->businessId);

        $headers = [
            TableUtil::setTableHeader('id', '#', false, false),
            TableUtil::setTableHeader('nama_role', 'Nama Role', true, true),
            TableUtil::setTableHeader('deskripsi', 'Deskripsi', true, true),
            TableUtil::setTableHeader('aksi', 'Aksi', false, false),
        ];

        $roles = TableUtil::paginate($this, $query, $headers, 10);
        $availableMenus = \App\Models\Menu::with('children')->whereNull('parent_id')->orderBy('order')->get();

        return view('livewire.role', [
            'roles' => $roles,
            'headers' => $headers,
            'availableMenus' => $availableMenus,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
