<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillKeuanganMenus extends Command
{
    protected $signature = 'menu:backfill-keuangan {--tenant=*}';
    protected $description = 'Backfill Chart of Accounts + Daftar Transaksi menu to all (or specified) tenants';

    public function handle()
    {
        $tenants = \App\Models\Owner::query();
        if ($ids = $this->option('tenant')) {
            $tenants = $tenants->whereIn('id', $ids);
        }
        $tenants = $tenants->get();

        foreach ($tenants as $tenant) {
            $id = $tenant->getTenantKey();
            $this->line("=== Tenant: {$id} ===");
            try {
                $this->call('tenants:seed', [
                    '--tenants' => [$id],
                    '--class'   => 'BackfillKeuanganMenusSeeder',
                ]);
            } catch (\Throwable $e) {
                $this->error("  FAILED: " . $e->getMessage());
            }
        }
        return 0;
    }
}
