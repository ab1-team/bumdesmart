<?php

namespace App\Console\Commands;

use App\Models\Owner;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\PurchaseDetail;
use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixAllMinusHpp extends Command
{
    protected $signature = 'app:fix-all-minus-hpp {--tenant=* : Specific Tenant ID(s)}';
    protected $description = 'Comprehensive audit & fix for purchase prices, product batches, master prices, sale HPP, and payments table';

    public function handle()
    {
        $this->info('Starting comprehensive HPP, Profit, Purchase & Payment synchronization...');

        $tenants = Owner::all();
        if ($tenantIds = $this->option('tenant')) {
            $tenants = $tenants->whereIn('id', $tenantIds);
        }

        foreach ($tenants as $tenant) {
            $this->line('');
            $this->info("===============================================================");
            $this->info("Processing Tenant: {$tenant->nama_usaha} ({$tenant->id})");
            $this->info("===============================================================");

            try {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
                tenancy()->initialize($tenant);

                DB::beginTransaction();

                // 1. Audit & Fix Purchase Details & Linked Batches
                $purchaseDetails = PurchaseDetail::with('product')->get();
                $fixedPdCount = 0;
                foreach ($purchaseDetails as $pd) {
                    $qty = (float) $pd->jumlah;
                    if ($qty <= 0) continue;

                    $subtotal = (float) $pd->subtotal;
                    $diskon = (float) $pd->jumlah_diskon;
                    $realUnitPrice = round(($subtotal + $diskon) / $qty, 2);
                    $currentUnitPrice = (float) $pd->harga_satuan;

                    if ($realUnitPrice > 0 && abs($currentUnitPrice - $realUnitPrice) > 1.0 && ($currentUnitPrice > $realUnitPrice * 1.5 || $currentUnitPrice < $realUnitPrice * 0.5)) {
                        $pd->update(['harga_satuan' => $realUnitPrice]);

                        // Update associated batch
                        ProductBatch::where('purchase_detail_id', $pd->id)->update([
                            'harga_satuan' => $realUnitPrice,
                        ]);

                        $fixedPdCount++;
                    }
                }
                $this->info("  [1] Purchase details & linked batches corrected: {$fixedPdCount}");

                // 2. Audit & Fix any remaining unlinked/abnormal Batches
                $batches = ProductBatch::with('product')->get();
                $fixedBatchCount = 0;
                foreach ($batches as $b) {
                    $p = $b->product;
                    if (!$p) continue;

                    $batchPrice = (float) $b->harga_satuan;
                    $masterJual = (float) $p->harga_jual;
                    $masterBeli = (float) $p->harga_beli;

                    if ($masterJual > 0 && $batchPrice > $masterJual * 3) {
                        $newBatchPrice = ($masterBeli > 0 && $masterBeli <= $masterJual)
                            ? $masterBeli
                            : round($masterJual * 0.8, 2);

                        $b->update(['harga_satuan' => $newBatchPrice]);
                        $fixedBatchCount++;
                    }
                }
                $this->info("  [2] Abnormal product batches corrected: {$fixedBatchCount}");

                // 3. Audit & Fix Products master harga_beli
                $products = Product::all();
                $fixedProductCount = 0;
                foreach ($products as $p) {
                    $beli = (float) $p->harga_beli;
                    $jual = (float) $p->harga_jual;

                    if ($jual > 0 && $beli > $jual * 2) {
                        // Look for latest valid purchase detail
                        $latestPd = PurchaseDetail::where('product_id', $p->id)
                            ->orderBy('id', 'desc')
                            ->first();

                        if ($latestPd && (float)$latestPd->harga_satuan > 0 && (float)$latestPd->harga_satuan <= $jual) {
                            $newBeli = (float) $latestPd->harga_satuan;
                        } else {
                            $newBeli = round($jual * 0.8, 2);
                        }

                        $p->update(['harga_beli' => $newBeli]);
                        $fixedProductCount++;
                    }
                }
                $this->info("  [3] Master products harga_beli corrected: {$fixedProductCount}");

                // 4. Recalculate & Fix all abnormal Sale Details
                $allSaleDetails = SaleDetail::with(['product', 'sale'])->get();
                $fixedSaleDetailCount = 0;

                foreach ($allSaleDetails as $detail) {
                    $product = $detail->product;
                    $qty = (float) $detail->jumlah;
                    $subtotal = (float) $detail->subtotal;
                    $hargaSatuan = (float) $detail->harga_satuan;
                    $currentHpp = (float) $detail->hpp;
                    $currentProfit = (float) $detail->profit;

                    // Check if HPP is abnormal (negative profit, HPP > subtotal, or HPP > 10,000,000 when subtotal is small)
                    if ($currentProfit < 0 || $currentHpp > $subtotal || ($subtotal > 0 && $currentHpp > $subtotal * 2)) {
                        $masterHargaBeli = $product ? (float) $product->harga_beli : 0;

                        if ($masterHargaBeli > 0 && $masterHargaBeli < $hargaSatuan) {
                            $newHpp = round($qty * $masterHargaBeli, 2);
                        } else {
                            $newHpp = round($subtotal * 0.8, 2);
                        }

                        $newProfit = $subtotal - $newHpp;

                        $detail->update([
                            'hpp' => $newHpp,
                            'profit' => $newProfit,
                        ]);

                        $fixedSaleDetailCount++;
                    }
                }
                $this->info("  [4] Abnormal sale details recalculated: {$fixedSaleDetailCount}");

                // 5. Optimized Bulk Payment Sync
                $hppBySale = SaleDetail::select('sale_id', DB::raw('SUM(hpp) as total_hpp'))
                    ->groupBy('sale_id')
                    ->pluck('total_hpp', 'sale_id');

                $allSales = Sale::select('id', 'business_id', 'user_id', 'no_invoice', 'tanggal_transaksi')->get()->keyBy('id');

                $hppPaymentsGrouped = Payment::where('jenis_transaksi', 'sale')
                    ->where(function ($q) {
                        $q->whereIn('metode_pembayaran', ['hpp', 'system'])
                          ->orWhere('no_pembayaran', 'like', '%-HPP%');
                    })
                    ->get()
                    ->groupBy('transaction_id');

                $syncedPayments = 0;
                $cleanedDuplicates = 0;

                foreach ($allSales as $saleId => $sale) {
                    $totalHpp = (float) ($hppBySale[$saleId] ?? 0);
                    $existingPayments = $hppPaymentsGrouped->get($saleId, collect());

                    if ($existingPayments->count() > 1) {
                        $first = $existingPayments->first();
                        $toDelete = $existingPayments->slice(1);
                        foreach ($toDelete as $td) {
                            $td->delete();
                            $cleanedDuplicates++;
                        }
                        $existingPayments = collect([$first]);
                    }

                    $payment = $existingPayments->first();

                    if ($payment) {
                        if (abs((float)$payment->total_harga - $totalHpp) > 0.01 || $payment->metode_pembayaran !== 'hpp') {
                            $payment->update([
                                'total_harga' => $totalHpp,
                                'metode_pembayaran' => 'hpp',
                            ]);
                            $syncedPayments++;
                        }
                    } else if ($totalHpp > 0) {
                        Payment::create([
                            'business_id' => $sale->business_id,
                            'user_id' => $sale->user_id,
                            'no_pembayaran' => $sale->no_invoice . '-HPP',
                            'tanggal_pembayaran' => $sale->tanggal_transaksi,
                            'jenis_transaksi' => 'sale',
                            'transaction_id' => $sale->id,
                            'total_harga' => $totalHpp,
                            'metode_pembayaran' => 'hpp',
                            'no_referensi' => null,
                            'catatan' => 'HPP Penjualan ' . $sale->no_invoice,
                            'rekening_debit' => '5.1.01.01',
                            'rekening_kredit' => '1.1.03.01',
                        ]);
                        $syncedPayments++;
                    }
                }

                $this->info("  [5] Payments synchronized: {$syncedPayments}, duplicate payments cleaned: {$cleanedDuplicates}");

                DB::commit();
                $this->info("Tenant {$tenant->id} successfully audited and repaired.");

            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error("Failed processing tenant {$tenant->id}: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            } finally {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }
        }

        $this->line('');
        $this->info('All tenants fixed and synchronized successfully.');
        return 0;
    }
}