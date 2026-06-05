<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BackfillKeuanganMenusSeeder extends Seeder
{
    public function run(): void
    {
        $parent = DB::table('menus')->where('title', 'Keuangan')->first();
        if (!$parent) {
            $this->command->warn('Parent "Keuangan" not found, skipped.');
            return;
        }

        $menus = [
            ['title' => 'Chart of Accounts', 'url' => '/keuangan/coa', 'order' => 1],
            ['title' => 'Daftar Transaksi',  'url' => '/keuangan/daftar-transaksi', 'order' => 99],
        ];

        foreach ($menus as $m) {
            $exists = DB::table('menus')->where('url', $m['url'])->first();
            if (!$exists) {
                $id = DB::table('menus')->insertGetId([
                    'parent_id' => $parent->id,
                    'title'     => $m['title'],
                    'url'       => $m['url'],
                    'icon'      => null,
                    'order'     => $m['order'],
                    'is_active' => 1,
                    'created_at'=> now(),
                    'updated_at'=> now(),
                ]);
                $this->command->info("Inserted: {$m['title']} (id=$id)");
            } else {
                $id = $exists->id;
                $this->command->line("Exists:  {$m['title']} (id=$id)");
            }

            $roleIds = DB::table('role_menu')->where('menu_id', $parent->id)->pluck('role_id');
            foreach ($roleIds as $rid) {
                DB::table('role_menu')->insertOrIgnore(['role_id' => $rid, 'menu_id' => $id]);
            }
        }
    }
}
