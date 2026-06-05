<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $parent = DB::table('menus')->where('title', 'Keuangan')->first();
        if (!$parent) {
            return;
        }

        $exists = DB::table('menus')->where('url', '/keuangan/coa')->first();
        if (!$exists) {
            $menuId = DB::table('menus')->insertGetId([
                'parent_id' => $parent->id,
                'title' => 'Chart of Accounts',
                'icon' => null,
                'order' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $menuId = $exists->id;
            DB::table('menus')->where('id', $menuId)->update([
                'parent_id' => $parent->id,
                'title' => 'Chart of Accounts',
                'updated_at' => now(),
            ]);
        }

        $roleIds = DB::table('role_menu')->where('menu_id', $parent->id)->pluck('role_id');
        foreach ($roleIds as $roleId) {
            DB::table('role_menu')->insertOrIgnore([
                'role_id' => $roleId,
                'menu_id' => $menuId,
            ]);
        }
    }

    public function down(): void
    {
        $menu = DB::table('menus')->where('url', '/keuangan/coa')->first();
        if ($menu) {
            DB::table('role_menu')->where('menu_id', $menu->id)->delete();
            DB::table('menus')->where('id', $menu->id)->delete();
        }
    }
};
