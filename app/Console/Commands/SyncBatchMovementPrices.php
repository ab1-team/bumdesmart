<?php

namespace App\Console\Commands;

use App\Models\Owner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncBatchMovementPrices extends Command
{
    protected $signature = 'app:sync-batch-movement-prices {--tenant= : ID Owner tenant}';

    protected $description = 'Sinkronkan batch_movements.harga_satuan agar mengikuti product_batches.harga_satuan';

    public function handle()
    {
        if ($tenantId = $this->option('tenant')) {
            $owners = Owner::where('id', $tenantId)->get();

            if ($owners->isEmpty()) {
                $this->error("Tenant dengan ID {$tenantId} tidak ditemukan.");

                return 1;
            }
        } else {
            $owners = Owner::all();
        }

        if ($owners->isEmpty()) {
            $this->warn('Tidak ada tenant yang ditemukan.');

            return 0;
        }

        foreach ($owners as $owner) {
            $this->line('');
            $this->info("Memproses tenant: {$owner->nama_usaha} (ID: {$owner->id})");

            try {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }

                tenancy()->initialize($owner);

                $affected = $this->syncPrices();

                $this->info("  Baris batch_movements diperbarui: {$affected}");
            } catch (\Throwable $e) {
                $this->error("Gagal memproses tenant {$owner->id}: ".$e->getMessage());
            } finally {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }
        }

        $this->line('');
        $this->info('Sinkronisasi harga batch_movements selesai.');

        return 0;
    }

    /**
     * Selaraskan harga_satuan batch_movements dengan product_batches milik batch terkait.
     */
    protected function syncPrices(): int
    {
        // MySQL mendukung multi-table UPDATE ... JOIN.
        // affectingStatement dipakai (bukan statement) agar jumlah baris yang
        // benar-benar terupdate bisa dilaporkan; statement() hanya mengembalikan bool.
        if (DB::connection()->getDriverName() === 'mysql') {
            return (int) DB::affectingStatement('
                UPDATE batch_movements bm
                JOIN product_batches pb ON pb.id = bm.batch_id
                SET bm.harga_satuan = pb.harga_satuan
                WHERE ABS(bm.harga_satuan - pb.harga_satuan) > 0.01
            ');
        }

        // Driver lain (mis. sqlite pada test) memakai subquery berkorelasi
        // tanpa alias pada tabel target, karena sqlite tidak mendukung alias di UPDATE.
        return (int) DB::affectingStatement('
            UPDATE batch_movements
            SET harga_satuan = (
                SELECT pb.harga_satuan FROM product_batches pb
                WHERE pb.id = batch_movements.batch_id
            )
            WHERE ABS(batch_movements.harga_satuan - (
                SELECT pb2.harga_satuan FROM product_batches pb2
                WHERE pb2.id = batch_movements.batch_id
            )) > 0.01
        ');
    }
}
