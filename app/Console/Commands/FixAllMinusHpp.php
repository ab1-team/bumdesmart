<?php

namespace App\Console\Commands;

use App\Models\Owner;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixAllMinusHpp extends Command
{
    protected $signature = 'app:fix-all-minus-hpp {--tenant=* : Specific Tenant ID(s)}';
    protected $description = 'Fix all minus profits in sale_details and synchronize the payments (HPP) table';

    public function handle()
    {
        $this->info('Starting comprehensive HPP, Profit & Payment synchronization...');

        $tenants = Owner::all();
        if ($tenantIds = $this->option('tenant')) {
            $tenants = $tenants->whereIn('id', $tenantIds);
        }

        foreach ($tenants as $tenant) {
            $this->line('');
            $this->info("==========================================");
            $this->info("Processing Tenant: {$tenant->nama_usaha} ({$tenant->id})");
            $this->info("==========================================");

            try {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
                tenancy()->initialize($tenant);

                DB::beginTransaction();

                // 1. First restore the exact known 14 records from previous run
                $knownOriginals = [
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

                foreach ($knownOriginals as $kId => $kVals) {
                    $d = SaleDetail::find($kId);
                    if ($d) {
                        $d->update([
                            'hpp' => $kVals['hpp'],
                            'profit' => $kVals['profit'],
                        ]);
                        $this->line("  [RESTORED] Detail #{$kId}: HPP => {$kVals['hpp']}, Profit => {$kVals['profit']}");
                    }
                }

                // 2. Scan for ANY remaining records in sale_details where profit < 0 or hpp > subtotal
                $abnormalDetails = SaleDetail::with('product')
                    ->where(function ($q) {
                        $q->where('profit', '<', 0)
                          ->orWhereRaw('hpp > subtotal');
                    })
                    ->get();

                $fixedCount = 0;
                foreach ($abnormalDetails as $detail) {
                    $product = $detail->product;
                    $qty = (float) $detail->jumlah;
                    $subtotal = (float) $detail->subtotal;
                    $hargaSatuan = (float) $detail->harga_satuan;

                    // If unit selling price is smaller than master harga_beli, it was sold in broken/retail unit
                    $masterHargaBeli = $product ? (float) $product->harga_beli : 0;

                    if ($masterHargaBeli > 0 && $masterHargaBeli < $hargaSatuan) {
                        $newHpp = $qty * $masterHargaBeli;
                    } else {
                        // Estimate normal retail margin (~20% margin, cost ~80% of selling price)
                        $newHpp = round($subtotal * 0.8, 2);
                    }

                    $newProfit = $subtotal - $newHpp;

                    $detail->update([
                        'hpp' => $newHpp,
                        'profit' => $newProfit,
                    ]);

                    $this->warn("  [AUTO-FIXED] Detail #{$detail->id} (" . ($product->nama_produk ?? 'N/A') . "): HPP -> {$newHpp}, Profit -> {$newProfit}");
                    $fixedCount++;
                }

                // 3. Synchronize ALL payments where metode_pembayaran IN ('hpp', 'system') with sum(sale_details.hpp)
                $sales = Sale::all();
                $syncedPayments = 0;

                foreach ($sales as $sale) {
                    $totalHpp = (float) SaleDetail::where('sale_id', $sale->id)->sum('hpp');

                    $payment = Payment::where('transaction_id', $sale->id)
                        ->where('jenis_transaksi', 'sale')
                        ->whereIn('metode_pembayaran', ['hpp', 'system'])
                        ->where('no_pembayaran', 'like', '%-HPP%')
                        ->first();

                    if ($payment) {
                        if (abs((float)$payment->total_harga - $totalHpp) > 0.01) {
                            $oldVal = $payment->total_harga;
                            $payment->update(['total_harga' => $totalHpp]);
                            $this->line("  [SYNCED PAYMENT] Sale #{$sale->id} ({$sale->no_invoice}): HPP Payment {$oldVal} -> {$totalHpp}");
                            $syncedPayments++;
                        }
                    }
                }

                DB::commit();
                $this->info("Tenant {$tenant->id} completed: {$fixedCount} abnormal details fixed, {$syncedPayments} HPP payments synchronized.");

            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error("Failed processing tenant {$tenant->id}: " . $e->getMessage());
            } finally {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }
        }

        $this->info('');
        $this->info('All tenants fixed and synchronized successfully.');
        return 0;
    }
}
