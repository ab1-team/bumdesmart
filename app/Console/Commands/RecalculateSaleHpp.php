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
                            {--force : Force recalculation even if hpp > 0}';

    protected $description = 'Recalculate HPP and Profit in sale_details for tenant(s) based on actual batch costs and product master harga_beli';

    public function handle()
    {
        $this->info('Starting HPP & Profit audit and recalculation on sale_details...');

        // If tenancy is already initialized (e.g. called via tenants:run)
        if (function_exists('tenant') && tenant()) {
            return $this->processCurrentTenant();
        }

        // Otherwise, iterate through tenants
        $tenantQuery = Owner::query();
        if ($tenantIds = $this->option('tenant')) {
            $tenantQuery->whereIn('id', $tenantIds);
        }

        $tenants = $tenantQuery->get();
        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');
            return 0;
        }

        $this->info("Found {$tenants->count()} tenant(s) to process.");

        foreach ($tenants as $tenant) {
            $this->line('');
            $this->info("==========================================");
            $this->info("Processing Tenant: {$tenant->nama_usaha} (ID: {$tenant->id})");
            $this->info("==========================================");

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

        $this->info('');
        $this->info('All tenants processed successfully.');
        return 0;
    }

    protected function processCurrentTenant()
    {
        $saleId = $this->option('sale_id');
        $force = $this->option('force');

        $query = SaleDetail::with(['product', 'sale']);
        if ($saleId) {
            $query->where('sale_id', $saleId);
        }

        $details = $query->get();
        $this->line("Found {$details->count()} sale detail records in tenant database.");

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
                    $this->warn("SaleDetail #{$detail->id} has no linked product (#{$detail->product_id}). Skipping.");
                    continue;
                }

                $qty = (float) $detail->jumlah;
                $subtotal = (float) $detail->subtotal;
                $masterHargaBeli = (float) ($product->harga_beli ?? 0);

                // Calculate HPP:
                // 1. Try to sum from linked batch movements
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

                // If not all qty covered by batch movements, fallback remaining to master harga_beli
                if ($coveredQty < $qty) {
                    $remainingQty = $qty - $coveredQty;
                    $calculatedHpp += ($remainingQty * $masterHargaBeli);
                }

                $calculatedProfit = $subtotal - $calculatedHpp;

                // Check if update is needed
                $currentHpp = (float) $detail->hpp;
                $currentProfit = (float) $detail->profit;

                if ($force || abs($currentHpp - $calculatedHpp) > 0.01 || abs($currentProfit - $calculatedProfit) > 0.01) {
                    $detail->update([
                        'hpp' => $calculatedHpp,
                        'profit' => $calculatedProfit,
                    ]);

                    $this->line("  [UPDATED] Detail #{$detail->id} ({$product->nama_produk}): HPP {$currentHpp} -> {$calculatedHpp}, Profit {$currentProfit} -> {$calculatedProfit}");
                    $updatedCount++;
                    $affectedSales[$detail->sale_id] = true;
                }
            }

            // Sync HPP payment entries for affected sales
            foreach (array_keys($affectedSales) as $sId) {
                $totalSaleHpp = SaleDetail::where('sale_id', $sId)->sum('hpp');
                $payment = Payment::where('transaction_id', $sId)
                    ->where('jenis_transaksi', 'sale')
                    ->where('metode_pembayaran', 'hpp')
                    ->first();

                if ($payment) {
                    $payment->update(['total_harga' => $totalSaleHpp]);
                    $this->line("  [SYNCED] HPP Payment entry for Sale #{$sId} updated to {$totalSaleHpp}");
                }
            }

            DB::commit();
            $this->info("  --> Recalculated {$updatedCount} sale detail records.");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Error recalculating tenant HPP: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
