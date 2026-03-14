<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssignMenusToRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = \App\Models\Role::all();
        $menus = \App\Models\Menu::all();

        foreach ($roles as $role) {
            $role->menus()->sync($menus->pluck('id'));
        }
    }
}
