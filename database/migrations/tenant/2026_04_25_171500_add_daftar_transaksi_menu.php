<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $parent = DB::table('menus')->where('title', 'Keuangan')->first();
        if ($parent) {
            $exists = DB::table('menus')->where('url', '/keuangan/daftar-transaksi')->first();
            
            if (!$exists) {
                $menuId = DB::table('menus')->insertGetId([
                    'parent_id' => $parent->id,
                    'title' => 'Daftar Transaksi',
                    'icon' => null,
                    'order' => 99,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $menuId = $exists->id;
                DB::table('menus')->where('id', $menuId)->update([
                    'parent_id' => $parent->id,
                    'title' => 'Daftar Transaksi',
                    'is_active' => 1,
                    'updated_at' => now(),
                ]);
            }

            // Get roles that have access to parent menu (Keuangan)
            $roleIds = DB::table('role_menu')->where('menu_id', $parent->id)->pluck('role_id');
            
            foreach ($roleIds as $roleId) {
                $roleMenuExists = DB::table('role_menu')
                    ->where('role_id', $roleId)
                    ->where('menu_id', $menuId)
                    ->exists();

                if (!$roleMenuExists) {
                    DB::table('role_menu')->insert([
                        'role_id' => $roleId,
                        'menu_id' => $menuId,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $menu = DB::table('menus')->where('url', '/keuangan/daftar-transaksi')->first();
        if ($menu) {
            DB::table('role_menu')->where('menu_id', $menu->id)->delete();
            DB::table('menus')->where('id', $menu->id)->delete();
        }
    }
};
