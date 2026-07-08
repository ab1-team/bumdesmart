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
        'filterInventarisBySumberDana' => 'filterInventarisBySumberDana',
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

    public function loadInventaris($sumberDana = null)
    {
        $query = Inventory::where('business_id', $this->business_id)
            ->where('status', 'baik')
            ->orderBy('nama_barang');

        if ($sumberDana) {
            $parts = explode('.', $sumberDana);
            $digitKe3 = isset($parts[2]) ? intval($parts[2]) : 0;
            $digitKe4 = isset($parts[3]) ? intval($parts[3]) : 0;

            if (str_starts_with($sumberDana, '1.2.01.')) {
                $jenis = 1;
                $kategori = $digitKe4;
            } elseif (str_starts_with($sumberDana, '1.2.02.')) {
                $jenis = 1;
                $akumToKategori = [
                    '1.2.02.01' => 2,
                    '1.2.02.02' => 3,
                    '1.2.02.03' => 4,
                ];
                $kategori = $akumToKategori[$sumberDana] ?? 0;
            } elseif (str_starts_with($sumberDana, '1.2.03.')) {
                $jenis = 1;
                $kategori = $digitKe4;
            } else {
                $jenis = 0;
                $kategori = 0;
            }

            if ($jenis > 0 && $kategori > 0) {
                $query->where('jenis', $jenis)->where('kategori', $kategori);
            }
        }

        $this->inventarisList = $query->get();
    }

    public function filterInventarisBySumberDana($sumberDana)
    {
        if (is_array($sumberDana)) {
            $sumberDana = $sumberDana['sumberDana'] ?? null;
        }
        $this->currentSumberDanaFilter = $sumberDana;
        $this->loadInventaris($sumberDana);
        $this->inventarisList = $this->inventarisList->values();
    }

    public function updatedInventarisList()
    {
        $this->dispatch('refreshNamaBarangSelect');
    }

    public $inventarisList = [];

    public $currentSumberDanaFilter = null;

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

            $ts = strtotime($data['tanggal_pembayaran']);
            $urutan = Jurnal::whereYear('tanggal', date('Y', $ts))
                ->whereMonth('tanggal', date('m', $ts))
                ->whereDay('tanggal', date('d', $ts))
                ->count() + 1;
            $noJurnal = 'JU-'.date('Ymd', $ts).'-'.str_pad($urutan, 4, '0', STR_PAD_LEFT);

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

                    $totalNilaiBuku = round($nilaiBuku, 2);
                    $totalHapus = round($inv->harga_satuan * $unitHapus, 2);

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
                            'keterangan' => 'Penghapusan '.$inv->nama_barang.' ('.$unitHapus.' unit) - hapus',
                            'relasi' => '',
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
                            'catatan' => 'Penghapusan '.$inv->nama_barang.' ('.$unitHapus.' unit) - hapus',
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

                    if (in_array($alasan, ['rusak', 'hilang'])) {
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

                        $jurnal = Jurnal::create([
                            'business_id' => $this->business_id,
                            'tanggal' => $data['tanggal_pembayaran'],
                            'keterangan' => 'Penghapusan '.$inv->nama_barang.' ('.$unitHapus.' unit) - '.$alasan,
                            'relasi' => '',
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
                            'catatan' => 'Penghapusan '.$inv->nama_barang.' ('.$unitHapus.' unit) - '.$alasan,
                            'rekening_debit' => $simpan,
                            'rekening_kredit' => $sumber,
                        ]);

                        DB::commit();
                        $this->loadInventaris();
                        $this->dispatch('alert', type: 'success', message: 'Status inventaris diperbarui & dicatat di jurnal');
                        $this->dispatch('redirect', url: '/keuangan/jurnal-umum', timeout: 1000);

                        return;
                    }

                    if ($alasan === 'dijual') {
                        if ($hargaJual <= 0) {
                            throw new \Exception('Harga jual wajib diisi');
                        }

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

                        $totalHargaJual = round($hargaJual * $unitHapus, 2);

                        $jurnal1 = Jurnal::create([
                            'business_id' => $this->business_id,
                            'tanggal' => $data['tanggal_pembayaran'],
                            'keterangan' => 'Penghapusan '.$inv->nama_barang.' ('.$unitHapus.' unit) - dijual',
                            'relasi' => '',
                            'jumlah' => $totalNilaiBuku,
                            'urutan' => $noJurnal,
                            'user_id' => auth()->id(),
                        ]);

                        Payment::create([
                            'business_id' => $this->business_id,
                            'user_id' => auth()->id(),
                            'no_pembayaran' => $noPembayaran.'-A',
                            'tanggal_pembayaran' => $data['tanggal_pembayaran'],
                            'jenis_transaksi' => 'inventaris',
                            'transaction_id' => $jurnal1->id,
                            'total_harga' => $totalNilaiBuku,
                            'metode_pembayaran' => 'tunai',
                            'no_referensi' => null,
                            'catatan' => 'Penghapusan '.$inv->nama_barang.' ('.$unitHapus.' unit) - dijual',
                            'rekening_debit' => $simpan,
                            'rekening_kredit' => $sumber,
                        ]);

                        $ts = strtotime($data['tanggal_pembayaran']);
                        $noUrut2 = Jurnal::whereYear('tanggal', date('Y', $ts))
                            ->whereMonth('tanggal', date('m', $ts))
                            ->whereDay('tanggal', date('d', $ts))
                            ->count() + 2;
                        $noJurnal2 = 'JU-'.date('Ymd', $ts).'-'.str_pad($noUrut2, 4, '0', STR_PAD_LEFT);

                        $jurnal2 = Jurnal::create([
                            'business_id' => $this->business_id,
                            'tanggal' => $data['tanggal_pembayaran'],
                            'keterangan' => 'Penjualan '.$inv->nama_barang.' ('.$unitHapus.' unit) - dijual',
                            'relasi' => '',
                            'jumlah' => $totalHargaJual,
                            'urutan' => $noJurnal2,
                            'user_id' => auth()->id(),
                        ]);

                        Payment::create([
                            'business_id' => $this->business_id,
                            'user_id' => auth()->id(),
                            'no_pembayaran' => $noPembayaran.'-B',
                            'tanggal_pembayaran' => $data['tanggal_pembayaran'],
                            'jenis_transaksi' => 'inventaris',
                            'transaction_id' => $jurnal2->id,
                            'total_harga' => $totalHargaJual,
                            'metode_pembayaran' => 'tunai',
                            'no_referensi' => null,
                            'catatan' => 'Penjualan '.$inv->nama_barang.' ('.$unitHapus.' unit)',
                            'rekening_debit' => '1.1.01.01',
                            'rekening_kredit' => '4.1.01.05',
                        ]);

                        DB::commit();
                        $this->loadInventaris();
                        $this->dispatch('alert', type: 'success', message: 'Penjualan inventaris berhasil dicatat (2 jurnal)');
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
                $jenis = 1;
                $kategori = 0;
                if (str_starts_with($simpan, '1.2.01.') || str_starts_with($simpan, '1.2.03.')) {
                    $kategori = intval($kodeInventaris[3] ?? 1);
                }

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
                    'keterangan' => $data['keterangan'] ?? '',
                    'relasi' => $data['relasi'] ?? '',
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
        $this->loadInventaris($this->currentSumberDanaFilter);

        return view('livewire.keuangan.jurnal-umum', [
            'inventarisList' => $this->inventarisList,
            'tgl_transaksi' => $this->tanggal_transaksi,
        ])
            ->layout('layouts.app', ['title' => $this->title]);
    }
}
