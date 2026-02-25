<div>
    <div class="card" x-data="laporanPage()">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="form-group">
                        <label class="form-label">Tahunan</label>
                        <select x-model="tahun" class="form-select tom-select" id="tahun">
                            @for ($i = date('Y'); $i >= date('Y') - 5; $i--)
                                <option value="{{ $i }}" {{ date('Y') == $i ? 'selected' : '' }}>
                                    {{ $i }}
                                </option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="form-group">
                        <label class="form-label">Bulanan</label>
                        <select x-model="bulan" class="form-select tom-select" id="bulan">
                            <option value="-">-</option>
                            <option value="01" {{ date('m') == '01' ? 'selected' : '' }}>Januari</option>
                            <option value="02" {{ date('m') == '02' ? 'selected' : '' }}>Februari</option>
                            <option value="03" {{ date('m') == '03' ? 'selected' : '' }}>Maret</option>
                            <option value="04" {{ date('m') == '04' ? 'selected' : '' }}>April</option>
                            <option value="05" {{ date('m') == '05' ? 'selected' : '' }}>Mei</option>
                            <option value="06" {{ date('m') == '06' ? 'selected' : '' }}>Juni</option>
                            <option value="07" {{ date('m') == '07' ? 'selected' : '' }}>Juli</option>
                            <option value="08" {{ date('m') == '08' ? 'selected' : '' }}>Agustus</option>
                            <option value="09" {{ date('m') == '09' ? 'selected' : '' }}>September</option>
                            <option value="10" {{ date('m') == '10' ? 'selected' : '' }}>Oktober</option>
                            <option value="11" {{ date('m') == '11' ? 'selected' : '' }}>November</option>
                            <option value="12" {{ date('m') == '12' ? 'selected' : '' }}>Desember</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="form-group">
                        <label class="form-label">Harian</label>
                        <select x-model="periode" class="form-select tom-select" id="periode">
                            <option value="-">-</option>
                            @for ($i = 1; $i <= 31; $i++)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label class="form-label">Nama Laporan</label>
                        <select x-model="jenis_laporan" class="form-select tom-select" id="jenis_laporan">
                            <option value="">- pilih nama laporan -</option>
                            <optgroup label="📋 Laporan Harian">
                                <option value="penjualanHarian">Laporan Penjualan Harian</option>
                                <option value="stokMinimum">Laporan Stok Minimum</option>
                            </optgroup>
                            <optgroup label="📊 Laporan Keuangan">
                                <option value="jurnalTransaksi">Jurnal Transaksi</option>
                                <option value="bukuBesar">Laporan Buku Besar</option>
                                <option value="neraca">Laporan Neraca</option>
                                <option value="labaRugi">Laporan Laba Rugi</option>
                                <option value="arusKas">Laporan Arus Kas</option>
                                <option value="asetTetapInventaris">Aset Tetap dan Inventaris</option>
                                <option value="pembelian">Laporan Pembelian</option>
                                <option value="piutang">Laporan Piutang (Customer)</option>
                                <option value="hutang">Laporan Hutang (Supplier)</option>
                                <option value="retur">Laporan Retur</option>
                            </optgroup>
                            <optgroup label="📦 Laporan Produk & Stok">
                                <option value="produkTerlaris">Laporan Produk Terlaris</option>
                                <option value="marginProduk">Laporan Margin & Profitabilitas</option>
                                <option value="inventoryTurnover">Laporan Inventory Turnover</option>
                                <option value="stokOpname">Laporan Stok Opname</option>
                            </optgroup>
                            <optgroup label="👥 Laporan Customer">
                                <option value="customerTerbaik">Laporan Customer Terbaik</option>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label class="form-label">Nama Sub Laporan</label>
                        <select x-model="jenis_sub_laporan" class="form-select tom-select" id="jenis_sub_laporan">
                            <option value="">-</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button @click="openReport" class="btn btn-primary">
                    Preview (New Window)
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function laporanPage() {
        return {
            tahun: @entangle('tahun'),
            bulan: @entangle('bulan'),
            periode: @entangle('periode'),
            jenis_laporan: @entangle('jenis_laporan'),
            jenis_sub_laporan: @entangle('jenis_sub_laporan'),
            daftarAkun: @entangle('daftarAkun'),
            init() {
                this.$watch('jenis_laporan', (value) => this.jenisLaporan(value));
            },
            jenisLaporan(value) {
                Select['jenis_sub_laporan'].clearOptions();

                if (value === 'bukuBesar') {
                    this.daftarAkun.forEach(account => {
                        Select['jenis_sub_laporan'].addOption({
                            value: account.kode,
                            text: `${account.kode}. ${account.nama}`
                        });
                    });

                }
            },
            openReport() {
                if (!this.jenis_laporan) {
                    Toast.fire({
                        icon: 'error',
                        title: 'Silakan pilih nama laporan terlebih dahulu.'
                    });

                    return;
                }

                let params = new URLSearchParams({
                    tahun: this.tahun,
                    bulan: this.bulan,
                    periode: this.periode,
                    laporan: this.jenis_laporan,
                    sub_laporan: this.jenis_sub_laporan
                });

                window.open('/keuangan/pelaporan/cetak?' + params.toString(), '_blank');
            }
        }
    }
</script>
