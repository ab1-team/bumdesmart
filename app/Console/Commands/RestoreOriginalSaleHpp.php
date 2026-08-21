<?php

namespace App\Console\Commands;

use App\Models\Owner;
use App\Models\Payment;
use App\Models\SaleDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RestoreOriginalSaleHpp extends Command
{
    protected $signature = 'app:restore-sale-hpp';
    protected $description = 'Restore original HPP and Profit for the affected sale details';

    public function handle()
    {
        $this->info('Restoring original HPP & Profit for affected sale_details...');

        $originalData = [
            5    => ['hpp' => 22464,     'profit' => 25536],
            54   => ['hpp' => 36000,     'profit' => 18000],
            90   => ['hpp' => 851999.94, 'profit' => 138000],
            260  => ['hpp' => 670000,    'profit' => 18300],
            264  => ['hpp' => 16848,     'profit' => 1596],
            267  => ['hpp' => 4180,      'profit' => 925],
            418  => ['hpp' => 4600000,   'profit' => 1000000],
            506  => ['hpp' => 468,       'profit' => 266],
            573  => ['hpp' => 1736000,   'profit' => 104000],
            581  => ['hpp' => 2925,      'profit' => 3325],
            614  => ['hpp' => 605999.94, 'profit' => 136000],
            768  => ['hpp' => 138762,    'profit' => 157738],
            843  => ['hpp' => 40950,     'profit' => 46550],
            1266 => ['hpp' => 234,       'profit' => 266],
        ];

        $tenants = Owner::all();

        foreach ($tenants as $tenant) {
            $this->line("Processing Tenant: {$tenant->nama_usaha} ({$tenant->id})");

            try {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
                tenancy()->initialize($tenant);

                DB::beginTransaction();

                $restoredCount = 0;
                $affectedSales = [];

                foreach ($originalData as $id => $vals) {
                    $detail = SaleDetail::find($id);
                    if ($detail) {
                        $detail->update([
                            'hpp' => $vals['hpp'],
                            'profit' => $vals['profit'],
                        ]);
                        $this->info("  [RESTORED] Detail #{$id}: HPP => {$vals['hpp']}, Profit => {$vals['profit']}");
                        $restoredCount++;
                        $affectedSales[$detail->sale_id] = true;
                    }
                }

                // Recalculate HPP Payment entries based on restored details
                foreach (array_keys($affectedSales) as $sId) {
                    $totalSaleHpp = SaleDetail::where('sale_id', $sId)->sum('hpp');
                    $payment = Payment::where('transaction_id', $sId)
                        ->where('jenis_transaksi', 'sale')
                        ->where('metode_pembayaran', 'hpp')
                        ->first();

                    if ($payment) {
                        $payment->update(['total_harga' => $totalSaleHpp]);
                        $this->info("  [RESTORED] HPP Payment for Sale #{$sId} updated to {$totalSaleHpp}");
                    }
                }

                DB::commit();
                $this->info("Successfully restored {$restoredCount} records for tenant {$tenant->id}.");
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error("Failed restoring tenant {$tenant->id}: " . $e->getMessage());
            } finally {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }
        }

        return 0;
    }
}
