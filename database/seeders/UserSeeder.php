<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Hash;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Role::truncate();
        User::truncate();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $roles = [
            [
                'business_id' => 1,
                'nama_role' => 'owner',
                'deskripsi' => 'Role owner',
            ],
            [
                'business_id' => 1,
                'nama_role' => 'admin',
                'deskripsi' => 'Role admin',
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }

        $users = [
            [
                'business_id' => 1,
                'role_id' => 1,
                'is_master' => true,
                'nama_lengkap' => 'Master Admin',
                'initial' => 'Mas',
                'no_hp' => '08123456789',
                'username' => 'master',
                'password' => Hash::make('password'),
            ],
            [
                'business_id' => 1,
                'role_id' => 2,
                'nama_lengkap' => 'Admin',
                'initial' => 'Admin',
                'no_hp' => '08123456780',
                'username' => 'admin',
                'password' => Hash::make('password'),
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
