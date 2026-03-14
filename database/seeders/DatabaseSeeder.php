<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Business;
use App\Models\Owner;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Owner::truncate();
        Business::truncate();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        Owner::create([
            'nama_usaha' => 'Bumdesmart',
            'tanggal_penggunaan' => now(),
            'logo' => 'logo.png',
        ]);

        Business::create([
            'owner_id' => 1,
            'nama_usaha' => 'Bumdesmart',
            'alamat' => 'Jl. Bumdesmart',
            'no_telp' => '08123456789',
            'email' => 'bumdesmart@gmail.com',
        ]);

        $this->call([
            AccountSeeder::class,
            UserSeeder::class,
        ]);
    }
}
