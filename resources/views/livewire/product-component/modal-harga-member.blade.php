<div class="modal fade" id="hargaMemberModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Harga Member</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if ($detailProduk)
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Harga</th>
                                <th>Nominal</th>
                                <th>Tanggal Mulai</th>
                                <th>Tanggal Selesai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Harga Default</td>
                                <td>
                                    <input type="text" class="form-control" readonly
                                        value="{{ number_format($detailProduk->harga_jual, 0, ',', '.') }}">
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            @foreach ($this->customerGroups as $customerGroup)
                                @php
                                    $hargaJualPerMember = '';
                                    foreach ($detailProduk->productPrices as $productPrice) {
                                        if ($productPrice->customer_group_id == $customerGroup->id) {
                                            $hargaJualPerMember = number_format($productPrice->harga_spesial, 0, ',', '.');
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td>Harga Member {{ $customerGroup->nama_group }}</td>
                                    <td>
                                        <input type="text" class="form-control"
                                            wire:model="hargaJualMember.{{ $customerGroup->id }}"
                                            x-mask:dynamic="$money($input, ',', '.', 2)" x-on:focus="$el.select()" value="{{ $hargaJualPerMember }}">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control litepicker"
                                            id="tanggalMulai-{{ $customerGroup->id }}"
                                            wire:model="tanggalMulai.{{ $customerGroup->id }}"
                                            placeholder="kosongkan untuk tidak ada batas awal">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control litepicker"
                                            id="tanggalAkhir-{{ $customerGroup->id }}"
                                            wire:model="tanggalAkhir.{{ $customerGroup->id }}"
                                            placeholder="kosongkan untuk tidak ada batas akhir">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- Catatan --}}
                    <div class="alert alert-info">
                        Kosongkan nominal untuk menghapus harga member
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary ms-auto" wire:click="simpanHargaMember">
                    Simpan
                </button>
            </div>
        </div>
    </div>
</div>
