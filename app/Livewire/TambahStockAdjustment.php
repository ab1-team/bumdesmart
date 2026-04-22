<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentDetail;
use App\Models\StockMovement;
use App\Models\Payment;
use App\Utils\PaymentUtil;

class TambahStockAdjustment extends Component
{
    public $title;
    public $businessId;

    public function mount()
    {
        $this->title = 'Tambah Stock Adjustment';
        $this->businessId = auth()->user()->business_id;
    }

    public function loadProducts($page = 1, $search = '')
    {
        $query = Product::where('business_id', $this->businessId);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nama_produk', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $data = $query
            ->orderBy('nama_produk')
            ->skip(($page - 1) * 20)
            ->take(21)
            ->get();

        return [
            'data' => $data->take(20)->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama_produk' => $item->nama_produk,
                    'sku' => $item->sku,
                    'stok_aktual' => $item->stok_aktual,
                    'harga_beli' => $item->harga_beli,
                    'unit' => $item->unit->nama_satuan ?? '',
                    'gambar' => $item->gambar ? asset('storage/' . $item->gambar) : null,
                ];
            })->values(),
            'has_more' => $data->count() > 20,
        ];
    }
    public function saveAdjustment($data)
    {
        if (!is_array($data) || empty($data['items'])) {
            $this->dispatch('alert', type: 'error', message: 'Data tidak valid');
            return;
        }

        DB::beginTransaction();

        try {
            if (empty($data['tanggal_penyesuaian'])) {
                throw new \Exception('Tanggal penyesuaian wajib diisi');
            }

            $status = $data['status'] ?? 'draft';

            $adjustment = StockAdjustment::create([
                'business_id'         => $this->businessId,
                'user_id'             => auth()->id(),
                'no_penyesuaian'      => $data['no_penyesuaian'] ?: 'ADJ-' . now()->format('YmdHis'),
                'tanggal_penyesuaian' => $data['tanggal_penyesuaian'],
                'jenis_penyesuaian'   => $data['jenis_penyesuaian'] ?? 'correction',
                'status'              => $status,
                'catatan'             => $data['catatan'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                if (
                    !isset(
                        $item['product_id'],
                        $item['stok_sistem'],
                        $item['stok_fisik'],
                        $item['selisih']
                    )
                ) {
                    continue;
                }

                $selisih = (int) $item['selisih'];
                if ($selisih === 0) continue;

                StockAdjustmentDetail::create([
                    'stock_adjustment_id' => $adjustment->id,
                    'product_id'          => $item['product_id'],
                    'jumlah'              => abs($selisih),
                    'jenis'               => $selisih > 0 ? 'in' : 'out',
                    'harga_satuan'        => $item['harga_satuan'],
                    'total_harga'         => abs($selisih) * $item['harga_satuan'],
                    'alasan'              => $item['alasan'] ?? null,
                    'catatan'             => $data['catatan'] ?? null,
                ]);

                // ONLY update stock and create movement IF status is APPROVED
                if ($status === 'approved') {
                    $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                    $product->stok_aktual += $selisih;
                    $product->save();

                    StockMovement::create([
                        'business_id'            => $this->businessId,
                        'product_id'             => $item['product_id'],
                        'tanggal_perubahan_stok' => $data['tanggal_penyesuaian'],
                        'jenis_perubahan'        => 'stock_adjustment',
                        'jumlah_perubahan'       => $selisih,
                        'reference_id'           => $adjustment->id,
                        'reference_type'         => 'stock_adjustment',
                        'catatan'                => $item['alasan'] ?? $data['catatan'] ?? null,
                    ]);
                }
            }

            // Generate Accounting Journal (Payments) IF APPROVED
            if ($status === 'approved') {
                $kodeRekening = PaymentUtil::ambilRekening('stock_adjustment');
                $timestamp = now();
                $payments = [];

                foreach ($data['items'] as $item) {
                    $selisih = (float)($item['selisih'] ?? 0);
                    if ($selisih == 0) continue;

                    $totalValue = abs($selisih) * ($item['harga_satuan'] ?? 0);
                    if ($totalValue <= 0) continue;

                    $rekDebit = $kodeRekening['variance']['rekening_debit'];
                    $rekKredit = $kodeRekening['variance']['rekening_kredit'];

                    if ($selisih > 0) {
                        // Profit/Gain
                        $rekDebit = $kodeRekening['variance']['rekening_kredit'];
                        $rekKredit = $kodeRekening['variance']['rekening_debit'];
                        $memo = "Adjustment In: " . ($item['nama_produk'] ?? $item['product_id']);
                    } else {
                        // Loss
                        $memo = "Adjustment Out: " . ($item['nama_produk'] ?? $item['product_id']);
                    }

                    $payments[] = [
                        'business_id' => $this->businessId,
                        'user_id' => auth()->id(),
                        'no_pembayaran' => $adjustment->no_penyesuaian . '-' . $item['product_id'],
                        'tanggal_pembayaran' => $data['tanggal_penyesuaian'],
                        'jenis_transaksi' => 'stock_adjustment',
                        'transaction_id' => $adjustment->id,
                        'total_harga' => $totalValue,
                        'metode_pembayaran' => 'system',
                        'no_referensi' => null,
                        'catatan' => $memo,
                        'rekening_debit' => $rekDebit,
                        'rekening_kredit' => $rekKredit,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }

                if (!empty($payments)) {
                    Payment::insert($payments);
                }
            }

            DB::commit();

            $this->dispatch('alert', type: 'success', message: 'Stock adjustment berhasil disimpan');
            $this->dispatch('redirect', url: '/stock-adjustment/daftar', timeout: 1000);
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }


    public function render()
    {
        return view('livewire.tambah-stock-adjustment')
            ->layout('layouts.app', ['title' => $this->title]);
    }
}
