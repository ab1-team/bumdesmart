<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shelves;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use App\Models\Payment;
use App\Utils\PaymentUtil;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TambahStockOpname extends Component
{
    public $title;

    public $businessId;

    public $nomorOpname;

    public $status = 'draft';

    public $tanggalOpname;

    public $tanggalApproved;

    public $approvedBy;

    public $catatan;
    public $opnameId;
    public $existingData = null;

    // Properties for filtering and data
    public $products = [];

    public $categoryId = '';

    public $shelfId = '';

    public $categories = [];

    public $shelves = [];

    public function mount($id = null)
    {
        $this->title = $id ? 'Edit Stock Opname' : 'Tambah Stock Opname';
        $this->businessId = auth()->user()->business_id;

        if ($id) {
            $this->opnameId = $id;
            $this->loadOpnameData($id);
        }

        $this->loadMasterData();
    }

    public function loadOpnameData($id)
    {
        $opname = StockOpname::with(['details.product'])->findOrFail($id);

        if ($opname->status !== 'draft') {
            return redirect()->to('/stock/opname/daftar');
        }

        $this->nomorOpname = $opname->no_opname;
        $this->tanggalOpname = \Carbon\Carbon::parse($opname->tanggal_opname)->format('Y-m-d');
        $this->catatan = $opname->catatan;
        $this->status = $opname->status;

        $items = [];
        foreach ($opname->details as $detail) {
            $items[] = [
                'id' => $detail->product_id,
                'nama_produk' => $detail->product->nama_produk ?? 'Produk Terhapus',
                'kode_produk' => $detail->product->sku ?? '-',
                'harga_beli' => $detail->harga_satuan,
                'sistem' => $detail->stok_sistem,
                'fisik' => $detail->stok_fisik,
                'selisih' => $detail->selisih,
                'jenis_selisih' => $detail->jenis_selisih,
                'alasan' => $detail->alasan,
                'counted' => true,
            ];
        }

        $this->existingData = [
            'opnameId' => $id,
            'nomorOpname' => $opname->no_opname,
            'tanggalOpname' => \Carbon\Carbon::parse($opname->tanggal_opname)->format('Y-m-d'),
            'status' => $opname->status,
            'catatan' => $opname->catatan,
            'items' => $items
        ];
    }

    public function loadMasterData()
    {
        $this->categories = Category::where('business_id', $this->businessId)->get();
        $this->shelves = Shelves::where('business_id', $this->businessId)->get();
    }

    public function updatedCategoryId()
    {
        $this->loadProducts();
    }

    public function updatedShelfId()
    {
        $this->loadProducts();
    }

    /**
     * Load products based on filters
     * NO PAGINATION to prevent data loss during counting
     */
    public function loadProducts()
    {
        // Only load if at least one filter is selected or user explicitly asks (to prevent loading thousands of items at once)
        if (empty($this->categoryId) && empty($this->shelfId)) {
            $this->products = [];

            return;
        }

        $query = Product::where('business_id', $this->businessId)
            ->where('is_active', 1);

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        if ($this->shelfId) {
            $query->where('shelf_id', $this->shelfId);
        }

        $this->products = $query->orderBy('nama_produk')->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'nama_produk' => $product->nama_produk,
                    'kode_produk' => $product->kode_produk,
                    'harga_beli' => $product->harga_beli,
                    'sistem' => $product->stok_aktual,
                    'fisik' => $product->stok_aktual, // Default to system stock
                    'selisih' => 0,
                    'jenis_selisih' => 'match',
                    'alasan' => '',
                    'counted' => false, // UI helper
                ];
            })->toArray();
    }

    /**
     * Menyimpan Stock Opname
     */
    public function saveOpname($data)
    {
        if (! $data || ! is_array($data)) {
            $this->dispatch('alert', type: 'error', message: 'Data tidak valid');

            return;
        }

        DB::beginTransaction();

        try {
            $tanggalOpname = $data['tanggal'] ?? null;
            if (! $tanggalOpname) {
                throw new \Exception('Tanggal opname wajib diisi');
            }

            $status = $data['status'] ?? 'draft';

            // Logic validation
            if ($status === 'approved' && empty($data['approved_by'])) {
                // If approving, ensure we have approver. If not sent, default to current user
                $data['approved_by'] = auth()->id();
            }

            $tanggalApproved = $status === 'approved' ? ($data['tanggal_approved'] ?? now()->format('Y-m-d')) : null;
            $approvedBy = $status === 'approved' ? ($data['approved_by'] ?? auth()->id()) : null;

            if ($this->opnameId) {
                $opname = StockOpname::findOrFail($this->opnameId);
                
                if ($opname->status !== 'draft') {
                    throw new \Exception('Hanya Stock Opname status DRAFT yang dapat diedit.');
                }

                $opname->update([
                    'no_opname' => $data['no_opname'] ?: $opname->no_opname,
                    'tanggal_opname' => $tanggalOpname,
                    'status' => $status,
                    'catatan' => $data['catatan'] ?? null,
                    'approved_by' => $approvedBy,
                    'tanggal_approved' => $tanggalApproved,
                ]);

                // Clear old details
                StockOpnameDetail::where('stock_opname_id', $opname->id)->delete();
            } else {
                $opname = StockOpname::create([
                    'business_id' => $this->businessId,
                    'user_id' => auth()->id(),
                    'no_opname' => $data['no_opname'] ?: 'SO-'.now()->format('YmdHis'),
                    'tanggal_opname' => $tanggalOpname,
                    'status' => $status,
                    'catatan' => $data['catatan'] ?? null,
                    'approved_by' => $approvedBy,
                    'tanggal_approved' => $tanggalApproved,
                ]);
            }

            // Filter items that have been modified/counted or just save all?
            // Usually we save all loaded items to keep track of what was checked in this session
            // But if user filters multiple times, we might receive a consolidated list from frontend
            // OR we only save what is currently in $data['items'] which comes from frontend state

            foreach ($data['items'] as $item) {
                if (! isset($item['product_id'])) {
                    continue;
                }

                // Simpan detail opname
                StockOpnameDetail::create([
                    'stock_opname_id' => $opname->id,
                    'product_id' => $item['product_id'],
                    'stok_sistem' => $item['stok_sistem'],
                    'stok_fisik' => $item['stok_fisik'],
                    'selisih' => $item['selisih'],
                    'jenis_selisih' => $item['jenis_selisih'] ?? 'match',
                    'alasan' => $item['alasan'] ?? null,
                    'harga_satuan' => $item['harga_satuan'] ?? 0,
                    'total_harga' => ($item['selisih']) * ($item['harga_satuan'] ?? 0), // Total value of variance
                    'catatan' => $data['catatan'] ?? null,
                ]);

                // ONLY update stock and create movement IF status is APPROVED
                if ($status === 'approved') {
                    // Update stok aktual produk
                    Product::where('id', $item['product_id'])
                        ->update(['stok_aktual' => $item['stok_fisik']]);

                    // Buat stock movement per produk ONLY if there is a difference
                    if ($item['selisih'] != 0) {
                        StockMovement::create([
                            'business_id' => $this->businessId,
                            'product_id' => $item['product_id'],
                            'tanggal_perubahan_stok' => $tanggalOpname,
                            'jenis_perubahan' => 'stock opname', // Adjust based on enum/convention
                            'jumlah_perubahan' => $item['selisih'],
                            'reference_id' => $opname->id,
                            'reference_type' => 'stock_opname',
                            'catatan' => $item['alasan'] ?? $data['catatan'] ?? 'Stock Opname Adjustment',
                        ]);
                    }
                }
            }

            // 6. Generate Accounting Journal (Payments) IF APPROVED
            if ($status === 'approved') {
                $kodeRekening = PaymentUtil::ambilRekening('stock_opname');
                $timestamp = now();
                $payments = [];

                foreach ($data['items'] as $item) {
                    $selisih = $item['selisih'];
                    if ($selisih == 0) continue;

                    $totalValue = abs($selisih) * ($item['harga_satuan'] ?? 0);
                    if ($totalValue <= 0) continue;

                    $rekDebit = $kodeRekening['variance']['rekening_debit'];
                    $rekKredit = $kodeRekening['variance']['rekening_kredit'];

                    if ($selisih > 0) {
                        // Fisik > Sistem (Gain/Surplus)
                        // Debit: Persediaan (Asset increase)
                        // Kredit: Laba/Rugi Penyesuaian (Income-like)
                        $rekDebit = $kodeRekening['variance']['rekening_kredit'];
                        $rekKredit = $kodeRekening['variance']['rekening_debit'];
                        $memo = "Surplus Stok: " . ($item['nama_produk'] ?? $item['id']);
                    } else {
                        // Fisik < Sistem (Loss/Shrinkage)
                        // Debit: Laba/Rugi Penyesuaian (Expense increase)
                        // Kredit: Persediaan (Asset decrease)
                        $memo = "Penyusutan Stok: " . ($item['nama_produk'] ?? $item['id']);
                    }

                    $payments[] = [
                        'business_id' => $this->businessId,
                        'user_id' => auth()->id(),
                        'no_pembayaran' => $opname->no_opname . '-' . $item['product_id'],
                        'tanggal_pembayaran' => $tanggalOpname,
                        'jenis_transaksi' => 'stock_opname',
                        'transaction_id' => $opname->id,
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

            $this->dispatch('alert', type: 'success', message: 'Stock opname berhasil disimpan');
            $this->dispatch('redirect', url: '/stock/opname/daftar', timeout: 1000);
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function render()
    {

        return view('livewire.tambah-stock-opname')
            ->layout('layouts.app', ['title' => $this->title]);
    }
}
