<?php

namespace App\Console\Commands;

use App\Models\BatchMovement;
use App\Models\Owner;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateSaleHpp extends Command
{
    protected $signature = 'app:recalculate-sale-hpp 
                            {--tenant=* : Specific Tenant ID(s) to process} 
                            {--sale_id= : Specific Sale ID to recalculate} 
                            {--force : Force recalculation for all records}';

    protected $description = 'Recalculate HPP and Profit for sale_details with missing/zero HPP';

    public function handle()
    {
        $this->info('Starting HPP & Profit check on sale_details...');

        if (function_exists('tenant') && tenant()) {
            return $this->processCurrentTenant();
        }

        $tenantQuery = Owner::query();
        if ($tenantIds = $this->option('tenant')) {
            $tenantQuery->whereIn('id', $tenantIds);
        }

        $tenants = $tenantQuery->get();
        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');
            return 0;
        }

        foreach ($tenants as $tenant) {
            $this->line('');
            $this->info("Processing Tenant: {$tenant->nama_usaha} (ID: {$tenant->id})");

            try {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }

                tenancy()->initialize($tenant);
                $this->processCurrentTenant();
            } catch (\Throwable $e) {
                $this->error("Failed processing tenant {$tenant->id}: " . $e->getMessage());
            } finally {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }
        }

        return 0;
    }

    protected function processCurrentTenant()
    {
        $saleId = $this->option('sale_id');
        $force = $this->option('force');

        // By default, ONLY target records that have missing or zero HPP (or negative profit due to bugs)
        $query = SaleDetail::with(['product', 'sale']);
        if ($saleId) {
            $query->where('sale_id', $saleId);
        }

        if (! $force) {
            $query->where(function ($q) {
                $q->where('hpp', '<=', 0)
                  ->orWhereNull('hpp')
                  ->orWhere('profit', '<', 0);
            });
        }

        $details = $query->get();
        $this->line("Found {$details->count()} records needing HPP inspection.");

        if ($details->isEmpty()) {
            return 0;
        }

        $updatedCount = 0;
        $affectedSales = [];

        DB::beginTransaction();
        try {
            foreach ($details as $detail) {
                $product = $detail->product;
                if (! $product) {
                    continue;
                }

                $qty = (float) $detail->jumlah;
                $subtotal = (float) $detail->subtotal;
                $masterHargaBeli = (float) ($product->harga_beli ?? 0);

                // Try to get from batch movements
                $batchMovements = BatchMovement::where('transaction_detail_id', $detail->id)
                    ->where('jenis_transaksi', 'sale')
                    ->get();

                $calculatedHpp = 0;
                $coveredQty = 0;

                if ($batchMovements->isNotEmpty()) {
                    foreach ($batchMovements as $bm) {
                        $bmQty = (float) $bm->jumlah;
                        $bmCost = (float) $bm->harga_satuan;
                        if ($bmCost <= 0) {
                            $bmCost = $masterHargaBeli;
                        }
                        $calculatedHpp += ($bmQty * $bmCost);
                        $coveredQty += $bmQty;
                    }
                }

                if ($coveredQty < $qty) {
                    $remainingQty = $qty - $coveredQty;
                    $calculatedHpp += ($remainingQty * $masterHargaBeli);
                }

                // Sanity check: If calculated HPP is excessively higher than subtotal (e.g. unit mismatch from bulk/box)
                // and current HPP was already positive, do not overwrite unless forced
                if ($calculatedHpp > $subtotal && (float) $detail->hpp > 0 && ! $force) {
                    continue;
                }

                $calculatedProfit = $subtotal - $calculatedHpp;

                $detail->update([
                    'hpp' => $calculatedHpp,
                    'profit' => $calculatedProfit,
                ]);

                $this->line("  [FIXED] Detail #{$detail->id} ({$product->nama_produk}): HPP -> {$calculatedHpp}, Profit -> {$calculatedProfit}");
                $updatedCount++;
                $affectedSales[$detail->sale_id] = true;
            }

            foreach (array_keys($affectedSales) as $sId) {
                $totalSaleHpp = SaleDetail::where('sale_id', $sId)->sum('hpp');
                $payment = Payment::where('transaction_id', $sId)
                    ->where('jenis_transaksi', 'sale')
                    ->where('metode_pembayaran', 'hpp')
                    ->first();

                if ($payment) {
                    $payment->update(['total_harga' => $totalSaleHpp]);
                }
            }

            DB::commit();
            $this->info("  --> Fixed {$updatedCount} records.");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
