<div wire:ignore x-data="jurnalUmum()" x-init="initData(@js($jurnalUmum))">
    <div class="row">
        <div class="col-md-9">
            <div class="card">
                <div class="card-body">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Transaksi</label>
                            <input type="text" class="form-control litepicker" id="tanggal_transaksi"
                                value="{{ date('Y-m-d') }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jenis Transaksi</label>
                            <select class="form-control" id="jenis_transaksi" x-model="selectedJenisTransaksi">
                                <option value="">-- Pilih Jenis Transaksi --</option>
                            </select>
                        </div>
                    </div>

                    <div class="row" id="kd_rekening">
                        <div class="col-sm-6 mb-3">
                            <label class="form-label" for="sumber_dana">Sumber Dana</label>
                            <select class="form-control" x-model="selectedSumberDana" id="sumber_dana">
                                <option value="">-- Pilih Sumber Dana --</option>
                            </select>
                        </div>

                        <div class="col-sm-6 mb-3">
                            <label class="form-label" for="disimpan_ke">Disimpan Ke</label>
                            <select class="form-control" x-model="selectedDisimpanKe" id="disimpan_ke">
                                <option value="">-- Disimpan Ke --</option>
                            </select>
                        </div>
                    </div>

                    <div class="row" x-show="!showFormInventaris && mode !== 'hapus'">
                        <template x-for="(item, index) in inputKeterangan" :key="index">
                            <div class="mb-3" :class="inputKeterangan.length > 1 ? 'col-sm-6' : 'col-12'">
                                <label class="form-label" x-text="item.label"></label>
                                <input type="text" class="form-control" :value="item.value"
                                    x-model="item.value">
                            </div>
                        </template>
                    </div>

                    <div x-show="showFormInventaris">
                        @include('livewire.keuangan.partials.form_inventaris')
                    </div>

                    <div x-show="mode === 'hapus'">
                        @include('livewire.keuangan.partials.form_hapus_inventaris', [
                            'inventaris' => $inventarisList,
                            'tgl_transaksi' => date('Y-m-d'),
                        ])
                    </div>

                    <div class="row" x-show="!showFormInventaris && mode !== 'hapus'">
                        <div class="col-12 my-3">
                            <label class="form-label">Nominal Rp.</label>
                            <input type="text" class="form-control" x-model="nominalFormatted"
                                x-on:input="formatNominal">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-primary" x-on:click="simpanTransaksi">Simpan
                            Transaksi</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">Saldo</label>
                        <h4 class="mb-0">Rp. <span id="saldo">0</span></h4>
                    </div>
                    <input type="hidden" id="saldo_trx">
                </div>
            </div>
        </div>
    </div>
</div>
@section('script')
    <script>
        function asetForm() {
            return {
                nama_barang: '',
                umur_ekonomis: '',
                jumlah: 0,
                harga_satuan: '',
                harga_perolehan: '',

                init() {
                    window.addEventListener('kirimInventaris', () => {
                        this.kirimData();
                    });
                },

                formatHarga() {
                    let angka = this.harga_satuan.replace(/\D/g, '');
                    let nilai = angka ? parseInt(angka) : 0;
                    this.harga_satuan = new Intl.NumberFormat('id-ID').format(nilai);
                    this.hitung();
                },

                hitung() {
                    let harga = this.harga_satuan.replace(/\D/g, '');
                    let jumlah = parseInt(this.jumlah) || 0;
                    let total = (parseInt(harga) || 0) * jumlah;

                    this.harga_perolehan = new Intl.NumberFormat('id-ID').format(total);

                    Livewire.dispatch('setHargaPerolehan', {
                        total: total
                    });
                },

                kirimData() {
                    const data = {
                        nama_barang: this.nama_barang,
                        umur_ekonomis: this.umur_ekonomis,
                        jumlah: this.jumlah,
                        harga_satuan: this.harga_satuan.replace(/\D/g, ''),
                        harga_perolehan: this.harga_perolehan.replace(/\D/g, '')
                    };

                    window.dispatchEvent(new CustomEvent('inventarisUpdated', {
                        detail: data
                    }));
                }
            }
        }

        function formHapusInventaris(allInventaris) {
            return {
                all: allInventaris || [],
                selectedId: '',

                init() {
                    this.$nextTick(() => {
                        this.initTomSelects();
                        this.populateBarang();
                        this.bindEvents();
                    });

                    // Re-init saat sumber dana berubah (dipanggil global dari Alpine parent)
                    window.addEventListener('sumber-dana-changed', () => {
                        this.$nextTick(() => this.populateBarang());
                    });
                },

                initTomSelects() {
                    const nb = document.getElementById('nama_barang');
                    const al = document.getElementById('alasan');
                    if (nb && !nb.tomselect && typeof initSingleTomSelect === 'function') {
                        initSingleTomSelect(nb);
                    }
                    if (al && !al.tomselect && typeof initSingleTomSelect === 'function') {
                        initSingleTomSelect(al);
                    }
                },

                getKategoriForSumber(sumberDana) {
                    if (!sumberDana) return null;
                    if (sumberDana === '1.2.01.01') return 1;
                    if (sumberDana === '1.2.01.02') return 2;
                    if (sumberDana === '1.2.01.03') return 3;
                    if (sumberDana === '1.2.01.04') return 4;
                    if (sumberDana === '1.2.02.01') return 2;
                    if (sumberDana === '1.2.02.02') return 3;
                    if (sumberDana === '1.2.02.03') return 4;
                    return null;
                },

                populateBarang() {
                    const nb = document.getElementById('nama_barang');
                    if (!nb) return;

                    const sumberDana = window.__selectedSumberDana || '';
                    const kategori = this.getKategoriForSumber(sumberDana);
                    const list = (kategori === null)
                        ? this.all
                        : this.all.filter(x => parseInt(x.kategori) === kategori);

                    const opts = list.map(x => ({
                        value: x.id + '#' + x.jumlah + '#' + x.nilai_buku + '#' + (x.harga_satuan * x.jumlah),
                        text: `${x.nama} (${x.jumlah} unit x ${new Intl.NumberFormat('id-ID').format(x.harga_satuan)}) | NB. ${new Intl.NumberFormat('id-ID').format(Math.round(x.nilai_buku))}`
                    }));

                    if (typeof Select !== 'undefined' && Select['nama_barang']) {
                        Select['nama_barang'].clear(true);
                        Select['nama_barang'].clearOptions();
                        opts.forEach(o => Select['nama_barang'].addOption(o));
                        Select['nama_barang'].refreshOptions(false);
                    } else {
                        // fallback jika TomSelect belum ready
                        nb.innerHTML = '<option value="">-- Pilih Nama Barang --</option>';
                        opts.forEach(o => {
                            const opt = document.createElement('option');
                            opt.value = o.value;
                            opt.textContent = o.text;
                            nb.appendChild(opt);
                        });
                    }

                    this.resetForm();
                },

                resetForm() {
                    this.selectedId = '';
                    const unit = document.getElementById('unit');
                    const nb = document.getElementById('nilai_buku');
                    if (unit) { unit.value = ''; unit.removeAttribute('max'); }
                    if (nb) nb.value = '';
                    this.emit();
                },

                bindEvents() {
                    const self = this;
                    const nb = document.getElementById('nama_barang');
                    const al = document.getElementById('alasan');
                    const unit = document.getElementById('unit');
                    const hj = document.getElementById('harga_jual');
                    const hrev = document.getElementById('harga_revaluasi');
                    const colHJ = document.getElementById('col_harga_jual');
                    const colHR = document.getElementById('col_harga_revaluasi');
                    if (!nb || !al || !unit) return;

                    const fire = () => {
                        const rawValue = nb.value || (Select['nama_barang'] ? Select['nama_barang'].getValue() : '');
                        const v = rawValue ? rawValue.split('#') : [];
                        const idBarang = v[0] || '';
                        const totalUnit = parseInt(v[1] || 0);
                        const totalNB = parseFloat(v[2] || 0);

                        this.selectedId = idBarang;

                        // Auto-fill unit saat barang baru dipilih
                        if (idBarang && (!unit.value || parseInt(unit.value) === 0)) {
                            unit.value = totalUnit;
                        }

                        if (totalUnit > 0) {
                            unit.setAttribute('max', totalUnit);
                        } else {
                            unit.removeAttribute('max');
                        }

                        let u = parseInt(unit.value || 0);
                        if (totalUnit > 0 && u > totalUnit) { unit.value = totalUnit; u = totalUnit; }
                        if (u < 1) u = 0;

                        const nbPerUnit = totalUnit > 0 ? (totalNB / totalUnit) : 0;
                        const nbVal = u > 0 ? nbPerUnit * u : 0;
                        const nilaiBuku = document.getElementById('nilai_buku');
                        if (nilaiBuku) {
                            nilaiBuku.value = nbVal > 0
                                ? new Intl.NumberFormat('id-ID').format(Math.round(nbVal))
                                : '';
                        }

                        const alasanVal = al.value || '';
                        if (colHJ) colHJ.classList.toggle('d-none', alasanVal !== 'dijual');
                        if (colHR) colHR.classList.toggle('d-none', alasanVal !== 'revaluasi');

                        this.emit(nbVal, totalUnit, totalNB);
                    };

                    nb.addEventListener('change', fire);
                    al.addEventListener('change', fire);
                    unit.addEventListener('input', fire);
                    if (hj) hj.addEventListener('input', fire);
                    if (hrev) hrev.addEventListener('input', fire);

                    if (typeof Select !== 'undefined') {
                        if (Select['nama_barang']) {
                            Select['nama_barang'].off('change');
                            Select['nama_barang'].on('change', () => {
                                try { nb.value = Select['nama_barang'].getValue() || ''; } catch (e) {}
                                fire();
                            });
                        }
                        if (Select['alasan']) {
                            Select['alasan'].off('change');
                            Select['alasan'].on('change', () => {
                                try { al.value = Select['alasan'].getValue() || ''; } catch (e) {}
                                fire();
                            });
                        }
                    }
                },

                emit(nbVal, totalUnit, totalHP) {
                    const al = document.getElementById('alasan');
                    const unit = document.getElementById('unit');
                    const hj = document.getElementById('harga_jual');
                    const hrev = document.getElementById('harga_revaluasi');
                    let hjRaw = 0, hrevRaw = 0;
                    if (hj) {
                        try { hjRaw = parseFloat((jQuery(hj).maskMoney('unmasked')[0] || '').toString().replace(',', '.')) || 0; }
                        catch (e) { hjRaw = parseFloat((hj.value || '').replace(/[^0-9]/g, '')) || 0; }
                    }
                    if (hrev) {
                        try { hrevRaw = parseFloat((jQuery(hrev).maskMoney('unmasked')[0] || '').toString().replace(',', '.')) || 0; }
                        catch (e) { hrevRaw = parseFloat((hrev.value || '').replace(/[^0-9]/g, '')) || 0; }
                    }

                    const alasanVal = al ? al.value : '';
                    window.dispatchEvent(new CustomEvent('hapusInventarisUpdated', {
                        detail: {
                            id_barang: this.selectedId,
                            alasan: alasanVal,
                            unit: parseInt(unit ? unit.value : 0) || 0,
                            total_unit: totalUnit || 0,
                            nilai_buku: nbVal || 0,
                            harga_perolehan_total: totalHP || 0,
                            harga_jual: alasanVal === 'dijual' ? hjRaw : null,
                            harga_revaluasi: alasanVal === 'revaluasi' ? hrevRaw : null,
                        }
                    }));
                }
            }
        }
    </script>
    <script>
        let jenisTransaksi = new TomSelect('#jenis_transaksi', {
            valueField: 'id',
            labelField: 'label',
            searchField: 'label',
            options: [],
            onChange: function(value) {
                let el = document.querySelector('[x-data]');
                if (el) Alpine.$data(el).selectedJenisTransaksi = value;
            }
        });

        let sumberDana = new TomSelect('#sumber_dana', {
            valueField: 'kode',
            labelField: 'label',
            searchField: 'label',
            options: [],
            onChange: function(value) {
                let el = document.querySelector('[x-data]');
                if (el) {
                    Alpine.$data(el).selectedSumberDana = value;
                    window.__selectedSumberDana = value;
                    window.dispatchEvent(new CustomEvent('sumber-dana-changed', { detail: value }));
                }
            }
        });
        window.__selectedSumberDana = '';

        let disimpanKe = new TomSelect('#disimpan_ke', {
            valueField: 'kode',
            labelField: 'label',
            searchField: 'label',
            options: [],
            onChange: function(value) {
                let el = document.querySelector('[x-data]');
                if (el) Alpine.$data(el).selectedDisimpanKe = value;
            }
        });

        document.addEventListener('alpine:init', () => {
            Alpine.data('jurnalUmum', () => ({
                akun: [],
                jenisTransaksi: [],
                inputKeterangan: [],

                selectedJenisTransaksi: '',
                selectedSumberDana: '',
                selectedDisimpanKe: '',

                nominal: 0,
                nominalFormatted: '',

                showFormInventaris: false,
                inventarisData: null,
                hapusData: null,
                mode: 'normal',

                formatNominal() {
                    let angka = this.nominalFormatted.replace(/\D/g, '');
                    this.nominal = angka ? parseInt(angka) : 0;
                    this.nominalFormatted = new Intl.NumberFormat('id-ID').format(this.nominal);
                },

                init() {
                    window.addEventListener('inventarisUpdated', (event) => {
                        this.inventarisData = event.detail;
                    });

                    window.addEventListener('hapusInventarisUpdated', (event) => {
                        this.hapusData = event.detail;
                    });

                    this.$watch('selectedJenisTransaksi', (value) => {
                        this.setKodeAkun(value);
                    });

                    this.$watch('selectedDisimpanKe', () => {
                        this.setKodeAkun(this.selectedJenisTransaksi);
                    });

                    this.$watch('selectedSumberDana', () => {
                        this.setKodeAkun(this.selectedJenisTransaksi);
                    });

                    const updateKeterangan = () => {
                        const sumber = this.akun.find(a => a.kode == this.selectedSumberDana);
                        const disimpan = this.akun.find(a => a.kode == this.selectedDisimpanKe);

                        if (sumber && disimpan) {
                            const ketField = this.inputKeterangan.find(f => f.label ===
                                'Keterangan');
                            if (ketField) {
                                ketField.value = `dari ${sumber.nama} ke ${disimpan.nama}`;
                            }
                        }
                    };
                    this.$watch('selectedSumberDana', updateKeterangan);
                    this.$watch('selectedDisimpanKe', updateKeterangan);
                },

                initData(jurnalUmum) {
                    this.akun = jurnalUmum.akun;
                    this.jenisTransaksi = jurnalUmum.jenis_transaksi;

                    const jenisTransaksiOptions = this.jenisTransaksi.map((item) => {
                        return {
                            id: item.id,
                            label: item.nama
                        }
                    })

                    jenisTransaksi.clearOptions()
                    jenisTransaksi.addOptions(jenisTransaksiOptions)
                },

                setKodeAkun(jenisTransaksiId) {
                    sumberDana.clearOptions();
                    disimpanKe.clearOptions();

                    const akunSumber = this.akun.find(a => a.kode == this.selectedSumberDana);
                    const akunTujuan = this.akun.find(a => a.kode == this.selectedDisimpanKe);

                    const kodesumber = akunSumber ? akunSumber.kode : '';
                    const kodetujuan = akunTujuan ? akunTujuan.kode : '';

                    this.showFormInventaris = false;
                    this.mode = 'normal';

                    if (jenisTransaksiId === '1') {
                        if (kodetujuan.startsWith('1.1.01') || kodetujuan.startsWith('1.1.02')) {
                            this.inputKeterangan = [{
                                    label: 'Relasi',
                                    value: ''
                                },
                                {
                                    label: 'Keterangan',
                                    value: ''
                                }
                            ];
                        } else if (kodetujuan.startsWith('1.2.01') || kodetujuan.startsWith('1.2.02') ||
                            kodetujuan.startsWith('1.2.03')) {
                            this.showFormInventaris = true;
                        } else {
                            this.inputKeterangan = [{
                                label: 'Keterangan',
                                value: ''
                            }];
                        }

                        this.setAkunJenisTransaksi1();
                        if (this.selectedSumberDana) sumberDana.setValue(this.selectedSumberDana, true);
                        if (this.selectedDisimpanKe) disimpanKe.setValue(this.selectedDisimpanKe, true);
                    }

                    if (jenisTransaksiId === '2') {
                        if (
                            kodesumber.startsWith('1.2.01') ||
                            kodesumber.startsWith('1.2.02')
                        ) {
                            if (kodetujuan === '7.2.02.01') {
                                this.mode = 'hapus';
                                this.inputKeterangan = [];
                                this.showFormInventaris = false;
                                this.setAkunHapusInventaris();
                                return;
                            }

                            this.mode = 'normal';
                            this.inputKeterangan = [
                                { label: 'Relasi', value: '' },
                                { label: 'Keterangan', value: '' }
                            ];
                            this.setAkunJenisTransaksi2();
                            if (this.selectedSumberDana) sumberDana.setValue(this.selectedSumberDana, true);
                            if (this.selectedDisimpanKe) disimpanKe.setValue(this.selectedDisimpanKe, true);
                            return;
                        }

                        if (kodesumber.startsWith('1.1.01')) {
                            this.inputKeterangan = [{
                                    label: 'Relasi',
                                    value: ''
                                },
                                {
                                    label: 'Keterangan',
                                    value: ''
                                }
                            ];
                        } else {
                            this.inputKeterangan = [{
                                label: 'Keterangan',
                                value: ''
                            }];
                        }

                        this.setAkunJenisTransaksi2();
                        if (this.selectedSumberDana) sumberDana.setValue(this.selectedSumberDana, true);
                        if (this.selectedDisimpanKe) disimpanKe.setValue(this.selectedDisimpanKe, true);
                    }

                    if (jenisTransaksiId === '3') {
                        if (kodetujuan.startsWith('1.1.01') || kodetujuan.startsWith('1.1.02')) {
                            this.inputKeterangan = [{
                                    label: 'Relasi',
                                    value: ''
                                },
                                {
                                    label: 'Keterangan',
                                    value: ''
                                }
                            ];
                        } else if (kodetujuan.startsWith('1.2.01') || kodetujuan.startsWith('1.2.02') ||
                            kodetujuan.startsWith('1.2.03')) {
                            this.showFormInventaris = true;
                        } else {
                            this.inputKeterangan = [{
                                label: 'Keterangan',
                                value: ''
                            }];
                        }
                        this.setAkunJenisTransaksi3();
                        if (this.selectedSumberDana) sumberDana.setValue(this.selectedSumberDana, true);
                        if (this.selectedDisimpanKe) disimpanKe.setValue(this.selectedDisimpanKe, true);
                    }
                },

                setAkunJenisTransaksi1() {
                    let akunSumberDana = [];
                    this.akun.forEach(item => {
                        const kode = item.kode;
                        const nama = item.nama;

                        if (!item.kode.startsWith('1.1.01') && !item.kode.startsWith(
                                '1.1.02') && !item.kode.startsWith('1.1.03') && !item.kode
                            .startsWith('1.1.04') && !item.kode.startsWith('1.1.05') && !item
                            .kode.startsWith('1.1.06') && !item.kode.startsWith('1.1.07') && !
                            item.kode.startsWith('1.2.01') && !item.kode.startsWith('1.2.02') &&
                            !item.kode.startsWith('1.2.03') && !item.kode.startsWith(
                                '1.2.04') && !item.kode.startsWith('1.2.05') && !item.kode
                            .startsWith('1.3.01')) {
                            akunSumberDana.push({
                                id: item.id,
                                kode: kode,
                                label: `${kode}. - ${nama}`
                            });
                        }

                    });
                    sumberDana.addOptions(akunSumberDana);

                    let akunDisimpanKe = [];
                    this.akun.forEach(item => {
                        const kode = item.kode;
                        const nama = item.nama;

                        akunDisimpanKe.push({
                            id: item.id,
                            kode: kode,
                            label: `${kode}. - ${nama}`
                        });
                    });
                    disimpanKe.addOptions(akunDisimpanKe);
                },

                setAkunJenisTransaksi2() {
                    let akunSumberDana = [];
                    this.akun.forEach(item => {
                        const kode = item.kode;
                        const nama = item.nama;

                        akunSumberDana.push({
                            id: item.id,
                            kode: kode,
                            label: `${kode}. - ${nama}`
                        });
                    });
                    sumberDana.addOptions(akunSumberDana);

                    let akunDisimpanKe = [];
                    this.akun.forEach(item => {
                        const kode = item.kode;
                        const nama = item.nama;

                        if (!item.kode.startsWith('1.1.01') && !item.kode.startsWith(
                                '1.1.02') && !item.kode.startsWith('1.1.03') && !item.kode
                            .startsWith('1.1.04') && !item.kode.startsWith('1.1.05') && !item
                            .kode.startsWith('1.1.06') && !item.kode.startsWith('1.1.07') && !
                            item.kode.startsWith('1.2.01') && !item.kode.startsWith('1.2.02') &&
                            !item.kode.startsWith('1.2.03') && !item.kode.startsWith(
                                '1.2.04') && !item.kode.startsWith('1.2.05') && !item.kode
                            .startsWith('1.3.01')) {

                            akunDisimpanKe.push({
                                id: item.id,
                                kode: kode,
                                label: `${kode}. - ${nama}`
                            });
                        }
                    });
                    disimpanKe.addOptions(akunDisimpanKe);
                },

                setAkunJenisTransaksi3() {
                    let akunSumberDana = [];
                    this.akun.forEach(item => {
                        const kode = item.kode;
                        const nama = item.nama;

                        akunSumberDana.push({
                            id: item.id,
                            kode: kode,
                            label: `${kode}. - ${nama}`
                        });
                    });
                    sumberDana.addOptions(akunSumberDana);

                    let akunDisimpanKe = [];
                    this.akun.forEach(item => {
                        const kode = item.kode;
                        const nama = item.nama;

                        if (!item.kode.startsWith('1.1.03')) {
                            akunDisimpanKe.push({
                                id: item.id,
                                kode: kode,
                                label: `${kode}. - ${nama}`
                            });
                        }
                    });
                    disimpanKe.addOptions(akunDisimpanKe);
                },

                setAkunHapusInventaris() {
                    const allowedSumberDana = [
                        '1.2.01.01',
                        '1.2.02.01',
                        '1.2.02.02',
                        '1.2.02.03'
                    ];

                    let akunSumberDana = [];
                    this.akun.forEach(item => {
                        if (allowedSumberDana.includes(item.kode)) {
                            akunSumberDana.push({
                                id: item.id,
                                kode: item.kode,
                                label: `${item.kode}. - ${item.nama}`
                            });
                        }
                    });
                    sumberDana.clearOptions();
                    sumberDana.addOptions(akunSumberDana);

                    let akunDisimpanKe = [];
                    this.akun.forEach(item => {
                        if (item.kode.startsWith('1.2') || item.kode.startsWith('1.1')) {
                            return;
                        }
                        akunDisimpanKe.push({
                            id: item.id,
                            kode: item.kode,
                            label: `${item.kode}. - ${item.nama}`
                        });
                    });
                    disimpanKe.clearOptions();
                    disimpanKe.addOptions(akunDisimpanKe);

                    if (this.selectedSumberDana && allowedSumberDana.includes(this.selectedSumberDana)) {
                        try {
                            sumberDana.setValue(this.selectedSumberDana, true);
                        } catch (e) {}
                    }
                    if (this.selectedDisimpanKe) {
                        try {
                            disimpanKe.setValue(this.selectedDisimpanKe, true);
                        } catch (e) {}
                    }

                    this.$nextTick(() => {
                        const sd = this.selectedSumberDana;
                        if (sd && allowedSumberDana.includes(sd)) {
                            window.__selectedSumberDana = sd;
                            window.dispatchEvent(new CustomEvent('sumber-dana-changed', { detail: sd }));
                        }
                    });
                },

                simpanTransaksi() {
                    if (this.mode === 'hapus') {
                        if (!this.selectedDisimpanKe) {
                            Swal.fire('Peringatan', 'Pilih akun Debit (Disimpan Ke) terlebih dahulu', 'warning');
                            return;
                        }
                        if (!this.hapusData || !this.hapusData.id_barang) {
                            Swal.fire('Peringatan', 'Pilih nama barang terlebih dahulu', 'warning');
                            return;
                        }
                        if (!this.hapusData.alasan) {
                            Swal.fire('Peringatan', 'Pilih alasan penghapusan', 'warning');
                            return;
                        }
                        if (!this.hapusData.unit || this.hapusData.unit <= 0) {
                            Swal.fire('Peringatan', 'Isi jumlah unit', 'warning');
                            return;
                        }
                    }

                    Swal.fire({
                        title: 'Simpan Transaksi?',
                        text: 'Data transaksi akan disimpan.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Simpan',
                        cancelButtonText: 'Batal'
                    }).then((result) => {

                        if (!result.isConfirmed) return;
                        window.dispatchEvent(new CustomEvent('kirimInventaris'));
                        this.formatNominal();

                        const payload = {
                            tanggal_pembayaran: document.getElementById('tanggal_transaksi')
                                .value,
                            jenis_transaksi: this.selectedJenisTransaksi,
                            sumber_dana: this.selectedSumberDana,
                            disimpan_ke: this.selectedDisimpanKe,
                            relasi: this.inputKeterangan.length > 0 ? this.inputKeterangan[
                                0].value : null,
                            keterangan: this.inputKeterangan.length > 1 ? this
                                .inputKeterangan[1].value : null,
                            nominal: String(this.nominal).replace(/\D/g, ''),
                            inventaris: this.showFormInventaris ? this.inventarisData : null,
                            hapus_inventaris: this.mode === 'hapus' ? this.hapusData : null
                        };

                        @this.call('saveJurnalUmum', payload);
                    });
                }
            }))
        });
    </script>
@endsection

@pushOnce('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const observer = new MutationObserver(() => {
                if (document.getElementById('nama_barang') &&
                    !document.getElementById('nama_barang').dataset.bound
                ) {
                    document.getElementById('nama_barang').dataset.bound = '1';
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
        });
    </script>
@endpushOnce
