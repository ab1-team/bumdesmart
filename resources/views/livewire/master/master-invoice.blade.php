<div>
    <div class="card">
        <div class="card-body">
            <div class="row justify-content-between mb-3">
                <div class="col-md-4">
                    <input type="search" wire:model.live.debounce.300ms="search" class="form-control"
                        placeholder="Cari no / jenis pembayaran...">
                </div>
                <div class="col-md-4">
                    <select class="form-select tom-select" id="filterMasterStatus" wire:model.live="status">
                        <option value="">Semua Status</option>
                        <option value="PAID">PAID</option>
                        <option value="PARTIAL">PARTIAL</option>
                        <option value="UNPAID">UNPAID</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-select tom-select" id="filterMasterJenis" wire:model.live="jenisPembayaran">
                        <option value="">Semua Jenis Pembayaran</option>
                        <option value="Biaya Lisensi Instalasi">Biaya Lisensi Instalasi</option>
                        <option value="Biaya Perpanjangan Maintenance dan Server">Biaya Perpanjangan Maintenance dan Server</option>
                        <option value="Biaya Bimbingan Teknis">Biaya Bimbingan Teknis</option>
                        <option value="Biaya Migrasi Ulang">Biaya Migrasi Ulang</option>
                        <option value="Biaya Aktivasi WA Gateway">Biaya Aktivasi WA Gateway</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Owner</th>
                            <th>No</th>
                            <th>Jenis Pembayaran</th>
                            <th>Tanggal Invoice</th>
                            <th>Tanggal Diterima</th>
                            <th>Tagihan</th>
                            <th>Jumlah Diterima</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $i => $inv)
                            <tr style="cursor: pointer;"
                                wire:click="bayarInvoice('{{ $inv['id'] }}', '{{ $inv['owner_id'] }}')">
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $inv['nama_usaha'] }}</td>
                                <td><span class="badge bg-blue-lt">{{ $inv['no'] }}</span></td>
                                <td>{{ $inv['jenis_pembayaran'] }}</td>
                                <td class="text-nowrap">
                                    {{ \Carbon\Carbon::parse($inv['tanggal_invoice'])->format('d/m/Y') }}
                                </td>
                                <td class="text-nowrap">
                                    {{ $inv['tanggal_diterima'] ? \Carbon\Carbon::parse($inv['tanggal_diterima'])->format('d/m/Y') : '-' }}
                                </td>
                                <td class="fw-bold">Rp {{ number_format($inv['tagihan'], 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($inv['saldo'], 0, ',', '.') }}</td>
                                <td class="text-center">
                                    @if ($inv['status'] === 'PAID')
                                        <span class="badge bg-success">PAID</span>
                                    @elseif ($inv['status'] === 'PARTIAL')
                                        <span class="badge bg-info">PARTIAL</span>
                                    @else
                                        <span class="badge bg-warning">{{ $inv['status'] }}</span>
                                    @endif
                                </td>
                                <td wire:click.stop>
                                    <a href="/master/invoice/{{ $inv['id'] }}/{{ $inv['owner_id'] }}/cetak"
                                        target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-print"></i> Cetak
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    Belum ada invoice.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="masterInvoiceBayarModal" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ $titleModal ?? 'Pembayaran Invoice' }}</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="savePembayaran">
                        <div class="mb-3">
                            <label class="form-label">Tanggal Diterima <span class="text-danger">*</span></label>
                            <input type="text" class="form-control litepicker" id="tanggalDiterima"
                                wire:model="tanggalDiterima" placeholder="YYYY-MM-DD" autocomplete="off" />
                            @error('tanggalDiterima')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Saldo (Jumlah Pembayaran) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model="saldo"
                                x-mask:dynamic="$money($input, '.', ',', 0)" placeholder="0" />
                            @error('saldo')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select class="form-select tom-select" id="metodePembayaran" wire:model="metodePembayaran">
                                <option value="">-- Pilih Metode --</option>
                                <option value="Terima dari Lembaga via Bank">Terima dari Lembaga via Bank</option>
                                <option value="Terima dari Lembaga via Kas">Terima dari Lembaga via Kas</option>
                            </select>
                            @error('metodePembayaran')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" wire:model="keterangan" rows="3"
                                placeholder="Catatan pembayaran (opsional)"></textarea>
                            @error('keterangan')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success ms-auto" wire:click="savePembayaran">
                        <i class="fas fa-save"></i> Simpan Pembayaran
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>