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

                    <div class="row" x-show="!showFormInventaris">
                        <template x-for="(item, index) in inputKeterangan" :key="index">
                            <div class="mb-3" :class="inputKeterangan.length > 1 ? 'col-sm-6' : 'col-12'">
                                <label class="form-label" x-text="item.label"></label>
                                <input type="text" class="form-control" :value="item.value" x-model="item.value">
                            </div>
                        </template>
                    </div>

                    <div x-show="showFormInventaris">
                        @include('livewire.keuangan.partials.form_inventaris')
                    </div>

                    <div class="row" x-show="!showFormInventaris">
                        <div class="col-12 my-3">
                            <label class="form-label">Nominal Rp.</label>
                            <input type="text" class="form-control" x-model="nominalFormatted"
                                x-on:input="formatNominal">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-github" x-on:click="simpanTransaksi">Simpan
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

                    Livewire.dispatch('setHargaPerolehan', { total: total });
                },

                kirimData() {
                    const data = {
                        nama_barang: this.nama_barang,
                        umur_ekonomis: this.umur_ekonomis,
                        jumlah: this.jumlah,
                        harga_satuan: this.harga_satuan.replace(/\D/g, ''),
                        harga_perolehan: this.harga_perolehan.replace(/\D/g, '')
                    };

                    window.dispatchEvent(new CustomEvent('inventarisUpdated', { detail: data }));
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
                if (el) Alpine.$data(el).selectedSumberDana = value;
            }
        });

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

                formatNominal() {
                    let angka = this.nominalFormatted.replace(/\D/g, '');
                    this.nominal = angka ? parseInt(angka) : 0;
                    this.nominalFormatted = new Intl.NumberFormat('id-ID').format(this.nominal);
                },

                init() {
                    window.addEventListener('inventarisUpdated', (event) => {
                        this.inventarisData = event.detail;
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

                        console.log(this.showFormInventaris);
                        this.setAkunJenisTransaksi1();
                    }

                    if (jenisTransaksiId === '2') {
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
                    sumberDana.addOption(akunSumberDana);

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
                    disimpanKe.addOption(akunDisimpanKe);
                },

                simpanTransaksi() {
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
                            tanggal_pembayaran: document.getElementById('tanggal_transaksi').value,
                            jenis_transaksi: this.selectedJenisTransaksi,
                            sumber_dana: this.selectedSumberDana,
                            disimpan_ke: this.selectedDisimpanKe,
                            relasi: this.inputKeterangan.length > 0 ? this.inputKeterangan[0].value : null,
                            keterangan: this.inputKeterangan.length > 1 ? this.inputKeterangan[1].value : null,
                            nominal: String(this.nominal).replace(/\D/g, ''), // <-- koma ini penting
                            inventaris: this.showFormInventaris ? this.inventarisData : null 
                        };

                        @this.call('saveJurnalUmum', payload);
                    });
                }
            }))
        });
    </script>
@endsection
