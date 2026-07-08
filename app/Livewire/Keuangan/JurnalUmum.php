<?php

namespace App\Livewire\Keuangan;

use App\Models\Account;
use App\Models\Inventory;
use App\Models\Jurnal;
use App\Models\Payment;
use App\Models\Transaction_type;
use App\Utils\InventarisUtil;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class JurnalUmum extends Component
{
    public $title = 'Jurnal Umum';

    public $business_id;

    public $jenis_transaksi = [];

    public $rekeningList = [];

    public $tanggal_transaksi;

    public $selectedJenis = null;

    public $selectedSumber = null;

    public $selectedTujuan = null;

    public $items = [];

    public $keterangan;

    public $total = 0;

    public $saldo = 0;

    public $harga_perolehan = 0;

    public $tahun;

    public $bulan;

    public $tanggal;

    public $jurnalUmum = null;

    public $inventaris = [];

    public function setHargaPerolehan($total)
    {
        $this->harga_perolehan = $total;
    }

    protected $listeners = [
        'setHargaPerolehan' => 'setHargaPerolehan',
        'setInventaris' => 'setInventaris',
        'setHapusInventaris' => 'setHapusInventaris',
    ];

    public function setInventaris($payload)
    {
        $this->inventaris = $payload;
    }

    public $hapusInventaris = [];

    public function setHapusInventaris($payload)
    {
        $this->hapusInventaris = $payload;
    }

    public function mount()
    {
        $this->business_id = auth()->user()->business_id;

        $this->jenis_transaksi = Transaction_type::all();
        $this->rekeningList = Account::where('business_id', $this->business_id)->get();
        $this->tanggal_transaksi = date('Y-m-d');
        $this->tahun = date('Y');
        $this->bulan = date('m');
        $this->tanggal = date('d');

        $akun = Account::where('business_id', auth()->user()->business_id)->get();
        $this->jurnalUmum = [
            'akun' => $akun,
            'jenis_transaksi' => $this->jenis_transaksi,
        ];

        $this->loadInventaris();
    }

    public function loadInventaris()
    {
        $this->inventarisList = Inventory::where('business_id', $this->business_id)
            ->orderBy('nama_barang')
            ->get();
    }

    public $inventarisList = [];

    public function saveJurnalUmum($data)
    {
        if (! is_array($data)) {
            $this->dispatch('alert', type: 'error', message: 'Data tidak valid');

            return;
        }

        DB::beginTransaction();

        try {
            if (empty($data['tanggal_pembayaran'])) {
                throw new \Exception('Tanggal Transaksi wajib diisi');
            }

            $sumber = $data['sumber_dana'] ?? '';
            $simpan = $data['disimpan_ke'] ?? '';

            $noPembayaran = 'PAY-'.date('Ymd').'-'.
                str_pad(Payment::withTrashed()->whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

            $urutan = Jurnal::whereYear('tanggal', $this->tahun)
                ->whereMonth('tanggal', $this->bulan)
                ->whereDay('tanggal', $this->tanggal)
                ->count() + 1;
            $noJurnal = 'JU-'.date('Ymd').'-'.str_pad($urutan, 4, '0', STR_PAD_LEFT);

            // PENGHAPUSAN / PENJUALAN ASET (Sumber dari akun Aset / Akumulasi Penyusutan)
            if (
                str_starts_with($sumber, '1.2.01') ||
                str_starts_with($sumber, '1.2.02')
            ) {
                if ($data['jenis_transaksi'] == 2) {
                    $hapus = $data['hapus_inventaris'] ?? null;

                    if (! $hapus || empty($hapus['id_barang']) || empty($hapus['alasan'])) {
                        throw new \Exception('Data hapus inventaris tidak lengkap');
                    }

                    $inv = Inventory::find($hapus['id_barang']);
                    if (! $inv) {
                        throw new \Exception('Inventaris tidak ditemukan');
                    }

                    $unitHapus = (int) ($hapus['unit'] ?? 0);
                    if ($unitHapus <= 0 || $unitHapus > $inv->jumlah) {
                        throw new \Exception('Jumlah unit hapus tidak valid');
                    }

                    $alasan = strtolower($hapus['alasan']);
                    $alasanMap = [
                        'hapus' => 'hapus',
                        'hilang' => 'hilang',
                        'rusak' => 'rusak',
                        'dijual' => 'jual',
                        'revaluasi' => 'revaluasi',
                    ];
                    $statusBaru = $alasanMap[$alasan] ?? $alasan;

                    $hargaRevaluasi = (float) ($hapus['harga_revaluasi'] ?? 0);
                    $hargaJual = (float) ($hapus['harga_jual'] ?? 0);
                    $nilaiBuku = (float) ($hapus['nilai_buku'] ?? 0);

                    $totalNilaiBuku = $nilaiBuku * $unitHapus;
                    $totalHapus = $inv->harga_satuan * $unitHapus;

                    if ($alasan === 'hapus') {
                        $sisaUnit = $inv->jumlah - $unitHapus;
                        $nilaiSisa = $inv->harga_satuan * $sisaUnit;

                        if ($sisaUnit <= 0) {
                            $inv->update([
                                'status' => $statusBaru,
                            ]);
                        } else {
                            if ($nilaiSisa <= 0) {
                                $inv->update([
                                    'status' => $statusBaru,
                                    'harga_satuan' => 1,
                                    'jumlah' => 1,
                                ]);
                            } else {
                                $inv->update([
                                    'jumlah' => $sisaUnit,
                                    'harga_satuan' => round($nilaiSisa / $sisaUnit, 2),
                                ]);

                                Inventory::create([
                                    'business_id' => $inv->business_id,
                                    'payment_id' => $inv->payment_id,
                                    'nama_barang' => $inv->nama_barang,
                                    'tanggal_beli' => $inv->tanggal_beli,
                                    'tanggal_validasi' => $data['tanggal_pembayaran'],
                                    'jumlah' => $unitHapus,
                                    'harga_satuan' => round($totalHapus / $unitHapus, 2),
                                    'umur_ekonomis' => $inv->umur_ekonomis,
                                    'jenis' => $inv->jenis,
                                    'kategori' => $inv->kategori,
                                    'status' => $statusBaru,
                                ]);
                            }
                        }

                        $jurnal = Jurnal::create([
                            'business_id' => $this->business_id,
                            'tanggal' => $data['tanggal_pembayaran'],
                            'keterangan' => 'Penghapusan '.$inv->nama_barang.' ('.$unitHapus.' unit) - '.$hapus['alasan'],
                            'relasi' => null,
                            'jumlah' => $totalNilaiBuku,
                            'urutan' => $noJurnal,
                            'user_id' => auth()->id(),
                        ]);

                        Payment::create([
                            'business_id' => $this->business_id,
                            'user_id' => auth()->id(),
                            'no_pembayaran' => $noPembayaran,
                            'tanggal_pembayaran' => $data['tanggal_pembayaran'],
                            'jenis_transaksi' => 'inventaris',
                            'transaction_id' => $jurnal->id,
                            'total_harga' => $totalNilaiBuku,
                            'metode_pembayaran' => 'tunai',
                            'no_referensi' => null,
                            'catatan' => 'Penghapusan '.$inv->nama_barang.' ('.$unitHapus.' unit)',
                            'rekening_debit' => $simpan,
                            'rekening_kredit' => $sumber,
                        ]);

                        DB::commit();
                        $this->loadInventaris();
                        $this->dispatch('alert', type: 'success', message: 'Penghapusan inventaris berhasil dicatat');
                        $this->dispatch('redirect', url: '/keuangan/jurnal-umum', timeout: 1000);

                        return;
                    }

                    if ($alasan === 'revaluasi') {
                        if ($hargaRevaluasi <= 0) {
                            throw new \Exception('Harga revaluasi wajib diisi');
                        }

                        $inv->update([
                            'harga_satuan' => round($hargaRevaluasi / $inv->jumlah, 2),
                            'tanggal_validasi' => $data['tanggal_pembayaran'],
                        ]);

                        DB::commit();
                        $this->loadInventaris();
                        $this->dispatch('alert', type: 'success', message: 'Revaluasi berhasil disimpan');
                        $this->dispatch('redirect', url: '/keuangan/jurnal-umum', timeout: 1000);

                        return;
                    }

                    if (in_array($alasan, ['rusak', 'hilang', 'dijual'])) {
                        $sisaUnit = $inv->jumlah - $unitHapus;
                        if ($sisaUnit <= 0) {
                            $inv->update(['status' => $statusBaru]);
                        } else {
                            $inv->update(['jumlah' => $sisaUnit]);
                            Inventory::create([
                                'business_id' => $inv->business_id,
                                'payment_id' => $inv->payment_id,
                                'nama_barang' => $inv->nama_barang,
                                'tanggal_beli' => $inv->tanggal_beli,
                                'tanggal_validasi' => $data['tanggal_pembayaran'],
                                'jumlah' => $unitHapus,
                                'harga_satuan' => $inv->harga_satuan,
                                'umur_ekonomis' => $inv->umur_ekonomis,
                                'jenis' => $inv->jenis,
                                'kategori' => $inv->kategori,
                                'status' => $statusBaru,
                            ]);
                        }

                        DB::commit();
                        $this->loadInventaris();
                        $this->dispatch('alert', type: 'success', message: 'Status inventaris diperbarui');
                        $this->dispatch('redirect', url: '/keuangan/jurnal-umum', timeout: 1000);

                        return;
                    }
                }
            } elseif (
                str_starts_with($simpan, '1.2.01') ||
                str_starts_with($simpan, '1.2.03')
            ) {
                $payment = Payment::create([
                    'business_id' => $this->business_id,
                    'user_id' => auth()->id(),
                    'no_pembayaran' => $noPembayaran,
                    'tanggal_pembayaran' => $data['tanggal_pembayaran'],
                    'jenis_transaksi' => 'inventaris',
                    'transaction_id' => null,
                    'total_harga' => $data['nominal'] ?? 0,
                    'metode_pembayaran' => 'tunai',
                    'no_referensi' => null,
                    'catatan' => $data['keterangan'] ?? 'Pembelian Inventaris',
                    'rekening_debit' => $simpan,
                    'rekening_kredit' => $sumber,
                ]);

                $kodeInventaris = explode('.', $simpan);
                $jenis = intval($kodeInventaris[2] ?? 1);
                $kategori = intval($kodeInventaris[3] ?? 1);

                if (! empty($data['inventaris'])) {
                    $inv = $data['inventaris'];
                    Inventory::create([
                        'business_id' => $this->business_id,
                        'payment_id' => $payment->id,
                        'nama_barang' => $inv['nama_barang'] ?? null,
                        'tanggal_beli' => $data['tanggal_pembayaran'] ?? null,
                        'tanggal_validasi' => now(),
                        'jumlah' => $inv['jumlah'] ?? 0,
                        'harga_satuan' => $inv['harga_satuan'] ?? 0,
                        'umur_ekonomis' => $inv['umur_ekonomis'] ?? 0,
                        'jenis' => $jenis,
                        'kategori' => $kategori,
                        'status' => 'baik',
                    ]);
                }
            } else {
                $jurnal = Jurnal::create([
                    'business_id' => $this->business_id,
                    'tanggal' => $data['tanggal_pembayaran'],
                    'keterangan' => $data['keterangan'] ?? null,
                    'relasi' => $data['relasi'] ?? null,
                    'jumlah' => $data['nominal'] ?? 0,
                    'urutan' => $noJurnal ?? 0,
                    'user_id' => auth()->id(),
                ]);

                Payment::create([
                    'business_id' => $this->business_id,
                    'user_id' => auth()->id(),
                    'no_pembayaran' => $noPembayaran,
                    'tanggal_pembayaran' => $data['tanggal_pembayaran'],
                    'jenis_transaksi' => 'jurnal_umum',
                    'transaction_id' => $jurnal->id,
                    'total_harga' => $data['nominal'] ?? 0,
                    'metode_pembayaran' => 'tunai',
                    'no_referensi' => null,
                    'catatan' => $data['keterangan'] ?? 'Transaksi Jurnal Umum',
                    'rekening_debit' => $simpan,
                    'rekening_kredit' => $sumber,
                ]);
            }
            DB::commit();

            $this->dispatch('alert', type: 'success', message: 'Transaksi berhasil disimpan');
            $this->dispatch('redirect', url: '/keuangan/jurnal-umum', timeout: 1000);
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('alert', type: 'error', message: $e->getMessage());
        }
    }

    public function render()
    {
        $this->loadInventaris();

        return view('livewire.keuangan.jurnal-umum', [
            'inventarisList' => $this->inventarisList,
            'tgl_transaksi' => $this->tanggal_transaksi,
        ])
            ->layout('layouts.app', ['title' => $this->title]);
    }
}
