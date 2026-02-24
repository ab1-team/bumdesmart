<?php

namespace App\Livewire\Keuangan;

use App\Models\Account;
use App\Models\Inventory;
use App\Models\Jurnal;
use App\Models\Payment;
use App\Models\Transaction_type;
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
    ];

    public function setInventaris($payload)
    {
        $this->inventaris = $payload;
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
    }

    public function saveJurnalUmum($data)
    {
        if (! is_array($data)) {
            $this->dispatch('alert', type: 'error', message: 'Data tidak valid');

            return;
        }
        // dd($data);

        DB::beginTransaction();

        try {
            if (empty($data['tanggal_pembayaran'])) {
                throw new \Exception('Tanggal Transaksi wajib diisi');
            }

            $sumber = $data['sumber_dana'] ?? '';
            $simpan = $data['disimpan_ke'] ?? '';

            $noPembayaran = 'PAY-'.date('Ymd').'-'.
                str_pad(Payment::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

            $urutan = Jurnal::whereYear('tanggal', $this->tahun)
                ->whereMonth('tanggal', $this->bulan)
                ->whereDay('tanggal', $this->tanggal)
                ->count() + 1;
            $noJurnal = 'JU-'.date('Ymd').'-'.str_pad($urutan, 4, '0', STR_PAD_LEFT);

            // PENGHAPUSAN / PENJUALAN ASET
            if (
                str_starts_with($sumber, '1.2.01.01') ||
                str_starts_with($sumber, '1.2.02')
            ) {
                if (str_starts_with($simpan, '5.3.02.01') && $data['jenis_transaksi'] == 2) {
                    $inventaris = Inventory::where('id', $data['id_barang'])->update([
                        'business_id' => auth()->user()->business_id,
                        'payment_id' => null,
                        'nama_barang' => $data['nama_barang'] ?? null,
                        'tanggal_beli' => $data['tanggal_pembelian'] ?? null,
                        'tanggal_validasi' => $data['tanggal_validasi'] ?? null,
                        'jumlah' => $data['jumlah'] ?? 0,
                        'harga_satuan' => $data['harga_satuan'] ?? 0,
                        'umur_ekonomis' => $data['umur_ekonomis'] ?? 0,
                        'jenis' => $data['jenis'] ?? null,
                        'kategori' => $data['kategori'] ?? null,
                        'status' => $data['status'] ?? null,
                    ]);

                    Payment::create(
                        [
                            'business_id' => auth()->user()->business_id,
                            'user_id' => auth()->id(),
                            'no_pembayaran' => $noPembayaran,
                            'tanggal_pembayaran' => $data['tanggal_pembayaran'],
                            'jenis_transaksi' => 'jurnal',
                            'transaksi_id' => $data['id_barang'],
                            'total_harga' => $data['nominal'] ?? 0,
                            'metode_pembayaran' => 'tunai',
                            'no_referensi' => null,
                            'catatan' => 'transaksi Jurnal Umum',
                            'rekening_debit' => $sumber,
                            'rekening_kredit' => $simpan,
                        ]
                    );
                }
                // PEMBELIAN INVENTARIS
            } elseif (
                str_starts_with($simpan, '1.2.01') ||
                str_starts_with($simpan, '1.2.03')
            ) {
                $payment = Payment::create([
                    'business_id' => auth()->user()->business_id,
                    'user_id' => auth()->id(),
                    'no_pembayaran' => $noPembayaran,
                    'tanggal_pembayaran' => $data['tanggal_pembayaran'],
                    'jenis_transaksi' => 'jurnal_inventaris',
                    'transaksi_id' => null,
                    'total_harga' => $data['nominal'] ?? 0,
                    'metode_pembayaran' => 'tunai',
                    'no_referensi' => null,
                    'catatan' => 'transaksi Jurnal Inventaris',
                    'rekening_debit' => $sumber,
                    'rekening_kredit' => $simpan,
                ]);
                if (! empty($data['inventaris'])) {
                    $inv = $data['inventaris'];
                    $inventaris = Inventory::create([
                        'business_id' => auth()->user()->business_id,
                        'payment_id' => $payment->id,
                        'nama_barang' => $inv['nama_barang'] ?? null,
                        'tanggal_beli' => $data['tanggal_pembayaran'] ?? null,
                        'tanggal_validasi' => now(),
                        'jumlah' => $inv['jumlah'] ?? 0,
                        'harga_satuan' => $inv['harga_satuan'] ?? 0,
                        'umur_ekonomis' => $inv['umur_ekonomis'] ?? 0,
                        'jenis' => 0,
                        'kategori' => 0,
                        'status' => 'baik',
                    ]);
                }
            } else {
                $jurnal = Jurnal::create([
                    'business_id' => auth()->user()->business_id,
                    'tanggal' => $data['tanggal_pembayaran'],
                    'keterangan' => $data['keterangan'] ?? null,
                    'relasi' => $data['relasi'] ?? null,
                    'jumlah' => $data['nominal'] ?? 0,
                    'urutan' => $noJurnal ?? 0,
                    'user_id' => auth()->id(),
                ]);

                Payment::create([
                    'business_id' => auth()->user()->business_id,
                    'user_id' => auth()->id(),
                    'no_pembayaran' => $noPembayaran,
                    'tanggal_pembayaran' => $data['tanggal_pembayaran'],
                    'jenis_transaksi' => 'jurnal',
                    'transaction_id' => $jurnal->id,
                    'total_harga' => $data['nominal'] ?? 0,
                    'metode_pembayaran' => 'tunai',
                    'no_referensi' => null,
                    'catatan' => 'transaksi Jurnal Umum',
                    'rekening_debit' => $sumber,
                    'rekening_kredit' => $simpan,
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
        return view('livewire.keuangan.jurnal-umum')
            ->layout('layouts.app', ['title' => $this->title]);
    }
}
