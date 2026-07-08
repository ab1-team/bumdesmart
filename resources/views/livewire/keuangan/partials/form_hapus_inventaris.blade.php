@php
    use App\Utils\InventarisUtil;
    $inventarisData = $inventaris->map(function ($inv) use ($tgl_transaksi) {
        $nb = InventarisUtil::nilaiBuku($tgl_transaksi, $inv);
        return [
            'id' => $inv->id,
            'nama' => $inv->nama_barang,
            'jumlah' => (int) $inv->jumlah,
            'harga_satuan' => (float) $inv->harga_satuan,
            'nilai_buku' => (float) $nb,
            'kategori' => (int) $inv->kategori,
            'jenis' => (int) $inv->jenis,
        ];
    })->values();
@endphp

<div x-data='formHapusInventaris(@json($inventarisData))' class="row" x-init="init()">
    <div class="col-sm-8 mb-3">
        <label class="form-label" for="nama_barang">Nama Barang</label>
        <select class="form-control tom-select" name="nama_barang" id="nama_barang"
            placeholder="-- Pilih Nama Barang --">
            <option value="">-- Pilih Nama Barang --</option>
        </select>
        <small class="text-danger" id="msg_nama_barang"></small>
    </div>

    <div class="col-sm-4 mb-3">
        <label class="form-label" for="alasan">Alasan</label>
        <select class="form-control tom-select" name="alasan" id="alasan" placeholder="-- Pilih Alasan --">
            <option value="">-- Pilih Alasan --</option>
            <option value="hapus">Hapus</option>
            <option value="hilang">Hilang</option>
            <option value="rusak">Rusak</option>
            <option value="dijual">Dijual</option>
            <option value="revaluasi">Revaluasi</option>
        </select>
        <small class="text-danger" id="msg_alasan"></small>
    </div>

    <div id="col_unit" class="col-sm-4 mb-3">
        <label class="form-label" for="unit">Jumlah (unit)</label>
        <input autocomplete="off" type="number" name="unit" id="unit" class="form-control" min="1">
        <small class="text-danger d-none" id="msg_unit"></small>
    </div>

    <div id="col_nilai_buku" class="col-sm-4 mb-3">
        <label class="form-label" for="nilai_buku">Nilai Buku</label>
        <input autocomplete="off" readonly type="text" name="nilai_buku" id="nilai_buku" class="form-control">
        <small class="text-danger d-none" id="msg_nilai_buku"></small>
    </div>

    <div id="col_harga_jual" class="col-sm-4 mb-3 d-none">
        <label class="form-label" for="harga_jual">Harga Jual</label>
        <input autocomplete="off" type="text" name="harga_jual" id="harga_jual" class="form-control">
        <small class="text-danger d-none" id="msg_harga_jual"></small>
    </div>

    <div id="col_harga_revaluasi" class="col-sm-4 mb-3 d-none">
        <label class="form-label" for="harga_revaluasi">Harga Revaluasi</label>
        <input autocomplete="off" type="text" name="harga_revaluasi" id="harga_revaluasi" class="form-control">
        <small class="text-danger d-none" id="msg_harga_revaluasi"></small>
    </div>
</div>

@once
    @push('scripts')
        <script>
            (function() {
                function initMask() {
                    if (typeof jQuery === 'undefined' || typeof jQuery.fn.maskMoney !== 'function') {
                        setTimeout(initMask, 100);
                        return;
                    }
                    var $hj = jQuery('#harga_jual');
                    var $hrev = jQuery('#harga_revaluasi');
                    if ($hj.length && !$hj.data('masked')) {
                        $hj.maskMoney({
                            prefix: 'Rp ', thousands: '.', decimal: ',', precision: 0,
                            allowZero: true, allowNegative: false
                        });
                        $hj.data('masked', true);
                    }
                    if ($hrev.length && !$hrev.data('masked')) {
                        $hrev.maskMoney({
                            prefix: 'Rp ', thousands: '.', decimal: ',', precision: 0,
                            allowZero: true, allowNegative: false
                        });
                        $hrev.data('masked', true);
                    }
                }
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initMask);
                } else {
                    initMask();
                }
                jQuery(document).ajaxComplete(function() { setTimeout(initMask, 200); });
            })();
        </script>
    @endpush
@endonce