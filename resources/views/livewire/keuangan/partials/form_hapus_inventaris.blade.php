@php
    use App\Utils\InventarisUtil;
@endphp

<input type="hidden" name="_nilai_buku" id="_nilai_buku">
<input type="hidden" name="harsat" id="harsat">
<input type="hidden" name="relasi" id="relasi">
<input type="hidden" name="id_barang" id="id_barang">

<div x-data="formHapusInventaris()" class="row" x-init="init()">
    <div class="col-sm-8 mb-3">
        <label class="form-label" for="nama_barang">Nama Barang</label>
        @if ($inventaris->isEmpty())
            <div class="alert alert-warning text-dark py-2 mb-0"
                style="background-color:#fef3c7;border:1px solid #facc15;color:#92400e;">
                <strong>Belum ada data inventaris.</strong> Tambahkan aset melalui Jurnal Umum (Pembelian)
                terlebih dahulu.
            </div>
        @else
            <select class="form-control tom-select" name="nama_barang" id="nama_barang"
                placeholder="-- Pilih Nama Barang --">
                <option value="">-- Pilih Nama Barang --</option>
                @foreach ($inventaris as $inv)
                    @php
                        $nilai_buku = InventarisUtil::nilaiBuku($tgl_transaksi, $inv);
                    @endphp
                    <option value="{{ $inv->id }}#{{ $inv->jumlah }}#{{ $nilai_buku }}#{{ $inv->harga_satuan * $inv->jumlah }}">
                        {{ $inv->nama_barang }} ({{ $inv->jumlah }} unit x
                        {{ number_format($inv->harga_satuan, 2, ',', '.') }}) | NB.
                        {{ number_format($nilai_buku, 2, ',', '.') }}
                    </option>
                @endforeach
            </select>
        @endif
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
        <input autocomplete="off" type="number" name="unit" id="unit" class="form-control">
        <small class="text-danger" id="msg_unit"></small>
    </div>

    <div id="col_nilai_buku" class="col-sm-4 mb-3">
        <label class="form-label" for="nilai_buku">Nilai Buku</label>
        <input autocomplete="off" readonly type="text" name="nilai_buku" id="nilai_buku"
            class="form-control">
        <small class="text-danger" id="msg_nilai_buku"></small>
    </div>

    <div id="col_harga_jual" class="col-sm-4 mb-3 d-none">
        <label class="form-label" for="harga_jual">Harga Jual</label>
        <input autocomplete="off" type="text" name="harga_jual" id="harga_jual" class="form-control">
        <small class="text-danger" id="msg_harga_jual"></small>
    </div>

    <div id="col_harga_revaluasi" class="col-sm-4 mb-3 d-none">
        <label class="form-label" for="harga_revaluasi">Harga Revaluasi</label>
        <input autocomplete="off" type="text" name="harga_revaluasi" id="harga_revaluasi"
            class="form-control">
        <small class="text-danger" id="msg_harga_revaluasi"></small>
    </div>
</div>
