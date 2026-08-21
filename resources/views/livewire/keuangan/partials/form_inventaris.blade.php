<div x-ref="inventaris" x-data="asetForm()" class="row">

    <div class="col-sm-6 mb-3">
        <label>Nama Barang</label>
        <input type="text" x-model="nama_barang" class="form-control">
    </div>

    <div class="col-sm-6 mb-3">
        <label>Jml. Unit</label>
        <input type="number" x-model="jumlah" @input="hitung()" class="form-control">
    </div>

    <div class="col-sm-4 mb-3">
        <label>Harga Satuan</label>
        <input type="text" x-model="harga_satuan" x-mask:dynamic="$money($input, '.', ',', 2)" @input="hitung()" class="form-control" placeholder="0,00">
    </div>

    <div class="col-sm-4 mb-3">
        <label>Umur Eko (bulan)</label>
        <input type="number" x-model="umur_ekonomis" class="form-control">
    </div>

    <div class="col-sm-4 mb-3">
        <label>Harga Perolehan</label>
        <input type="text" x-model="harga_perolehan" readonly class="form-control">
    </div>
</div>