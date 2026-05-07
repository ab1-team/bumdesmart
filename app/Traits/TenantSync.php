<?php

namespace App\Traits;

use App\Models\Owner;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

trait TenantSync
{
    public function syncTenantData($ownerId, $businessId, $businessData, $username = null, $password = null)
    {
        try {
            $owner = Owner::find($ownerId);
            if (!$owner) return false;

            $username = $username ?: (strtolower(str_replace(' ', '_', $businessData['nama_usaha'])) . '_owner');
            $password = $password ?: 'password';

            // 1. Force refresh connection and run migrations
            DB::purge('tenant');
            Artisan::call('tenants:migrate', [
                '--tenants' => [$owner->id],
                '--force' => true,
            ]);

            tenancy()->run($owner, function () use ($businessId, $owner, $businessData, $username, $password) {
                $conn = DB::connection('tenant');
                
                // 2. Ensure Seeding runs if tables are empty
                if (!$conn->getSchemaBuilder()->hasTable('menus') || $conn->table('menus')->count() == 0) {
                    Artisan::call('tenants:seed', [
                        '--tenants' => [$owner->id],
                        '--force' => true,
                    ]);
                }

                // 3. Sync Business
                $conn->table('businesses')->updateOrInsert(
                    ['id' => $businessId],
                    array_merge($businessData, ['updated_at' => now()])
                );

                // 4. Init Accounts
                \App\Utils\AccountUtil::initializeBusinessAccounts($businessId);

                // 5. Roles & Menus
                $roleData = [
                    ['nama_role' => 'owner', 'deskripsi' => 'Role owner'],
                    ['nama_role' => 'admin', 'deskripsi' => 'Role admin'],
                ];
                foreach ($roleData as $rd) {
                    $conn->table('roles')->updateOrInsert(
                        ['business_id' => $businessId, 'nama_role' => $rd['nama_role']],
                        ['deskripsi' => $rd['deskripsi'], 'updated_at' => now()]
                    );
                }

                $newRoleIds = $conn->table('roles')->where('business_id', $businessId)->pluck('id');
                $menuIds = $conn->table('menus')->pluck('id');
                if ($menuIds->isNotEmpty()) {
                    $roleMenus = [];
                    foreach ($newRoleIds as $roleId) {
                        foreach ($menuIds as $menuId) {
                            $roleMenus[] = ['role_id' => $roleId, 'menu_id' => $menuId];
                        }
                    }
                    $conn->table('role_menu')->whereIn('role_id', $newRoleIds)->delete();
                    $conn->table('role_menu')->insert($roleMenus);
                }

                // 6. User
                $ownerRole = $conn->table('roles')->where('business_id', $businessId)->where('nama_role', 'owner')->first();
                if ($ownerRole) {
                    $conn->table('users')->updateOrInsert(
                        ['business_id' => $businessId, 'username' => $username],
                        [
                            'role_id' => $ownerRole->id,
                            'nama_lengkap' => $businessData['nama_usaha'],
                            'initial' => substr($businessData['nama_usaha'], 0, 3),
                            'no_hp' => $businessData['no_telp'] ?? '-',
                            'password' => Hash::make($password),
                            'updated_at' => now(),
                        ]
                    );
                }
            });

            return true;
        } catch (\Exception $e) {
            \Log::error("Tenant sync failed: " . $e->getMessage());
            return false;
        }
    }
}
